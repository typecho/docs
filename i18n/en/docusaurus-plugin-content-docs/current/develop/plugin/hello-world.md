---
title: Your First Plugin
sidebar_position: 2
translation: machine
source_commit: fc55a1004f355202effc7d999ebe984ff3ec2cf2
---

# Your First Plugin

Typecho ships with a minimal plugin, `HelloWorld` (`usr/plugins/HelloWorld/Plugin.php`). It does only one thing: it shows a line of text on the right side of the admin top navigation bar, and you can change that text in the plugin settings. This page first walks through the file line by line, then uses it as a model to write your own plugin, `Greeting`, from scratch.

## Understanding HelloWorld

The complete file is only 72 lines:

```php title="usr/plugins/HelloWorld/Plugin.php" showLineNumbers
<?php

namespace TypechoPlugin\HelloWorld;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Hello World
 *
 * @package HelloWorld
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        /** 分类名称 */
        $name = new Text('word', null, 'Hello World', _t('说点什么'));
        $form->addInput($name);
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        echo '<span class="message success">'
            . htmlspecialchars(Options::alloc()->plugin('HelloWorld')->word)
            . '</span>';
    }
}
```

### Line 3: the namespace

```php
namespace TypechoPlugin\HelloWorld;
```

The plugin class must live in the `TypechoPlugin\插件名` namespace, where `插件名` (the plugin name) is the directory name under `usr/plugins/`, here `HelloWorld`, with the same case. Typecho uses this convention to find and autoload the `\TypechoPlugin\HelloWorld\Plugin` class in `usr/plugins/HelloWorld/Plugin.php`. See [Plugin structure and lifecycle](./structure.md).

### Lines 5–8: importing the classes you use

`PluginInterface` is the interface every plugin must implement, `Form` and `Text` build the settings form, and `Options` reads the settings.

