---
title: Plugin Settings
sidebar_position: 5
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Plugin Settings

A plugin can have two kinds of settings:

- **Plugin settings**: one copy for the whole site, changed by an administrator under "Console → Plugins → Settings", defined by `config()`;
- **Personal settings**: one copy per user, shown at the bottom of the "Console → Profile" page, defined by `personalConfig()`.

Both are described with form elements; Typecho displays the form, validates the input and saves it.

## config(Form $form)

In `config()`, add form elements to `$form`. The name of each element is one setting:

```php
public static function config(Form $form)
{
    $word = new Text('word', null, 'Hello', _t('Greeting'));
    $word->addRule('required', _t('The greeting cannot be empty'));
    $form->addInput($word);

    $position = new Radio('position', [
        'navBar' => _t('Admin navigation bar'),
        'footer' => _t('Front-end footer'),
        'both'   => _t('Both'),
    ], 'both', _t('Display location'));
    $form->addInput($position);
}
```

- The third argument is the default value. **When the plugin is activated**, Typecho saves the default values of all settings, so your plugin code can always read every setting;
- Validation added with `addRule()` runs on save. If it fails, the form is not saved and the message is shown below the field;
- For the available elements and validation rules, see [Form elements](../reference/form-elements.md).

The "Settings" link appears in the plugin list only when the body of `config()` is not empty. The settings page is at `admin/options-plugin.php?config=<plugin name>`.

## Reading settings

```php
$config = \Widget\Options::alloc()->plugin('Greeting');

echo $config->word;       // read one setting
$all = $config->toArray(); // array of all settings
```

`plugin()` returns a `Typecho\Config` object. A setting that does not exist reads as `null`. The value of a Checkbox is an array; all other elements give strings.

When the plugin has never saved any settings (no elements are added in `config()`, or the plugin is not activated), `plugin()` throws `Typecho\Plugin\Exception` ("the configuration of plugin Greeting was not found"). Catch the exception wherever this can happen.

## Where settings are stored

Settings are stored as JSON in the `options` table:

| Setting | `name` | `user` |
|---|---|---|
| Plugin settings | `plugin:<plugin name>` | `0` |
| Default values of personal settings | `_plugin:<plugin name>` | `0` |
| Personal settings saved by a user | `_plugin:<plugin name>` | User ID |

For example, after you activate HelloWorld, the `options` table has a new row `plugin:HelloWorld` with the value `{"word":"Hello World"}`.

