---
title: 常见插件模式
sidebar_position: 9
---

# 常见插件模式

本页给出五类最常见的插件的写法，每一类都是一个可以直接启用的完整插件。用到的钩子在[钩子列表](../reference/hooks.md)中都能查到参数和调用位置。

## 过滤内容：contentEx 与 excerptEx {#content-filter}

文章、页面的正文和摘要在输出前会经过一系列过滤钩子，都在 `Widget\Base\Contents` 上（句柄 `Widget_Abstract_Contents`）：

| 钩子 | 类型 | 时机 |
|---|---|---|
| `content` | trigger + filter | Markdown 解析**之前**，收到原文。注册后 Typecho 不再解析 Markdown，由插件负责把原文转成 HTML |
| `markdown` / `autoP` | trigger + filter | 解析 Markdown / 自动分段，注册后替换 Typecho 自带的解析器 |
| `contentEx` | filter | 生成 HTML **之后**，收到最终的正文 HTML |
| `excerpt` | filter | 截取摘要之前，收到完整的正文 HTML |
| `excerptEx` | filter | 摘要生成之后，收到摘要 HTML |

只想在 HTML 上做点修改（加属性、替换文字、插入内容）时用 `contentEx` 和 `excerptEx`。评论的正文有同名的 `content`、`markdown`、`autoP`、`contentEx` 钩子，在 `Widget\Base\Comments` 上（句柄 `Widget_Abstract_Comments`）；评论没有 `excerpt` 和 `excerptEx` 钩子，修改评论 HTML 用 `contentEx`。

下面的插件给站外链接加上 `target="_blank"`：

```php title="usr/plugins/LinkTarget/Plugin.php"
<?php

namespace TypechoPlugin\LinkTarget;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 站外链接在新窗口打开
 *
 * @package LinkTarget
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::filter';
        \Typecho\Plugin::factory('Widget\Base\Contents')->excerptEx = __CLASS__ . '::filter';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function filter(string $html): string
    {
        $host = parse_url(Options::alloc()->siteUrl, PHP_URL_HOST);

        return preg_replace_callback('/<a\s[^>]*href="(https?:\/\/[^"]+)"[^>]*>/i', function ($m) use ($host) {
            if (parse_url($m[1], PHP_URL_HOST) === $host || stripos($m[0], 'target=') !== false) {
                return $m[0];
            }

            return substr($m[0], 0, -1) . ' target="_blank" rel="noopener noreferrer">';
        }, $html);
    }
}
```

要点：

