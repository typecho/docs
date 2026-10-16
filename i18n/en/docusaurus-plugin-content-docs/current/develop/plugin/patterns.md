---
title: Common Plugin Patterns
sidebar_position: 9
translation: machine
source_commit: fc55a1004f355202effc7d999ebe984ff3ec2cf2
---

# Common Plugin Patterns

This page shows how to write the five most common kinds of plugin. Each one is a complete plugin you can activate as is. You can look up the arguments and call sites of every hook used here in the [hook list](../reference/hooks.md).

## Filtering content: contentEx and excerptEx {#content-filter}

The body and excerpt of posts and pages pass through a series of filter hooks before output, all on `Widget\Base\Contents` (handle `Widget_Abstract_Contents`):

| Hook | Type | When |
|---|---|---|
| `content` | trigger + filter | **Before** Markdown parsing; receives the source text. Once registered, Typecho no longer parses Markdown, and the plugin is responsible for turning the source text into HTML |
| `markdown` / `autoP` | trigger + filter | Markdown parsing / automatic paragraphs; once registered, replaces Typecho's built-in parser |
| `contentEx` | filter | **After** HTML is generated; receives the final body HTML |
| `excerpt` | filter | Before the excerpt is cut; receives the full body HTML |
| `excerptEx` | filter | After the excerpt is generated; receives the excerpt HTML |

When you only want to make small changes to the HTML (add attributes, replace text, insert content), use `contentEx` and `excerptEx`. Comment bodies have a set of hooks with the same names on `Widget\Base\Comments` (handle `Widget_Abstract_Comments`).

The plugin below adds `target="_blank"` to external links:

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

Key points:

- The excerpt is cut from the **already filtered body**, so the HTML that `excerptEx` receives may already have been processed once by `contentEx`. Your filter function must be safe to run more than once; the example achieves this by skipping links that already have `target=`.
- The filtered body is also used for the page's `description` meta tag and for feeds. Content inserted before or after the body shows up there too. Content you only want to display on the page is better placed in the theme, or offered to the theme through a [dynamic hook](./hooks.md#动态钩子).
- `contentEx` applies everywhere a body is output: posts, pages, the home page list, feeds and so on. To handle only one kind of page, check the second argument (the content object) in the callback, e.g. `$widget instanceof \Widget\Archive && $widget->is('single')`.

## Comment anti-spam: comment on Widget_Feedback {#anti-spam}

When a visitor submits a comment, after validating the required fields and before writing to the database, Typecho passes the comment data to the `comment` filter of `Widget\Feedback`:

- The first argument is the array about to be written to the `comments` table, including `cid`, `created`, `author`, `mail`, `url`, `text`, `ip`, `agent`, `ownerId`, `authorId` (logged-in user), `parent`, `type`, `status`.
- The second argument is the post being commented on (a `Widget\Archive` object).
- The callback returns the modified array. Set `status` to `waiting` to send it to moderation, or to `spam` to send it straight to spam.
- To reject the comment, throw `\Typecho\Widget\Exception`. The visitor sees the exception message, and the comment they typed is kept and is still in the input box when they go back.

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

Trackbacks have a corresponding `trackback` filter used the same way; pingbacks go through the `pingback` filter of `Widget\XmlRpc`. To do something after the comment is written (for example sending an email notification), use `finishComment` on `Widget\Feedback`. It receives the `Widget\Feedback` object, and `$feedback->coid`, `$feedback->status` and so on are the comment that was just written.

## Attachment storage: the handler hooks of Widget_Upload {#external-storage}

`Widget\Upload` has five trigger hooks. Once registered, the plugin takes over saving, replacing, deleting and addressing attachments:

| Hook | Arguments | Callback returns |
|---|---|---|
| `uploadHandle` | `$file`: the uploaded file, with `name`, `tmp_name`, `size`; uploads through XML-RPC have no `tmp_name`, the content is in `bits` | File info array, or `false` on failure |
| `modifyHandle` | `$content`: the original attachment, `$content['attachment']` is its file info; `$file`: the new file | New file info array, or `false` on failure |
| `deleteHandle` | `$content`: the attachment to delete, as above | Whether the deletion succeeded |
| `attachmentHandle` | `$attachment`: file info (a `Typecho\Config` object) | The attachment's URL |
| `attachmentDataHandle` | `$content` | File content. Typecho itself does not call it in 1.3.0, so you can leave it unimplemented |

"File info" is an array. Typecho stores it as JSON in the `text` field of the attachment record and later hands it back to the plugin as `$content['attachment']` or `$attachment`:

```php
[
    'name' => 'photo.png',             // 原始文件名
    'path' => '/2026/10/12345678.png', // 插件自己用来定位文件的路径
    'size' => 1024,                     // 字节数
    'type' => 'png',                    // 扩展名，小写
    'mime' => 'image/png',
]
```

