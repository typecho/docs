---
title: Plugin Structure and Lifecycle
sidebar_position: 3
translation: machine
source_commit: fc55a1004f355202effc7d999ebe984ff3ec2cf2
---

# Plugin Structure and Lifecycle

This page explains how Typecho discovers a plugin, how it reads the plugin's information, and what happens on activation, configuration and deactivation.

## Two layouts

Plugins live under `usr/plugins/` and come in two layouts:

| Layout | Entry file | Plugin class | Suited for |
|---|---|---|---|
| Directory | `usr/plugins/Name/Plugin.php` | `\TypechoPlugin\Name\Plugin` | The vast majority of plugins |
| Single file | `usr/plugins/Name.php` | `\TypechoPlugin\Name` | Small plugins with only one file |

`Name` is the plugin name: the admin panel uses it to tell plugins apart, settings are saved as `plugin:Name`, and `Options::alloc()->plugin('Name')` uses it to read them. The directory name (or file name) and the name in the namespace must match exactly, including case. If a directory and a single file with the same name both exist, the directory wins.

In the directory layout, the plugin's other classes are also autoloaded by namespace, so you do not need `require`:

```text
usr/plugins/Guestbook/
├── Plugin.php        \TypechoPlugin\Guestbook\Plugin
├── Action.php        \TypechoPlugin\Guestbook\Action
├── Storage/
│   └── Message.php   \TypechoPlugin\Guestbook\Storage\Message
├── panel.php         后台面板（普通 PHP 文件，见“路由、Action 与后台面板”）
└── lang/             语言文件
```

The rule is: strip the leading `TypechoPlugin\` from the class name, replace `\` with `/`, append `.php`, and look the file up under `usr/plugins/`.

The single-file layout looks like this:

```php title="usr/plugins/Single.php"
<?php

namespace TypechoPlugin;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 单文件插件示例：给每个前台响应加一个响应头
 *
 * @package Single
 * @version 1.0.0
 * @since 1.3.0
 */
class Single implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('index.php')->begin = __CLASS__ . '::begin';
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

    public static function begin()
    {
        header('X-Single: 1');
    }
}
```

The old class naming style `class Name_Plugin implements Typecho_Plugin_Interface` (without a namespace) still works in 1.3; Typecho maps `Name_Plugin` to `\TypechoPlugin\Name\Plugin`. Use the namespace style for new plugins. See [Migrating themes and plugins to 1.3](../migrate-to-1-3.md).

## Header comment {#header}

The information shown in the plugin list comes from the **first** doc comment (`/** ... */`) in the entry file:

```php
/**
 * 插件描述，可以写多行
 * 第二行描述
 *
 * @package Guestbook
 * @author 你的名字
 * @version 1.0.0
 * @since 1.3.0
 * @link https://example.com
 */
```

| Tag | Meaning |
|---|---|
| Non-empty lines before the first `@` | Description; multiple lines are joined when displayed |
| `@package` | Plugin name (for display; may differ from the directory name) |
| `@author` | Author |
| `@version` | Plugin version, for display only |
| `@link` | Author's home page, linked on the author's name |
| `@since` | Minimum Typecho version the plugin requires |

Other tags are ignored. `@dependence`, common in old plugins, is no longer read; put the version requirement in `@since` instead.

### @since check {#since-检查}

The value of `@since` is compared with the current Typecho version using PHP's `version_compare()`. The plugin can be activated only when the current version is **greater than or equal to** `@since`:

- With `@since 1.3.0`, versions 1.3.0 and later can activate it;
- If the version requirement is not met when you click "Activate", the plugin is not activated and the page shows "插件名 无法在此版本的typecho下正常工作" (the plugin cannot work properly under this version of Typecho);
- If an already activated plugin no longer meets the requirement after an upgrade or downgrade, a warning icon appears after its name in the plugin list, but the plugin stays activated;
- Omitting `@since` means no check.

If your plugin uses classes that only exist in 1.3 (for example `\Widget\Options`, `Typecho\Plugin::call()`), you should write `@since 1.3.0`.

### How the admin panel determines which methods a plugin has

Typecho does not execute the plugin code. Instead, it reads the PHP tokens one by one and checks whether the **method bodies** of the four methods `activate`, `deactivate`, `config` and `personalConfig` **are empty**:

- The "Settings" link appears in the plugin list only when the body of `config()` is not empty;
- The plugin's form appears on the "Personal settings" page only when the body of `personalConfig()` is not empty;
- If all four methods are empty, the plugin is treated as "plug and play": it appears directly in the list of activated plugins, labeled "plug and play", with no activate or deactivate button, and `activate()` is never called.

This check only works when the class declaration is written as `implements PluginInterface` (together with `use Typecho\Plugin\PluginInterface;`) or `implements Typecho_Plugin_Interface`. Under PHP 8, 1.3.0 **does not recognize** the form with a backslash:

```php
// 后台会把插件当作“即插即用”，activate() 永远不会被调用
class Plugin implements \Typecho\Plugin\PluginInterface
```

Always `use` the interface first, then write `implements PluginInterface`.

## PluginInterface

The plugin class must implement the four static methods of `Typecho\Plugin\PluginInterface`:

| Method | When it is called | Purpose |
|---|---|---|
| `activate()` | Once, when you click "Activate" in the admin panel | Register hooks; add routes, actions and admin panels; create tables |
| `deactivate()` | Once, when you click "Deactivate" in the admin panel | Undo the route, action, panel and other changes made in `activate()` |
| `config(Form $form)` | On activation, and when the plugin settings page is opened or saved | Add settings to `$form`; see [Plugin settings](./settings.md) |
| `personalConfig(Form $form)` | On activation, and when the "Personal settings" page is opened or saved | Per-user settings |

`activate()` and `deactivate()` can:

- **Return a string**: activation (deactivation) completes as usual, and the admin panel shows this string as the notice instead of the default "插件已经被启用" (plugin activated) or "插件已经被禁用" (plugin deactivated);
- **Throw `Typecho\Plugin\Exception`**: the operation is canceled and the exception message is shown as an error notice. Throwing any other type of exception results in a 500 error page in the admin panel.

```php
public static function activate()
{
    if (!extension_loaded('curl')) {
        throw new \Typecho\Plugin\Exception(_t('此插件需要 PHP 的 curl 扩展'));
    }

    \Typecho\Plugin::factory('Widget\Feedback')->comment = __CLASS__ . '::filter';

    return _t('插件已启用，请到设置页面填写 API 密钥');
}