**Deactivating the plugin deletes these rows** (including every user's personal settings). When you activate it again, the settings are reset to their defaults.

## Checking before save: configCheck()

A plugin class can define an optional static method `configCheck(array $settings)`. It is called when an administrator saves the settings (not when the plugin is enabled). If it returns a non-empty string, that string is shown as the message instead of "Plugin settings saved":

```php
public static function configCheck(array $settings)
{
    if (mb_strlen($settings['word']) > 20) {
        return _t('Settings saved. The greeting is long and may be cut off in the admin navigation bar');
    }
}
```

`configCheck()` **cannot prevent saving**: whatever it returns, the settings are saved as usual. To reject an input, use `addRule()` on the form element.

## Saving it yourself: configHandle()

When a plugin class defines a static method `configHandle(array $settings, bool $isInit)`, Typecho no longer saves the plugin settings automatically. Instead, it passes the form values to this method:

- It is called once when the plugin is activated, with `$isInit` set to `true` and `$settings` containing the default values;
- It is called when an administrator saves the settings, with `$isInit` set to `false` and `$settings` containing the submitted values (already validated).

This is useful for transforming data before saving, or for storing the settings somewhere else. If you still want to save them to `plugin:<plugin name>` afterwards, call `\Utils\Helper::configPlugin()`:

```php
public static function configHandle(array $settings, bool $isInit)
{
    $settings['word'] = trim($settings['word']);
    \Utils\Helper::configPlugin('Greeting', $settings);
}
```

`Helper::configPlugin($pluginName, $settings, $isPersonal = false)` **merges** `$settings` into the existing settings, and creates a new row if there are none. When `$settings` is an empty array, it does nothing.

## Personal settings: personalConfig()

`personalConfig(Form $form)` is written the same way as `config()`. It can **return a user group name**; only users in that group or above can see and save these settings. If it returns nothing, the group is `subscriber` (all logged-in users):

```php
public static function personalConfig(Form $form)
{
    $nickname = new Text('nickname', null, '', _t('How the navigation bar addresses you'));
    $form->addInput($nickname);

    return 'contributor';
}
```

The user groups from lowest to highest are `subscriber`, `contributor`, `editor`, `administrator`. See the [permission table](../../guide/content/users.md#permission-table).

To read the personal settings of the currently logged-in user:

```php
$nickname = \Widget\Options::alloc()->personalPlugin('Greeting')->nickname;
```

If the user has saved their own settings, you get their values; otherwise you get the defaults saved when the plugin was activated. Visitors who are not logged in also get the defaults.

As with `plugin()`, `personalPlugin()` throws `Typecho\Plugin\Exception` when there are no personal settings records at all. A typical case: the plugin adds `personalConfig()` only in a new version, and the user overwrites the files without re-enabling the plugin. When you read the settings in a hook that runs on every admin page (such as `navBar`), you must catch this exception; otherwise every admin page returns a 500 error, including the page where you would disable the plugin.

Corresponding to `configHandle()`, you can define `personalConfigHandle(array $settings, bool $isInit)` to save personal settings yourself: it is called once with the defaults when the plugin is enabled (`$isInit` is `true`), and when a user saves their personal settings (`$isInit` is `false`).

`Helper::configPlugin('PluginName', $settings, true)` writes personal settings. It merges `$settings` into **every** existing `_plugin:<plugin name>` row, which means the personal settings of all users are changed.

## Removing a setting when upgrading a plugin {#remove-setting-on-upgrade}

In 1.3.0, opening the plugin settings page fills every saved setting back into the form. If a new version of the plugin removes a setting from `config()` while the database still holds it, the settings page returns a 500 error.

`Helper::configPlugin()` only merges and never deletes existing keys, so you have two options when removing a setting:

- Keep the setting but switch it to a `Hidden` element, so it is no longer shown on the page (recommended);
- Tell users in the upgrade notes to deactivate and then reactivate the plugin; deactivating clears the saved settings.

```php
$form->addInput(new \Typecho\Widget\Helper\Form\Element\Hidden('oldKey'));
```

## Complete example

Building on `Greeting` from [Your first plugin](./hello-world.md), with everything on this page added:

```php title="usr/plugins/Greeting/Plugin.php"
<?php

namespace TypechoPlugin\Greeting;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Text;
use Utils\Helper;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Shows a greeting in the admin navigation bar and the front-end footer
 *
 * @package Greeting
 * @author Your Name
 * @version 1.1.0
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
        $word = new Text('word', null, 'Hello', _t('Greeting'));
        $word->addRule('required', _t('The greeting cannot be empty'));
        $form->addInput($word);

        $position = new Radio('position', [
            'navBar' => _t('Admin navigation bar'),
            'footer' => _t('Front-end footer'),
            'both'   => _t('Both'),
        ], 'both', _t('Display location'));
        $form->addInput($position);
    }

    public static function configCheck(array $settings)
    {
        if (mb_strlen($settings['word']) > 20) {
            return _t('Settings saved. The greeting is long and may be cut off in the admin navigation bar');
        }
    }

    public static function configHandle(array $settings, bool $isInit)
    {
        $settings['word'] = trim($settings['word']);
        Helper::configPlugin('Greeting', $settings);
    }

    public static function personalConfig(Form $form)
    {
        $nickname = new Text('nickname', null, '', _t('How the navigation bar addresses you'));
        $form->addInput($nickname);

        return 'contributor';
    }

    public static function navBar()
    {
        $config = Options::alloc()->plugin('Greeting');
        if ($config->position == 'footer') {
            return;
        }

        $text = $config->word;
        try {
            $nickname = Options::alloc()->personalPlugin('Greeting')->nickname;
        } catch (\Typecho\Plugin\Exception $e) {
            $nickname = null;
        }
        if (!empty($nickname)) {
            $text .= ', ' . $nickname;
        }

        echo '<span class="message success">' . htmlspecialchars($text) . '</span>';
    }

    public static function footer()
    {
        $config = Options::alloc()->plugin('Greeting');
        if ($config->position != 'navBar') {
            echo '<p class="greeting">' . htmlspecialchars($config->word) . '</p>';
        }
    }
}
```

For the full list of elements such as `Radio` and `Hidden`, see [Form elements](../reference/form-elements.md).