The plugin below saves attachments to a configurable directory and serves them from a configurable URL, for example a mounted network drive paired with a separate static file domain. Replace `save()`, `deleteHandle()` and `attachmentHandle()` with an object storage SDK's upload, delete and URL generation calls, and you have an object storage plugin:

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

Key points:

- **Once you take over uploads, Typecho no longer checks the file type.** The example calls `\Widget\Upload::checkFileType($ext)`, which checks against the attachment types allowed in Basic Settings.
- You must implement `deleteHandle`; otherwise deleting an attachment in the admin only deletes the record, and the file stays in storage.
- When replacing an attachment, Typecho's built-in logic requires the old and new files to have the same extension; the example keeps this rule.
- `attachmentHandle` is called every time an attachment URL is read, so do not make network requests in it.
- The `path` in the file info is decided by the plugin and only that plugin understands it. **After the plugin is deactivated**, attachments it saved still get URLs the default Typecho way (site URL + `path`), which usually do not work. Warn users about this in the plugin description.

`Widget\Upload` also has a few notification hooks: `beforeUpload` / `beforeModify` receive the file info array before it is written to the database, and `upload` / `modify` receive the `Widget\Upload` object after it is written.

If a site owner only wants attachments served through a CDN without changing where they are stored, no plugin is needed; see [Attachment Storage Location](../../guide/operations/uploads-storage.md).

## Replacing the editor: richEditor {#editor}

The post and page writing screens trigger `richEditor` before outputting the editor scripts:

```php
\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}
```

Once it is registered, Typecho's built-in Markdown editor (toolbar, preview, shortcuts) is no longer loaded. The page only has a plain textarea with `id="text"`, and the plugin outputs scripts to turn it into a different editor. Posts and pages are two separate handles; register both.

When you replace the editor, you must provide two functions that other Typecho scripts depend on:

- `Typecho.insertFileToEditor(file, url, isImage)`: called when an attachment name is clicked in the attachment panel; inserts the attachment into the editor.
- `Typecho.uploadComplete(attachment)`: called after an attachment upload finishes.

Saving, autosave and preview all read the value of the `#text` textarea; if your editor keeps the content elsewhere, write it back to `#text` continuously. In addition, the built-in editor is responsible for triggering the `write` event on the form when the content changes (`$('#text').parents('form').trigger('write')`). Autosave and the "content has changed but not been saved" leave-page warning both depend on it, so a replacement editor has to trigger it itself.

The plugin below replaces the built-in editor with a plain textarea without toolbar or preview, and implements the two functions above and the `write` event:

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

    // 内容变化时通知表单，自动保存和离开页面提示依赖这个事件
    textarea.on('input', function () {
        textarea.parents('form').trigger('write');
    });

    // 附件面板中点击“插入”时调用，必须由编辑器提供
    Typecho.insertFileToEditor = function (file, url, isImage) {
        const el = textarea.get(0);
        const html = isImage ? '![' + file + '](' + url + ')' : '[' + file + '](' + url + ')';
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

If you only want to **extend** the built-in editor (for example add a toolbar button), do not use `richEditor`. Use the `markdownEditor` hook of `admin/editor-js.php` instead: it is called in the middle of the built-in editor's initialization script and receives the current post, and the JavaScript the plugin outputs there can use the variables in that script. Once `richEditor` is registered, `markdownEditor` is no longer triggered.

## Login events {#login}

Login-related hooks live on two classes:

| Handle | Hook | When |
|---|---|---|
| `Widget_User` | `login` | Trigger; takes over the whole login verification and returns whether it succeeded |
| `Widget_User` | `hashValidate` | Trigger; takes over password verification, receives the plaintext password and the hash from the database, and returns whether they match |
| `Widget_User` | `loginSucceed` / `loginFail` | After every successful / failed login, through any route, including the login form and XML-RPC |
| `Widget_Login` | `loginSuccess` / `loginFailure` | Only after a successful / failed login through the admin login form |
| `Widget_User` | `logout` | Trigger; takes over logout: once registered, Typecho no longer clears the login cookie |
| `Widget_Logout` | `logout` | After the user clicks "Logout" and the login state has been cleared |

To record logins through every route, use `loginSucceed` / `loginFail` on `Widget_User`. They receive the `Widget\User` object, the login name, the **plaintext password**, whether the login is temporary (every XML-RPC request is a temporary login) and the expiry. Do not write the password to logs or send it anywhere.

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
 * 把每次登录成功和失败写入 PHP 错误日志
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

After activation, every login through the login form and XML-RPC clients leaves a line in the PHP error log, for example:

```text
[LoginLog] success user=admin uid=1 ip=203.0.113.5 temporarily=0
[LoginLog] failure name=admin ip=203.0.113.5
```

For the log location, see [Debugging and Troubleshooting](../../guide/operations/debugging.md#logs).
