---
title: 插件结构与生命周期
sidebar_position: 3
---

# 插件结构与生命周期

本页说明 Typecho 如何发现一个插件、如何读取它的信息，以及启用、设置、禁用时分别发生了什么。

## 两种布局

插件放在 `usr/plugins/` 下，有两种写法：

| 布局 | 入口文件 | 插件类 | 适合 |
|---|---|---|---|
| 目录 | `usr/plugins/Name/Plugin.php` | `\TypechoPlugin\Name\Plugin` | 绝大多数插件 |
| 单文件 | `usr/plugins/Name.php` | `\TypechoPlugin\Name` | 只有一个文件的小插件 |

`Name` 就是插件名：后台用它区分插件，设置保存为 `plugin:Name`，`Options::alloc()->plugin('Name')` 也用它读取。目录名（或文件名）、命名空间中的名字要完全一致，包括大小写。同名的目录和单文件同时存在时，目录优先。

目录布局中，插件的其他类也按命名空间自动加载，不需要 `require`：

```text
usr/plugins/Guestbook/
├── Plugin.php        \TypechoPlugin\Guestbook\Plugin
├── Action.php        \TypechoPlugin\Guestbook\Action
├── Storage/
│   └── Message.php   \TypechoPlugin\Guestbook\Storage\Message
├── panel.php         后台面板（普通 PHP 文件，见“路由、Action 与后台面板”）
└── lang/             语言文件
```