public static function filter(array $comment): array
{
    return $comment;
}
```

## What happens on activation {#启用时发生了什么}

After you click "Activate", Typecho (`Widget\Plugins\Edit::activate()`) does the following in order:

1. Finds the entry file and reads the header comment;
2. Checks `@since`; if the requirement is not met, it shows a notice and stops;
3. Loads the entry file. If the plugin is already activated, the class does not exist, or there is no `activate()` method, it reports the error "无法启用插件" (unable to activate plugin);
4. Calls `activate()`. The callbacks registered through `Typecho\Plugin::factory()` during the call are recorded;
5. Writes these callbacks to the `plugins` option (JSON); every later request reads the callbacks from there. `activate()` is called only this once;
6. Calls `config()` and `personalConfig()`, and stores the default values of the form items as the options `plugin:Name` and `_plugin:Name` respectively (if the plugin defines `configHandle()` / `personalConfigHandle()`, they handle this instead; see [Plugin settings](./settings.md)).

A few consequences follow:

- After you change the hooks registered in `activate()`, you must **deactivate and reactivate** the plugin for the change to take effect; changes to the body of a callback method take effect immediately.
- Registered callbacks must be storable as JSON, so write them as a string `'类名::方法名'` (`'ClassName::methodName'`) or an array `[类名, '方法名']`, not closures. A callback must be callable at registration time; a misspelled method name causes an error right at activation.
- Only administrators can activate, deactivate and configure plugins.

## What happens on deactivation

After you click "Deactivate", Typecho (`Widget\Plugins\Edit::deactivate()`) does the following in order:

1. Loads the entry file and calls `deactivate()`;
2. Removes all callbacks registered by this plugin from the `plugins` option;
3. **Deletes the option `plugin:Name` and every user's `_plugin:Name`**. In other words, deactivation clears the plugin settings, and they are reset to their defaults when you activate the plugin again.

Changes that Typecho does not undo automatically must be handled by the plugin itself in `deactivate()`:

| Done in `activate()` | Undo in `deactivate()` |
|---|---|
| `Helper::addRoute()` | `Helper::removeRoute()` |
| `Helper::addAction()` | `Helper::removeAction()` |
| `Helper::addMenu()` / `Helper::addPanel()` | `Helper::removeMenu()` / `Helper::removePanel()` |
| Created tables | Drop them if needed, or keep the data and let the user decide |

For these methods, see [Routes, actions and admin panels](./routes-actions.md).

### Plugin files deleted

If a plugin's files are deleted directly while the plugin is activated, the plugin list shows "此插件文件已经损坏或者被不安全移除, 强烈建议你禁用它" (the plugin files are damaged or were removed unsafely; you are strongly advised to deactivate it) along with a "Deactivate" link. Deactivating it then skips `deactivate()` and only removes the callbacks and settings; routes, actions and panels added by the plugin are not undone. So when you uninstall a plugin, deactivate it in the admin panel first, then delete the files.

In code, you can also force a plugin to be deactivated with `\Utils\Helper::removePlugin('Name')`. See [Utils\Helper](../reference/helper.md).