After the `use` imports, line 22 can simply say `implements PluginInterface`. **Keep it written this way**: when 1.3.0 reads plugin information, it only recognizes the two forms without a backslash, `implements PluginInterface` and `implements Typecho_Plugin_Interface`. If you write `implements \Typecho\Plugin\PluginInterface`, the admin panel treats the plugin as a "plug and play" plugin and `activate()` is never called. See [Plugin structure and lifecycle](./structure.md#header).

### Lines 10–12: blocking direct access

```php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
```

`usr/plugins/` is inside the web root, so anyone can request `/usr/plugins/HelloWorld/Plugin.php` directly. When Typecho loads the file, the constant `__TYPECHO_ROOT_DIR__` is already defined; on a direct request it is not, and the script exits immediately. Every PHP file in a plugin should start with these three lines.

### Lines 14–21: the header comment

```php
/**
 * Hello World
 *
 * @package HelloWorld
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
```

All the information listed on the "Dashboard → Plugins" admin page comes from the **first** doc comment in the file:

| What you write | Shown as |
|---|---|
| The text before the first `@` | Description (`Hello World`) |
| `@package` | Plugin name |
| `@author` | Author |
| `@version` | Version |
| `@link` | Link on the author's name |
| `@since` | Minimum Typecho version, for example `@since 1.3.0`. HelloWorld omits it, which means no check |

### Lines 27–30: activate(), registering a hook

```php
public static function activate()
{
    \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
}
```

When you click "Activate" in the admin panel, Typecho calls `activate()` once. This line means: when the hook named `navBar` in `admin/menu.php` is triggered, call `\TypechoPlugin\HelloWorld\Plugin::render()`.

Line 10 of `admin/menu.php` triggers this hook:

```php title="admin/menu.php"
<?php \Typecho\Plugin::factory('admin/menu.php')->call('navBar'); ?>
```

It sits on the right side of the admin top navigation bar, just before the link with the current user's name, so whatever `render()` outputs appears in this position on every admin page.

Two things to note:

- `activate()` **runs only once, on activation**. Typecho saves the callbacks registered here to the database (the `plugins` option), and every later request reads them from the database without calling `activate()` again. So after you change the registration code in `activate()`, you must deactivate and then reactivate the plugin for the change to take effect.
- Write callbacks as a string `'类名::方法名'` (`'ClassName::methodName'`) or an array `[类名, '方法名']`, not as closures: callbacks are saved to the database as JSON.

For everything about hooks, see [Using hooks](./hooks.md). For all hooks Typecho provides, see the [Hook list](../reference/hooks.md).

### Lines 35–37: deactivate()

Called when the plugin is deactivated. HelloWorld has nothing to clean up, so the method is empty. Typecho automatically removes the registered callbacks and the plugin settings on deactivation, so you do not need to handle them here.

### Lines 44–49: config(), the settings form

```php
public static function config(Form $form)
{
    /** 分类名称 */
    $name = new Text('word', null, 'Hello World', _t('说点什么'));
    $form->addInput($name);
}
```

When `config()` adds form elements to `$form`, the admin panel shows a "Settings" link for the plugin in the plugin list. This adds a text box:

- `'word'`: the name of the setting, read later with `->word`;
- `null`: the list of options, not needed for a text box;
- `'Hello World'`: the default value. When the plugin is activated, Typecho stores the default values of all settings in the database;
- `_t('说点什么')`: the label shown in front of the text box.

For the available form elements, see [Form elements](../reference/form-elements.md).

### Lines 56–58: personalConfig()

Per-user settings, shown on the "Personal settings" page. HelloWorld does not need any, so the method is empty. See [Plugin settings](./settings.md).

### Lines 66–71: render(), the hook callback

```php
public static function render()
{
    echo '<span class="message success">'
        . htmlspecialchars(Options::alloc()->plugin('HelloWorld')->word)
        . '</span>';
}
```

`Options::alloc()->plugin('HelloWorld')` gets the plugin's settings, and `->word` is the value of the `word` item in the settings form. The value is escaped with `htmlspecialchars()` before output so that HTML in the setting is not executed. `message success` is a built-in admin style (a green notice bar).

## Writing Greeting

Use HelloWorld as a model to write a plugin `Greeting`: it shows a greeting in the admin navigation bar and also outputs the same text at the bottom of every front-end page.

### 1. Create the directory and file

Create a directory `Greeting` under `usr/plugins/`, and create `Plugin.php` in it:

```text
usr/plugins/Greeting/
└── Plugin.php
```

### 2. Write the code

```php title="usr/plugins/Greeting/Plugin.php"
<?php

namespace TypechoPlugin\Greeting;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Shows a greeting in the admin navigation bar and the front-end footer
 *
 * @package Greeting
 * @author Your Name
 * @version 1.0.0
 * @since 1.3.0
 * @link https://example.com
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::navBar';
        \Typecho\Plugin::factory('Widget\Archive')->footer = __CLASS__ . '::footer';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $word = new Text('word', null, '你好，Typecho', _t('问候语'));
        $form->addInput($word);
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function navBar()
    {
        echo '<span class="message success">' . htmlspecialchars(self::word()) . '</span>';
    }

    public static function footer()
    {
        echo '<p class="greeting">' . htmlspecialchars(self::word()) . '</p>';
    }

    private static function word(): string
    {
        return (string) Options::alloc()->plugin('Greeting')->word;
    }
}
```

Compared with HelloWorld, there are three changes:

- The namespace and `@package` are both changed to `Greeting`, matching the directory name;
- `@since 1.3.0` is added: Typecho versions below 1.3.0 refuse to activate it;
- One more hook is registered: `footer` of `Widget\Archive`. It is triggered when the theme calls `$this->footer()` in `footer.php`, so the greeting appears at the bottom of front-end pages (the theme must call `$this->footer()`; both bundled themes do).

### 3. Activate and check

1. Go to "Dashboard → Plugins" in the admin panel. `Greeting` appears in the list of deactivated plugins, showing the description, version and author from the header comment. Click "Activate".
2. The page shows the notice "插件已经被启用" (plugin activated), and `Greeting` moves to the list of activated plugins, followed by two links, "Settings" and "Deactivate".
3. A green "你好，Typecho" appears on the right side of the admin top navigation bar.
4. Open the site's home page. The same text appears at the bottom of the page.
5. Click "Settings", change the greeting to some other text and save. The page shows the notice "插件设置已经保存" (plugin settings saved). Both the navigation bar and the front-end footer now show the new text.

If the navigation bar does not change after activation, check in order: whether the namespace is `TypechoPlugin\Greeting`, whether the directory name is `Greeting`, and whether `implements` is followed by `PluginInterface`. After changing `activate()`, deactivate and reactivate the plugin.

## Next steps

- [Plugin structure and lifecycle](./structure.md): single-file plugins, the full rules for the header comment, and what Typecho does on activation and deactivation.
- [Using hooks](./hooks.md): hook naming, priority and the three ways hooks are called.
- [Plugin settings](./settings.md): validating the settings form, custom saving, personal settings.
