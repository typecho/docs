---
title: 插件配置
sidebar_position: 5
---

# 插件配置

插件可以有两种设置：

- **插件设置**：全站一份，由管理员在“控制台 → 插件 → 设置”中修改，由 `config()` 定义；
- **个人设置**：每个用户一份，显示在“控制台 → 个人设置”页面底部，由 `personalConfig()` 定义。

两者都用表单元素描述，Typecho 负责显示表单、校验输入和保存。

## config(Form $form)

在 `config()` 中往 `$form` 中添加表单元素，每个元素的名字就是一个设置项：

```php
public static function config(Form $form)
{
    $word = new Text('word', null, '你好', _t('问候语'));
    $word->addRule('required', _t('问候语不能为空'));
    $form->addInput($word);

    $position = new Radio('position', [
        'navBar' => _t('后台导航栏'),
        'footer' => _t('前台页脚'),
        'both'   => _t('两处都显示'),
    ], 'both', _t('显示位置'));
    $form->addInput($position);
}
```

- 第三个参数是默认值。**启用插件时**，Typecho 把所有设置项的默认值保存下来，所以插件代码中总能读到每一项；
- `addRule()` 添加的校验在保存时执行，不通过时表单不会保存，提示显示在字段下方；
- 可用的元素和校验规则见[表单元素](../reference/form-elements.md)。

`config()` 的方法体不为空时，插件列表中才会出现“设置”链接，设置页面的地址是 `admin/options-plugin.php?config=插件名`。

## 读取设置

```php
$config = \Widget\Options::alloc()->plugin('Greeting');

echo $config->word;       // 读取一项
$all = $config->toArray(); // 全部设置项组成的数组
```

`plugin()` 返回一个 `Typecho\Config` 对象，不存在的设置项读作 `null`。Checkbox 的值是数组，其余元素是字符串。

插件没有保存过任何设置时（`config()` 中没有添加元素，或插件未启用），`plugin()` 抛出 `Typecho\Plugin\Exception`（“插件Greeting的配置信息没有找到”）。在可能遇到这种情况的地方要捕获异常。

## 存储位置

设置以 JSON 保存在 `options` 表中：

| 设置 | `name` | `user` |
|---|---|---|
| 插件设置 | `plugin:插件名` | `0` |
| 个人设置的默认值 | `_plugin:插件名` | `0` |
| 某个用户保存的个人设置 | `_plugin:插件名` | 用户 ID |

例如启用 HelloWorld 后，`options` 表中多了一行 `plugin:HelloWorld`，值为 `{"word":"Hello World"}`。

**禁用插件会删除这些行**（包括所有用户的个人设置），再次启用时恢复为默认值。

## 保存前检查：configCheck()

插件类中可以定义一个可选的静态方法 `configCheck(array $settings)`，在管理员保存设置时调用（启用时不调用）。它返回一个非空字符串时，这个字符串会作为提示显示，代替“插件设置已经保存”：

```php
public static function configCheck(array $settings)
{
    if (mb_strlen($settings['word']) > 20) {
        return _t('设置已经保存。问候语较长，在后台导航栏中可能显示不全');
    }
}
```

`configCheck()` **不能阻止保存**：无论它返回什么，设置都会照常保存。需要拒绝某个输入时，用表单元素的 `addRule()`。

## 自己保存：configHandle()

插件类中定义了静态方法 `configHandle(array $settings, bool $isInit)` 时，Typecho 不再自动保存插件设置，而是把表单的值交给它：

- 启用插件时调用一次，`$isInit` 为 `true`，`$settings` 是各项的默认值；
- 管理员保存设置时调用，`$isInit` 为 `false`，`$settings` 是提交的值（已经通过校验）。

适合在保存前转换数据，或者把设置存到别处。处理完后仍要保存到 `plugin:插件名` 的话，调用 `\Utils\Helper::configPlugin()`：

```php
public static function configHandle(array $settings, bool $isInit)
{
    $settings['word'] = trim($settings['word']);
    \Utils\Helper::configPlugin('Greeting', $settings);
}
```