- 摘要是从**已经过滤的正文**截取的，所以 `excerptEx` 收到的 HTML 可能已经被 `contentEx` 处理过一次。过滤函数要能重复执行而不出错，上例用“已经有 `target=` 的链接跳过”做到这一点；
- 过滤后的正文还会用于页面的 `description` 元信息和订阅源。在正文前后插入的内容也会出现在这些地方；只想在页面上显示的内容，更适合放在主题中，或者用[动态钩子](./hooks.md#动态钩子)提供给主题调用；
- `contentEx` 对文章、页面、首页列表、订阅源等所有输出正文的地方都生效。只想处理某一类页面时，在回调中检查第二个参数（内容对象），例如 `$widget instanceof \Widget\Archive && $widget->is('single')`。

## 评论反垃圾：Widget_Feedback 的 comment {#anti-spam}

访客提交评论时，Typecho 校验完必填项后、写入数据库之前，把评论数据交给 `Widget\Feedback` 的 `comment` 过滤器：

- 第一个参数是即将写入 `comments` 表的数组，包括 `cid`、`created`、`author`、`mail`、`url`、`text`、`ip`、`agent`、`ownerId`、`authorId`（登录用户）、`parent`、`type`、`status`；
- 第二个参数是被评论的文章（`Widget\Archive` 对象）；
- 回调返回修改后的数组。把 `status` 改为 `waiting` 进入待审核，改为 `spam` 直接进入垃圾评论；
- 要拒绝这条评论，抛出 `\Typecho\Widget\Exception`，访客看到异常信息，已填写的评论内容会被保留，返回时仍在输入框中。

```php title="usr/plugins/CommentGuard/Plugin.php"
<?php

namespace TypechoPlugin\CommentGuard;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 简单的评论过滤：拒绝含有屏蔽词的评论，链接过多的评论标为垃圾
 *
 * @package CommentGuard
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Feedback')->comment = __CLASS__ . '::check';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $words = new Textarea('words', null, '', _t('屏蔽词'), _t('每行一个，评论内容、名字、网址中出现任何一个就拒绝提交'));
        $form->addInput($words);

        $maxLinks = new Text('maxLinks', null, '2', _t('链接数上限'), _t('评论中的链接超过这个数目时标为垃圾'));
        $maxLinks->addRule('isInteger', _t('请填写整数'));
        $form->addInput($maxLinks);
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function check(array $comment, $post): array
    {
        $config = Options::alloc()->plugin('CommentGuard');
        $haystack = $comment['text'] . "\n" . $comment['author'] . "\n" . $comment['url'];

        foreach (preg_split('/\r?\n/', (string) $config->words) as $word) {
            $word = trim($word);
            if ($word !== '' && mb_stripos($haystack, $word) !== false) {
                throw new Exception(_t('评论中含有不允许的内容'), 403);
            }
        }

        if (preg_match_all('/https?:\/\//i', $comment['text']) > (int) $config->maxLinks) {
            $comment['status'] = 'spam';
        }

        return $comment;
    }
}
```

引用通告（trackback）有对应的 `trackback` 过滤器，用法相同；pingback 在 `Widget\XmlRpc` 的 `pingback` 过滤器中。需要在评论写入之后做事（例如发邮件通知），用 `Widget\Feedback` 的 `finishComment`，它收到 `Widget\Feedback` 对象，`$feedback->coid`、`$feedback->status` 等就是刚写入的评论。

## 附件存储：Widget_Upload 的处理钩子 {#external-storage}

`Widget\Upload` 上有五个 trigger 类钩子，注册后由插件接管附件的保存、替换、删除和地址：

| 钩子 | 参数 | 回调返回 |
|---|---|---|
| `uploadHandle` | `$file`：上传的文件，含 `name`、`tmp_name`、`size`；通过 XML-RPC 上传时没有 `tmp_name`，内容在 `bits` 中 | 文件信息数组，失败时返回 `false` |
| `modifyHandle` | `$content`：原附件，`$content['attachment']` 是它的文件信息；`$file`：新文件 | 新的文件信息数组，失败时返回 `false` |
| `deleteHandle` | `$content`：要删除的附件，同上 | 是否删除成功 |
| `attachmentHandle` | `$attachment`：文件信息（`Typecho\Config` 对象） | 附件的访问地址 |
| `attachmentDataHandle` | `$content` | 文件内容。1.3.0 中 Typecho 自己没有调用它，可以不实现 |

“文件信息”是一个数组，Typecho 把它以 JSON 存进附件记录的 `text` 字段，之后以 `$content['attachment']` 或 `$attachment` 交还给插件：

```php
[
    'name' => 'photo.png',             // 原始文件名
    'path' => '/2026/10/12345678.png', // 插件自己用来定位文件的路径
    'size' => 1024,                     // 字节数
    'type' => 'png',                    // 扩展名，小写
    'mime' => 'image/png',
]
```

下面的插件把附件保存到一个可配置的目录，并通过可配置的地址访问，例如一个挂载的网络磁盘配一个独立的静态文件域名。把 `save()`、`deleteHandle()` 和 `attachmentHandle()` 换成对象存储 SDK 的上传、删除和生成地址，再改写 `info()`，就是一个对象存储插件。`info()` 从本地的目标文件读取大小和 MIME 类型，文件只上传到对象存储时本地没有这个文件，所以要在上传之前从临时文件（`$file['tmp_name']`）或 XML-RPC 传来的文件内容取得这两项，再和对象存储中的路径一起返回：

```php title="usr/plugins/LocalBucket/Plugin.php"
<?php

namespace TypechoPlugin\LocalBucket;

use Typecho\Common;
use Typecho\Config;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;
use Widget\Upload;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 把附件保存到指定目录，并通过指定的地址访问
 *
 * @package LocalBucket
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Upload')->uploadHandle = __CLASS__ . '::uploadHandle';
        \Typecho\Plugin::factory('Widget\Upload')->modifyHandle = __CLASS__ . '::modifyHandle';
        \Typecho\Plugin::factory('Widget\Upload')->deleteHandle = __CLASS__ . '::deleteHandle';
        \Typecho\Plugin::factory('Widget\Upload')->attachmentHandle = __CLASS__ . '::attachmentHandle';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $dir = new Text('dir', null, __TYPECHO_ROOT_DIR__ . '/usr/bucket', _t('保存目录'), _t('服务器上的绝对路径，PHP 要有写权限'));
        $dir->addRule('required', _t('必须填写保存目录'));
        $form->addInput($dir);

        $url = new Text('url', null, Common::url('usr/bucket', Options::alloc()->siteUrl), _t('访问地址'), _t('这个地址要能访问到保存目录中的文件'));
        $url->addRule('required', _t('必须填写访问地址'));
        $form->addInput($url);
    }

    public static function personalConfig(Form $form)
    {
    }

    /**
     * 上传：保存文件，返回文件信息；返回 false 表示失败
     */
    public static function uploadHandle(array $file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!Upload::checkFileType($ext)) {
            return false;
        }

        $path = '/' . date('Y/m') . '/' . sprintf('%u', crc32(uniqid())) . '.' . $ext;

        return self::save($file, $path) ? self::info($file['name'], $path, $ext) : false;
    }

    /**
     * 替换：用新文件覆盖原文件，扩展名必须相同
     */
    public static function modifyHandle(array $content, array $file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $path = $content['attachment']->path;

        if ($ext !== $content['attachment']->type || !self::save($file, $path)) {
            return false;
        }

        return self::info($content['attachment']->name, $path, $ext);
    }

    /**
     * 删除：删除文件，返回是否成功
     */
    public static function deleteHandle(array $content): bool
    {
        return @unlink(self::root() . $content['attachment']->path);
    }

    /**
     * 地址：返回附件的访问地址
     */
    public static function attachmentHandle(Config $attachment): string
    {
        return Common::url($attachment->path, Options::alloc()->plugin('LocalBucket')->url);
    }

    private static function root(): string
    {
        return rtrim(Options::alloc()->plugin('LocalBucket')->dir, '/');
    }

    private static function save(array $file, string $path): bool
    {
        $target = self::root() . $path;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
            return false;
        }

        if (isset($file['tmp_name'])) {
            return move_uploaded_file($file['tmp_name'], $target);
        }

        // 通过 XML-RPC 上传时没有临时文件，文件内容在 bits 中
        $bytes = $file['bytes'] ?? $file['bits'] ?? null;
        return $bytes !== null && file_put_contents($target, $bytes) !== false;
    }

    private static function info(string $name, string $path, string $ext): array
    {
        $target = self::root() . $path;

        return [
            'name' => $name,
            'path' => $path,
            'size' => filesize($target),
            'type' => $ext,
            'mime' => Common::mimeContentType($target),
        ];
    }
}
```

要点：

- **接管上传后，Typecho 不再检查文件类型**。上例调用 `\Widget\Upload::checkFileType($ext)`，按“基本设置”中允许的附件类型检查；
- `deleteHandle` 一定要实现，否则后台删除附件时只删除了记录，存储中的文件还在；
- 替换附件时，Typecho 自带的逻辑要求新旧文件扩展名相同，上例沿用了这个规则；
- 每次读取附件地址都会调用 `attachmentHandle`，不要在里面发网络请求；
- 启用后，**所有**附件的地址、替换和删除都按“访问地址 / 保存目录 + `path`”处理，包括启用之前上传、`path` 为 `/usr/uploads/…` 的附件。在已有附件的站点上启用前，先把站点目录下的 `usr/uploads` 按原样复制到保存目录中（成为 `保存目录/usr/uploads/…`），否则这些附件的地址无法访问，删除附件时也只删除了记录、原文件还在。之后修改保存目录或访问地址时，同样要自己把已有文件搬到新位置；
- 文件信息中的 `path` 由插件自己决定，只有这个插件能理解。**禁用插件后**，它保存的附件仍按 Typecho 默认的方式（站点地址 + `path`）生成地址，通常无法访问。在插件说明中提醒用户这一点。

`Widget\Upload` 上还有几个通知类钩子：`beforeUpload` / `beforeModify` 在写入数据库之前收到文件信息数组，`upload` / `modify` 在写入之后收到 `Widget\Upload` 对象。

站长只想让附件通过 CDN 访问而不改变存储位置时，不需要插件，见[附件存储位置](../../guide/operations/uploads-storage.md)。

## 替换编辑器：richEditor {#editor}

撰写文章和页面的页面在输出编辑器脚本前触发 `richEditor`：

```php
\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}
```

注册之后，Typecho 自带的 Markdown 编辑器（工具栏、预览、快捷键）就不再加载，页面上只剩一个 `id="text"` 的普通文本框，由插件输出脚本把它变成别的编辑器。文章和页面是两个句柄，两个都要注册。

替换编辑器时，要提供 Typecho 其他脚本依赖的两个函数：

- `Typecho.insertFileToEditor(file, url, isImage)`：在附件面板中点击附件名时调用，把附件插入编辑器；
- `Typecho.uploadComplete(attachment)`：附件上传完成后调用。

保存、自动保存和预览都读取 `#text` 文本框的值，编辑器把内容放在别处时要随时写回 `#text`。另外，自带编辑器负责在内容变化时对表单触发 `write` 事件（`$('#text').parents('form').trigger('write')`），自动保存和“内容已经改变尚未保存”的离开提示都依赖它，替换后的编辑器要自己触发。

下面的插件用一个不带工具栏和预览的纯文本框代替自带编辑器，实现了上面两个函数和 `write` 事件：

```php title="usr/plugins/PlainEditor/Plugin.php"
<?php

namespace TypechoPlugin\PlainEditor;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 用不带工具栏和预览的纯文本框代替自带的 Markdown 编辑器
 *
 * @package PlainEditor
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/write-post.php')->richEditor = __CLASS__ . '::render';
        \Typecho\Plugin::factory('admin/write-page.php')->richEditor = __CLASS__ . '::render';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function render($post)
    {
        ?>
<script>
$(document).ready(function () {
    const textarea = $('#text');
    // 内容按 Markdown 保存时，表单中有 name="markdown" 的隐藏字段；没有时按 HTML 插入附件
    const isMarkdown = textarea.parents('form').find('input[name="markdown"]').length > 0;

    // 内容变化时通知表单，自动保存和离开页面提示依赖这个事件
    textarea.on('input', function () {
        textarea.parents('form').trigger('write');
    });

    // 附件面板中点击“插入”时调用，必须由编辑器提供
    Typecho.insertFileToEditor = function (file, url, isImage) {
        const el = textarea.get(0);
        const html = isMarkdown
            ? (isImage ? '![' + file + '](' + url + ')' : '[' + file + '](' + url + ')')
            : (isImage ? '<img src="' + url + '" alt="' + file + '" />' : '<a href="' + url + '">' + file + '</a>');
        const start = el.selectionStart, end = el.selectionEnd;

        el.value = el.value.substring(0, start) + html + el.value.substring(end);
        el.selectionStart = el.selectionEnd = start + html.length;
        textarea.trigger('input').focus();
    };

    // 上传完成后自动插入
    Typecho.uploadComplete = function (attachment) {
        Typecho.insertFileToEditor(attachment.title, attachment.url, attachment.isImage);
    };
});
</script>
        <?php
    }
}
```

只想**扩展**自带编辑器（例如加一个工具栏按钮）时，不要用 `richEditor`，而是用 `admin/editor-js.php` 的 `markdownEditor` 钩子：它在自带编辑器初始化的脚本中间被调用，收到当前文章，插件在这里输出的 JavaScript 可以使用其中的变量。注册了 `richEditor` 之后，`markdownEditor` 不会再被触发。

## 登录事件 {#login}

和登录有关的钩子在两个类上：

| 句柄 | 钩子 | 时机 |
|---|---|---|
| `Widget_User` | `login` | trigger 类，接管整个登录验证，返回是否成功 |
| `Widget_User` | `hashValidate` | trigger 类，接管密码校验，收到明文密码和数据库中的哈希，返回是否匹配 |
| `Widget_User` | `loginSucceed` / `loginFail` | 找到账号并校验密码之后，成功 / 失败时触发，登录表单、XML-RPC 等途径都会经过；用户名不存在、或 `login` 被插件接管时不触发 |
| `Widget_Login` | `loginSuccess` / `loginFailure` | 只在后台登录表单登录成功 / 失败后 |
| `Widget_User` | `logout` | trigger 类，接管退出登录：注册后 Typecho 不再清除登录 Cookie |
| `Widget_Logout` | `logout` | 用户点击“登出”、登录状态清除之后 |

记录登录表单和 XML-RPC 等途径的登录用 `Widget_User` 上的 `loginSucceed` / `loginFail`。它们收到 `Widget\User` 对象、登录名、**明文密码**、是否临时登录（XML-RPC 每次请求都是临时登录）和有效期。不要把密码写进日志或发送出去。

这两个钩子只在找到账号、校验过密码之后触发：用户名（或邮箱）不存在时登录直接失败，不触发 `loginFail`；有插件注册了 `login` 钩子接管验证时，两个都不触发。要记录后台登录表单上的所有失败（包括用户名不存在），再注册 `Widget_Login` 的 `loginFailure`，它收到 `Widget\User` 对象、登录名、明文密码和是否勾选了“记住我”四个参数；密码错误时它和 `loginFail` 都会触发。

```php title="usr/plugins/LoginLog/Plugin.php"
<?php

namespace TypechoPlugin\LoginLog;

use Typecho\Plugin\PluginInterface;
use Typecho\Request;
use Typecho\Widget\Helper\Form;
use Widget\User;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 把登录成功和密码错误写入 PHP 错误日志
 *
 * @package LoginLog
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\User')->loginSucceed = __CLASS__ . '::succeed';
        \Typecho\Plugin::factory('Widget\User')->loginFail = __CLASS__ . '::fail';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function succeed(User $user, string $name, string $password, bool $temporarily, int $expire)
    {
        error_log(sprintf(
            '[LoginLog] success user=%s uid=%d ip=%s temporarily=%d',
            $user->name,
            $user->uid,
            Request::getInstance()->getIp(),
            $temporarily
        ));
    }

    public static function fail(User $user, string $name, string $password, bool $temporarily, int $expire)
    {
        error_log(sprintf(
            '[LoginLog] failure name=%s ip=%s',
            $name,
            Request::getInstance()->getIp()
        ));
    }
}
```

启用后，登录表单和 XML-RPC 客户端的登录成功和密码错误都会在 PHP 错误日志中留下一行（用户名不存在的尝试不会记录），例如：

```text
[LoginLog] success user=admin uid=1 ip=203.0.113.5 temporarily=0
[LoginLog] failure name=admin ip=203.0.113.5
```

日志位置见[调试与排错](../../guide/operations/debugging.md#logs)。
