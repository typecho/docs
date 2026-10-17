---
title: Using Hooks
sidebar_position: 4
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Using Hooks

A hook is a place reserved in Typecho's code: when execution reaches it, Typecho calls the callbacks that plugins registered at that place, one after another. Almost every plugin feature is implemented through hooks.

A hook is identified by two parts: the **handle** (the file or class where the hook lives) and the **hook name**. For example, `navBar` in `admin/menu.php`, or `footer` in `Widget\Archive`. For all hooks Typecho provides, see the [Hook list](../reference/hooks.md).

## Registering callbacks

In the plugin's `activate()`, use `Typecho\Plugin::factory($handle)` (where `$handle` is the handle) to get the handle object, then assign the callback to the hook name:

```php
public static function activate()
{
    \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::navBar';
    \Typecho\Plugin::factory('Widget\Archive')->footer = [__CLASS__, 'footer'];
}
```

- Write the callback as a string `'ClassName::methodName'` or an array `[ClassName, 'methodName']`, pointing to a `public static` method. Callbacks are saved to the database as JSON, so you cannot use closures;
- Registration runs once when the plugin is activated and is then saved. After you change the registration code, deactivate and reactivate the plugin. See [Plugin structure and lifecycle](./structure.md#on-activation);
- You can register multiple callbacks on the same hook, and callbacks from different plugins can be registered on the same hook too; they run in order of priority.

## Handle names {#handle-names}

There are two kinds of handles:

- **File handles**: for hooks in admin templates and entry files, the handle is the file's relative path, such as `admin/menu.php`, `admin/write-post.php`, `index.php`;
- **Class handles**: for hooks in widget classes, the handle is the class name.

You can write the class name as `Widget\Archive`, `\Widget\Archive`, `Widget_Archive` or `\Widget\Archive::class`. Typecho normalizes it as follows:

1. If the class has a legacy class name alias (see [Legacy class name mapping](../reference/class-aliases.md)), it is replaced with the alias;
2. Otherwise, `\` is replaced with `_` and any leading `_` is removed.

So the following three lines register on the same hook:

```php
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::filter';
\Typecho\Plugin::factory(\Widget\Base\Contents::class)->contentEx = __CLASS__ . '::filter';
\Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = __CLASS__ . '::filter';
```

Their handle is `Widget_Abstract_Contents` in all cases (the legacy class name of `Widget\Base\Contents`). Likewise, the handle of `Widget\Contents\Page\Rows` is `Widget_Contents_Page_List`, and the handle of `Widget\Metas\Category\Rows` is `Widget_Metas_Category_List`. The [Hook list](../reference/hooks.md) shows the normalized names.

### Which class the handle is

Typecho triggers hooks in classes in two ways, which determine the class the hook is attached to:

- When the class name is written out, as in `Contents::pluginHandle()` or `Plugin::factory(Upload::class)`, the handle is always that class. For example, `contentEx` is written in `Widget\Base\Contents` and triggered with `Contents::pluginHandle()`, so every content object that outputs its body, whether in the home page post list, on a post page or in a recent posts list, triggers `contentEx` of `Widget_Abstract_Contents`;
- With `self::pluginHandle()`, the handle is **the class the current object actually belongs to**. For example, the handle of `header` in `Widget\Archive` is `Widget_Archive`.

## Priority: the `_N` suffix

Append `_<number>` (`_` plus a number) to the hook name to set the priority. Smaller numbers run first; no suffix is the same as `_10`:

```php
// runs before other plugins
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx_1 = __CLASS__ . '::first';
// default priority
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::normal';
// runs last
\Typecho\Plugin::factory('Widget\Base\Contents')->contentEx_99 = __CLASS__ . '::last';
```

The suffix only affects the order; all three callbacks are registered on the same hook, `contentEx`. Callbacks with the same priority run in the order they were registered.

:::warning Callbacks with the same priority may overwrite each other
In 1.3.0, if a hook already has a callback with a **smaller** priority number, registering a new callback with the same priority as an existing callback **replaces** that existing callback. For example, if plugin A registers `contentEx_5`, plugin B registers `contentEx`, and then plugin C also registers `contentEx`, B's callback is no longer called. This does not happen if you only use the default priority (no suffix); when you need to set a priority, pick a number that is rarely used.
:::

## Three ways hooks are triggered

The code that triggers a hook determines what parameters the callbacks receive and what their return values do. The "Type" column of the [Hook list](../reference/hooks.md) uses these categories.

### call: notification

```php
\Typecho\Plugin::factory('Widget\Archive')->call('beforeRender', $this);
```

Calls all callbacks in turn, passing the parameters as they are. `call()` returns the return value of the **last** callback; with no callbacks it returns `null`. Most `call` hooks do not use the return value. They are suited to doing something before or after an event, for example `finishPublish` (after a post is published).

```php
public static function beforeRender(\Widget\Archive $archive)
{
    // runs before every front-end page is rendered
}
```

### filter: filtering

```php
$content = \Typecho\Plugin::factory('Widget\Base\Contents')->filter('contentEx', $content, $this);
```

The first parameter is the value to filter. Each callback receives the current value and the remaining parameters, and **must return** the processed value, which becomes the value the next callback receives. The return value of the last callback is the result of `filter()`. With no callbacks, the value is returned unchanged.

```php
public static function contentEx(string $content, \Widget\Base\Contents $widget): string
{
    return $content . '<p>(The end)</p>';
}
```

If a callback forgets to `return`, the filter result becomes `null` and the body disappears from the page.

The parameters a callback actually receives are "the current value, the remaining parameters, the current value". You will rarely need the extra last parameter, so you can leave it out of the method declaration.

### trigger: taking over

```php
\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}
```

`trigger($plugged)` goes before `call()` or `filter()`. As soon as any callback runs, `$plugged` becomes `true`, and Typecho uses it to **skip its own default behavior**. In the example above, as long as some plugin has registered `richEditor`, Typecho no longer outputs its built-in Markdown editor.

So registering a callback on a trigger hook means the plugin takes over that job entirely. For example, with `uploadHandle` of `Widget\Upload`: once you register it, Typecho no longer saves uploaded files, and the callback must save the file itself and return the file information. See [Common plugin patterns](./patterns.md#external-storage).

### Deprecated magic calls

Code before 1.3 triggered hooks by "calling a method that does not exist":

```php
\Typecho\Plugin::factory('admin/write-page.php')->bottom($page);
```

This is equivalent to `->call('bottom', $page)`. This style is deprecated in 1.3. Typecho itself still uses it in two places (`bottom` in `admin/write-page.php` and `import` in `Widget\Backup`); this has no effect on plugins that register callbacks.

## Dynamic hooks {#dynamic-hooks}

Besides hooks whose names are written in the code, there are three kinds of hooks whose names are built at runtime.

### call&lt;Method&gt;: adding methods to a widget

When you call a method `$widget->xxx()` that **does not exist** on any widget object, Typecho triggers the `callXxx` hook (method name with its first letter capitalized) on the class the object belongs to. The callback receives the widget object and an array of arguments. With no callbacks, Typecho outputs the value of `$widget->xxx`.

In templates, `$this` is `Widget\Archive`, so:

```php
// registered in activate()
\Typecho\Plugin::factory('Widget\Archive')->callWordCount = __CLASS__ . '::wordCount';
```

```php
// called in a template; the callback receives ($this, [])
<?php $this->wordCount(); ?>
```

### \_\_\_&lt;prop&gt;: adding properties to a widget

When you read a property `$widget->xxx` that does not exist on a widget object (it is neither a database field nor backed by a `___xxx()` method), Typecho triggers the `___xxx` hook (three underscores) on the class the object belongs to. The callback receives the widget object, and its return value is the property value.

```php
\Typecho\Plugin::factory('Widget\Archive')->___readingTime = __CLASS__ . '::readingTime';
```

```php
<?php echo $this->readingTime; ?>
```

The handle of these two kinds of hooks is the class the object actually belongs to: `$this` in a template is `Widget_Archive`, `$comments` in a comment list is `Widget_Comments_Archive`, and the object returned by `\Widget\Contents\Post\Recent::alloc()` is `Widget_Contents_Post_Recent`.

### Widget_Service: async tasks

`\Utils\Helper::requestService('taskName', ...$params)` (task name, parameters) sends a request to the site itself after the current request ends, and in that request triggers the hook on `Widget_Service` named after the task. See [HTTP requests and async tasks](./http-async.md).

## Complete example: reading time

The following plugin uses the three registration styles introduced on this page: a filter with a priority, and two dynamic hooks.

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
 * Estimates the character count and reading time of a post
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
     * Inserts the reading time before the post body
     */
    public static function prepend(string $content, Contents $widget): string
    {
        if ($widget instanceof Archive && $widget->is('single') && 'post' == $widget->type) {
            $content = '<p class="reading-time">'
                . _t('%d characters, about %d minutes to read', self::count($widget), self::readingTime($widget))
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

After activation, "… characters, about … minutes to read" appears before the body on post pages. You can also use it in your theme's `post.php` like this:

```php title="usr/themes/your-theme/post.php"
<p>Characters: <?php $this->wordCount(); ?>, reading time: <?php echo $this->readingTime; ?> min</p>
```

The text inserted into the body also appears in the post page's `description` meta tag, because it is taken from the filtered body. Posts in feeds do not carry this text: when a feed fetches posts, `is('single')` is false and `prepend()` inserts nothing. A content filter without this check affects feeds as well; see [Common plugin patterns](./patterns.md#content-filter).

`prepend()` detects post pages with `is('single')` plus a `type` check rather than `is('post')`: the body of a post page is already output and cached when Typecho generates the page description, and at that point the page type has not yet been set to `post`, so `is('post')` returns `false` while `is('single')` is already `true`.

`count()` reads `$widget->text` (the raw, unprocessed text) rather than `$widget->content`: `content` is exactly the value `contentEx` is producing, so reading it inside a `contentEx` callback gives an empty value (PHP does not call `__get()` recursively for the same property).

## Providing hooks in your own plugin

A plugin can also provide hooks so that other plugins can extend it. The handle is usually the plugin's class name:

```php
// notify
\Typecho\Plugin::factory(__CLASS__)->call('afterSave', $message);

// filter
$html = \Typecho\Plugin::factory(__CLASS__)->filter('render', $html, $message);

// take over
$result = \Typecho\Plugin::factory(__CLASS__)->trigger($plugged)->call('send', $message);
if (!$plugged) {
    $result = self::defaultSend($message);
}
```

Other plugins register with `\Typecho\Plugin::factory('TypechoPlugin\Guestbook\Plugin')->afterSave = ...`; after normalization, the handle is `TypechoPlugin_Guestbook_Plugin`.
