---
title: 使用钩子
sidebar_position: 4
---

# 使用钩子

钩子（hook）是 Typecho 代码中预留的位置：执行到这里时，Typecho 依次调用插件注册在这个位置上的回调。插件几乎所有的功能都通过钩子实现。

一个钩子由两部分确定：**句柄**（handle，钩子所在的文件或类）和**钩子名**。例如 `admin/menu.php` 中的 `navBar`、`Widget\Archive` 中的 `footer`。Typecho 提供的全部钩子见[钩子列表](../reference/hooks.md)。

## 注册回调

在插件的 `activate()` 中，用 `Typecho\Plugin::factory(句柄)` 取得句柄对象，再把回调赋给钩子名：

```php
public static function activate()
{
    \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::navBar';
    \Typecho\Plugin::factory('Widget\Archive')->footer = [__CLASS__, 'footer'];
}
```

- 回调写成字符串 `'类名::方法名'` 或数组 `[类名, '方法名']`，指向一个 `public static` 方法。回调会以 JSON 形式保存到数据库，不能使用闭包；
- 注册在启用插件时执行一次并保存，修改注册代码后要禁用再启用插件，见[插件结构与生命周期](./structure.md#启用时发生了什么)；
- 同一个钩子可以注册多个回调，不同插件的回调也可以注册在同一个钩子上，按优先级依次执行。

## 句柄的名字

句柄分两类：

- **文件句柄**：后台模板和入口文件中的钩子，句柄就是文件的相对路径，如 `admin/menu.php`、`admin/write-post.php`、`index.php`；
- **类句柄**：Widget 类中的钩子，句柄是类名。

类名可以写成 `Widget\Archive`、`\Widget\Archive`、`Widget_Archive` 或 `\Widget\Archive::class`，Typecho 会统一换算：

1. 如果类有旧类名别名（见[旧类名对照表](../reference/class-aliases.md)），换成别名；
2. 否则把 `\` 换成 `_`，去掉开头的 `_`。

所以下面三种写法注册到的是同一个钩子：

```php
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::filter';
\Typecho\Plugin::factory(\Widget\Base\Contents::class)->contentEx = __CLASS__ . '::filter';
\Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = __CLASS__ . '::filter';
```

它们的句柄都是 `Widget_Abstract_Contents`（`Widget\Base\Contents` 的旧类名）。同理，`Widget\Contents\Page\Rows` 的句柄是 `Widget_Contents_Page_List`，`Widget\Metas\Category\Rows` 的句柄是 `Widget_Metas_Category_List`。[钩子列表](../reference/hooks.md)中列出的是换算后的名字。

### 句柄是哪个类

Typecho 在类中触发钩子有两种写法，决定了钩子挂在哪个类上：

- `Contents::pluginHandle()`、`Plugin::factory(Upload::class)` 这样写明类名的，句柄固定是这个类。例如 `contentEx` 写在 `Widget\Base\Contents` 中，用 `Contents::pluginHandle()` 触发，所以首页文章列表、文章页、最新文章列表等所有内容对象输出正文时都会触发 `Widget_Abstract_Contents` 的 `contentEx`；
- `self::pluginHandle()` 的句柄是**当前对象实际所属的类**。例如 `Widget\Archive` 中的 `header`，句柄是 `Widget_Archive`。

## 优先级：`_N` 后缀

钩子名后面加 `_数字` 可以指定优先级，数字越小越先执行；不加后缀等于 `_10`：

```php
// 先于其他插件执行
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx_1 = __CLASS__ . '::first';
// 默认优先级
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::normal';
// 最后执行
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx_99 = __CLASS__ . '::last';
```

后缀只影响顺序，三个回调注册在同一个钩子 `contentEx` 上。优先级相同的回调按注册顺序执行。

:::warning 优先级相同的回调可能互相覆盖
1.3.0 中，如果某个钩子上已经有优先级数字**更小**的回调，再注册一个与已有回调优先级相同的回调时，新回调会**替换**掉那个已有的回调。例如插件 A 注册了 `contentEx_5`，插件 B 注册了 `contentEx`，插件 C 再注册 `contentEx`，B 的回调就不会再被调用。只用默认优先级（不加后缀）时不会出现这个问题；需要指定优先级时，尽量选一个不常用的数字。
:::

## 三种触发方式

触发钩子的代码决定了回调收到什么参数、返回值有什么作用。[钩子列表](../reference/hooks.md)的“类型”一栏就是这里的分类。

### call：通知

```php
\Typecho\Plugin::factory('Widget\Archive')->call('beforeRender', $this);
```

依次调用所有回调，参数原样传入。`call()` 返回**最后一个**回调的返回值；没有回调时返回 `null`。大多数 `call` 钩子不使用返回值，适合在某件事发生前后执行自己的逻辑，例如 `finishPublish`（文章发布之后）。

```php
public static function beforeRender(\Widget\Archive $archive)
{
    // 每次渲染前台页面前执行
}
```

### filter：过滤

```php
$content = \Typecho\Plugin::factory('Widget\Base\Contents')->filter('contentEx', $content, $this);
```

第一个参数是要过滤的值。每个回调收到当前的值和其余参数，**必须返回**处理后的值，作为下一个回调收到的值；最后一个回调的返回值就是 `filter()` 的结果。没有回调时原样返回。

```php
public static function contentEx(string $content, \Widget\Base\Contents $widget): string
{
    return $content . '<p>（完）</p>';
}
```

回调忘记 `return` 时，过滤结果变成 `null`，页面上的正文会消失。

回调实际收到的参数依次是当前值、其余参数，最后再重复一次当前值。末尾这个重复的参数一般用不到，声明方法时可以省略。

### trigger：接管

```php
\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}
```

`trigger($plugged)` 写在 `call()` 或 `filter()` 前面。只要有回调被执行，`$plugged` 就变成 `true`，Typecho 据此**跳过自己的默认行为**。上例中，只要有插件注册了 `richEditor`，Typecho 就不再输出自带的 Markdown 编辑器。

所以给 trigger 类钩子注册回调，意味着由插件完全接管这件事。例如 `Widget\Upload` 的 `uploadHandle`：注册之后，Typecho 不再保存上传的文件，回调要自己保存并返回文件信息，见[常见插件模式](./patterns.md#external-storage)。

### 已弃用的魔术调用

1.3 之前的代码用“调用一个不存在的方法”来触发钩子：

```php
\Typecho\Plugin::factory('admin/write-page.php')->bottom($page);
```

它等同于 `->call('bottom', $page)`。1.3 中这种写法已弃用，Typecho 自己还有两处在用（`admin/write-page.php` 的 `bottom` 和 `Widget\Backup` 的 `import`），对注册回调的插件没有影响。

## 动态钩子

除了代码中写明名字的钩子，还有三类钩子的名字是在运行时拼出来的。

### call&lt;Method&gt;：给 Widget 加方法

在任何 Widget 对象上调用一个**不存在**的方法 `$widget->xxx()` 时，Typecho 触发该对象所属类上的 `callXxx` 钩子（方法名首字母大写），回调收到 Widget 对象和参数数组。没有回调时，Typecho 输出 `$widget->xxx` 的值。

在模板中，`$this` 是 `Widget\Archive`，所以：

```php
// activate() 中注册
\Typecho\Plugin::factory('Widget\Archive')->callWordCount = __CLASS__ . '::wordCount';
```

```php
// 模板中调用，回调收到 ($this, [])
<?php $this->wordCount(); ?>
```

### \_\_\_&lt;prop&gt;：给 Widget 加属性

读取 Widget 对象上一个不存在的属性 `$widget->xxx` 时（既不是数据库字段，也没有对应的 `___xxx()` 方法），Typecho 触发该对象所属类上的 `___xxx` 钩子（三个下划线），回调收到 Widget 对象，回调的返回值就是属性值。

```php
\Typecho\Plugin::factory('Widget\Archive')->___readingTime = __CLASS__ . '::readingTime';
```

```php
<?php echo $this->readingTime; ?>
```

这两类钩子的句柄是对象实际所属的类：模板中的 `$this` 是 `Widget_Archive`，评论列表中的 `$comments` 是 `Widget_Comments_Archive`，`\Widget\Contents\Post\Recent::alloc()` 得到的对象是 `Widget_Contents_Post_Recent`。

### Widget_Service：异步任务

`\Utils\Helper::requestService('任务名', 参数...)` 会在当前请求结束后，向站点自己发一个请求，在那个请求中触发 `Widget_Service` 上名为“任务名”的钩子。见 [HTTP 请求与异步任务](./http-async.md)。

## 完整示例：阅读时间

下面的插件用到了本页介绍的三种注册方式：一个带优先级的过滤器，以及两个动态钩子。

```php title="usr/plugins/ReadingTime/Plugin.php"
<?php