`Helper::configPlugin($pluginName, $settings, $isPersonal = false)` 把 `$settings` **合并**到已有的设置中，没有设置时新建一行；`$settings` 为空数组时什么也不做。

## 个人设置：personalConfig()

`personalConfig(Form $form)` 的写法与 `config()` 相同。它可以**返回一个用户组名**，只有该组及以上的用户才能看到和保存这些设置；不返回时为 `subscriber`（所有登录用户）：

```php
public static function personalConfig(Form $form)
{
    $nickname = new Text('nickname', null, '', _t('导航栏中对你的称呼'));
    $form->addInput($nickname);

    return 'contributor';
}
```

用户组从低到高依次是 `subscriber`、`contributor`、`editor`、`administrator`，见[权限分配表](../../guide/content/users.md#permission-table)。

读取当前登录用户的个人设置：

```php
$nickname = \Widget\Options::alloc()->personalPlugin('Greeting')->nickname;
```

用户自己保存过时得到他的值，否则得到启用插件时保存的默认值。未登录的访客读到的也是默认值。

与 `plugin()` 一样，没有任何个人设置记录时 `personalPlugin()` 抛出 `Typecho\Plugin\Exception`。典型的情况是：插件在新版本中才加入 `personalConfig()`，用户直接覆盖了文件而没有重新启用插件。在后台每个页面都会执行的钩子（如 `navBar`）中读取设置时，一定要捕获这个异常，否则所有后台页面都会出现 500 错误，连禁用插件的页面也打不开。

与 `configHandle()` 对应，可以定义 `personalConfigHandle(array $settings, bool $isInit)` 自己保存个人设置：启用插件时以默认值调用一次（`$isInit` 为 `true`），用户保存个人设置时调用（`$isInit` 为 `false`）。

`Helper::configPlugin('插件名', $settings, true)` 写入的是个人设置，它会把 `$settings` 合并到**每一个**已经存在的 `_plugin:插件名` 行中，即所有用户的个人设置都会被修改。

## 插件升级时删除设置项

1.3.0 打开插件设置页面时，会把已保存的每一项填回表单。如果新版本的插件从 `config()` 中删除了某个设置项，而数据库中还保存着它，设置页面会出现 500 错误。

`Helper::configPlugin()` 只会合并、不会删除已有的键，所以删除设置项时有两种做法：

- 保留这个设置项，改用 `Hidden` 元素，页面上不再显示它（推荐）；
- 在升级说明中提示用户先禁用、再启用插件，禁用会清空已保存的设置。

```php
$form->addInput(new \Typecho\Widget\Helper\Form\Element\Hidden('oldKey'));
```

## 完整示例

在[第一个插件](./hello-world.md)的 `Greeting` 基础上加入本页的全部内容：

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
 * 在后台导航栏和前台页脚显示一句问候语
 *
 * @package Greeting
 * @author 你的名字
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
        $word = new Text('word', null, '你好', _t('问候语'));
        $word->addRule('required', _t('问候语不能为空'));
        $form->addInput($word);

        $position = new Radio('position', [
            'navBar' => _t('后台导航栏'),
            'footer' => _t('前台页脚'),
            'both'   => _t('两处都显示'),
        ], 'both', _t('显示位置'));
        $form->addInput($position);
    }

    public static function configCheck(array $settings)
    {
        if (mb_strlen($settings['word']) > 20) {
            return _t('设置已经保存。问候语较长，在后台导航栏中可能显示不全');
        }
    }

    public static function configHandle(array $settings, bool $isInit)
    {
        $settings['word'] = trim($settings['word']);
        Helper::configPlugin('Greeting', $settings);
    }

    public static function personalConfig(Form $form)
    {
        $nickname = new Text('nickname', null, '', _t('导航栏中对你的称呼'));
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
            $text .= '，' . $nickname;
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

`Radio` 和 `Hidden` 等元素的完整列表见[表单元素](../reference/form-elements.md)。