规则是：类名去掉开头的 `TypechoPlugin\`，把 `\` 换成 `/`，加上 `.php`，在 `usr/plugins/` 下查找。

单文件布局写法如下：

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

旧版本的类名写法 `class Name_Plugin implements Typecho_Plugin_Interface`（不用命名空间）在 1.3 中仍然有效，Typecho 会把 `Name_Plugin` 对应到 `\TypechoPlugin\Name\Plugin`。新插件请使用命名空间写法，见[主题与插件迁移到 1.3](../migrate-to-1-3.md)。

## 头注释 {#header}

插件列表中显示的信息来自入口文件中的**第一个**文档注释（`/** ... */`）：

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

| 标签 | 含义 |
|---|---|
| 第一个 `@` 之前的非空行 | 描述，多行会连在一起显示 |
| `@package` | 插件名称（显示用，可以与目录名不同） |
| `@author` | 作者 |
| `@version` | 插件版本，只用于显示 |
| `@link` | 作者主页，作者名字上的链接 |
| `@since` | 插件要求的最低 Typecho 版本 |

其他标签会被忽略。旧插件中常见的 `@dependence` 不再被读取，版本要求请写在 `@since` 中。

### @since 检查

`@since` 的值与当前 Typecho 版本用 PHP 的 `version_compare()` 比较，当前版本**大于或等于** `@since` 时才能启用：

- 写 `@since 1.3.0`：1.3.0 及以后的版本可以启用；
- 版本不满足时点击“启用”，插件不会被启用，页面提示“插件名 无法在此版本的typecho下正常工作”；
- 已经启用的插件在升级或降级后不满足要求时，插件列表中名字后面显示一个警告图标，但插件仍保持启用；
- 不写 `@since` 表示不检查。

写插件时用到了 1.3 才有的类（例如 `\Widget\Options`、`Typecho\Plugin::call()`），就应该写 `@since 1.3.0`。

### 后台如何判断插件有哪些方法

Typecho 不执行插件代码，而是逐个读取 PHP 语法单元，判断 `activate`、`deactivate`、`config`、`personalConfig` 四个方法的**方法体是否为空**：

- `config()` 方法体不为空时，插件列表中才显示“设置”链接；
- `personalConfig()` 方法体不为空时，“个人设置”页面才会出现这个插件的表单；
- 四个方法都是空的，插件被视为“即插即用”：它直接出现在“启用的插件”列表中，标着“即插即用”，没有启用和禁用按钮，`activate()` 也不会被调用。

这项检查只在类声明写成 `implements PluginInterface`（配合 `use Typecho\Plugin\PluginInterface;`）或 `implements Typecho_Plugin_Interface` 时才生效。1.3.0 在 PHP 8 下**不认得**带反斜线的写法：

```php
// 后台会把插件当作“即插即用”，activate() 永远不会被调用
class Plugin implements \Typecho\Plugin\PluginInterface
```

请始终先 `use`，再写 `implements PluginInterface`。

## PluginInterface

插件类必须实现 `Typecho\Plugin\PluginInterface` 的四个静态方法：

| 方法 | 何时调用 | 用途 |
|---|---|---|
| `activate()` | 在后台点击“启用”时，调用一次 | 注册钩子，添加路由、Action、后台面板，建数据表 |
| `deactivate()` | 在后台点击“禁用”时，调用一次 | 撤销 `activate()` 中做的路由、Action、面板等改动 |
| `config(Form $form)` | 启用时，以及打开、保存插件设置页面时 | 往 `$form` 中添加设置项，见[插件配置](./settings.md) |
| `personalConfig(Form $form)` | 启用时，以及打开、保存“个人设置”页面时 | 每个用户各自的设置项 |

`activate()` 和 `deactivate()` 可以：

- **返回一个字符串**：启用（禁用）照常完成，后台把这个字符串作为提示显示，代替默认的“插件已经被启用”（“插件已经被禁用”）；
- **抛出 `Typecho\Plugin\Exception`**：操作被取消，异常信息作为错误提示显示。抛出其他类型的异常会导致后台 500 错误页。

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

## 启用时发生了什么

点击“启用”后，Typecho（`Widget\Plugins\Edit::activate()`）依次：

1. 找到入口文件，读取头注释；
2. 检查 `@since`，不满足则提示并停止；
3. 载入入口文件。插件已经启用、类不存在或没有 `activate()` 方法时，报错“无法启用插件”；
4. 调用 `activate()`。期间通过 `Typecho\Plugin::factory()` 注册的回调被记下来；
5. 把这些回调写入选项 `plugins`（JSON），之后的每个请求都从这里读取回调。`activate()` 只调用这一次；
6. 调用 `config()` 和 `personalConfig()`，把表单中各项的默认值分别存为选项 `plugin:Name` 和 `_plugin:Name`（插件定义了 `configHandle()` / `personalConfigHandle()` 时改为交给它们处理，见[插件配置](./settings.md)）。

由此可以得出几点：

- 修改 `activate()` 中注册的钩子后，要**禁用再启用**插件，改动才会生效；只修改回调方法的内容则立即生效。
- 注册的回调要能以 JSON 保存，所以写成字符串 `'类名::方法名'` 或数组 `[类名, '方法名']`，不能用闭包。回调在注册时必须可以调用，写错方法名会在启用时直接报错。
- 只有管理员可以启用、禁用和设置插件。

## 禁用时发生了什么

点击“禁用”后，Typecho（`Widget\Plugins\Edit::deactivate()`）依次：

1. 载入入口文件并调用 `deactivate()`；
2. 从选项 `plugins` 中删除这个插件注册的所有回调；
3. **删除选项 `plugin:Name` 和所有用户的 `_plugin:Name`**，也就是说禁用会清空插件设置，再次启用时恢复为默认值。

Typecho 不会自动撤销的改动，需要插件自己在 `deactivate()` 中处理：

| 在 `activate()` 中做了 | 在 `deactivate()` 中撤销 |
|---|---|
| `Helper::addRoute()` | `Helper::removeRoute()` |
| `Helper::addAction()` | `Helper::removeAction()` |
| `Helper::addMenu()` / `Helper::addPanel()` | `Helper::removeMenu()` / `Helper::removePanel()` |
| 建了数据表 | 视需要删除，或者保留数据由用户决定 |

这些方法见[路由、Action 与后台面板](./routes-actions.md)。

### 插件文件被删除

如果插件在启用状态下被直接删除了文件，插件列表中会显示“此插件文件已经损坏或者被不安全移除, 强烈建议你禁用它”，并提供“禁用”链接。此时禁用会跳过 `deactivate()`，只删除回调和设置；插件添加的路由、Action 和面板不会被撤销。所以卸载插件时，应先在后台禁用，再删除文件。

在代码中也可以用 `\Utils\Helper::removePlugin('Name')` 强制禁用一个插件，见 [Utils\Helper](../reference/helper.md)。