namespace TypechoPlugin\ReadingTime;

use Typecho\Common;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Archive;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 估算文章的字数和阅读时间
 *
 * @package ReadingTime
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Base\Contents')->contentEx_5 = __CLASS__ . '::prepend';
        \Typecho\Plugin::factory('Widget\Archive')->___readingTime = __CLASS__ . '::readingTime';
        \Typecho\Plugin::factory('Widget\Archive')->callWordCount = __CLASS__ . '::wordCount';
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

    /**
     * 文章页正文前插入阅读时间
     */
    public static function prepend(string $content, Contents $widget): string
    {
        if ($widget instanceof Archive && $widget->is('single') && 'post' == $widget->type) {
            $content = '<p class="reading-time">'
                . _t('全文 %d 字，阅读约需 %d 分钟', self::count($widget), self::readingTime($widget))
                . '</p>' . $content;
        }

        return $content;
    }

    /**
     * $this->readingTime
     */
    public static function readingTime(Archive $archive): int
    {
        return max(1, (int) ceil(self::count($archive) / 400));
    }

    /**
     * $this->wordCount()
     */
    public static function wordCount(Archive $archive, array $args)
    {
        echo self::count($archive);
    }

    private static function count(Contents $widget): int
    {
        return Common::strLen(preg_replace('/\s+/u', '', strip_tags((string) $widget->text)));
    }
}
```

启用后，文章页正文前出现“全文 … 字，阅读约需 … 分钟”。在主题的 `post.php` 中还可以这样使用：

```php title="usr/themes/你的主题/post.php"
<p>字数：<?php $this->wordCount(); ?>，阅读时间：<?php echo $this->readingTime; ?> 分钟</p>
```

插入正文的文字会一起出现在文章页的 `description` 元信息中，因为它取自过滤后的正文。订阅源中的文章不会带上这段文字：订阅源取出文章时 `is('single')` 为假，`prepend()` 不插入。不做这个判断的内容过滤会同时影响订阅源，见[常见插件模式](./patterns.md#content-filter)。

`prepend()` 中用 `is('single')` 加 `type` 判断文章页，而不是 `is('post')`。原因是 Typecho 在生成页面描述时，就已经输出并缓存了文章页的正文。那时页面类型还没有确定为 `post`：`is('post')` 返回 `false`，而 `is('single')` 已经是 `true`。

`count()` 读取的是 `$widget->text`（未经处理的原文），而不是 `$widget->content`：`content` 正是 `contentEx` 正在生成的值，在 `contentEx` 的回调中读取它得到的是空值（PHP 不会为同一个属性递归调用 `__get()`）。

## 在自己的插件中提供钩子

插件也可以提供钩子，让别的插件扩展它。句柄一般用插件类名：

```php
// 通知
\Typecho\Plugin::factory(__CLASS__)->call('afterSave', $message);

// 过滤
$html = \Typecho\Plugin::factory(__CLASS__)->filter('render', $html, $message);

// 接管
$result = \Typecho\Plugin::factory(__CLASS__)->trigger($plugged)->call('send', $message);
if (!$plugged) {
    $result = self::defaultSend($message);
}
```

其他插件注册时写 `\Typecho\Plugin::factory('TypechoPlugin\Guestbook\Plugin')->afterSave = ...`，句柄换算后是 `TypechoPlugin_Guestbook_Plugin`。
