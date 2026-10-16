---
title: 第一个插件
sidebar_position: 2
---

# 第一个插件

Typecho 自带一个最小的插件 `HelloWorld`（`usr/plugins/HelloWorld/Plugin.php`）。它只做一件事：在后台顶部导航栏右侧显示一句话，这句话可以在插件设置中修改。本页先逐行读懂这个文件，再照着它从零写一个自己的插件 `Greeting`。

## 读懂 HelloWorld

完整的文件只有 72 行：

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

### 第 3 行：命名空间

```php
namespace TypechoPlugin\HelloWorld;
```

插件类必须放在 `TypechoPlugin\插件名` 命名空间下。`插件名` 就是 `usr/plugins/` 下的目录名 `HelloWorld`，大小写要一致。Typecho 按这个约定找到并自动加载 `usr/plugins/HelloWorld/Plugin.php` 中的 `\TypechoPlugin\HelloWorld\Plugin` 类，详见[插件结构与生命周期](./structure.md)。

### 第 5–8 行：导入用到的类

`PluginInterface` 是每个插件都要实现的接口，`Form` 和 `Text` 用来构造设置表单，`Options` 用来读取设置。

`use` 导入后，第 22 行可以直接写 `implements PluginInterface`。**请保持这种写法**：1.3.0 读取插件信息时，只认得 `implements PluginInterface` 或 `implements Typecho_Plugin_Interface` 这两种不带反斜线的写法，写成 `implements \Typecho\Plugin\PluginInterface` 时，后台会把插件当作“即插即用”插件，`activate()` 永远不会被调用，见[插件结构与生命周期](./structure.md#header)。

### 第 10–12 行：防止直接访问

```php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
```

`usr/plugins/` 在网站目录中，任何人都可以直接请求 `/usr/plugins/HelloWorld/Plugin.php`。由 Typecho 载入时常量 `__TYPECHO_ROOT_DIR__` 已经定义；直接请求时没有定义，脚本立即退出。插件中的每个 PHP 文件都应该以这三行开头。

### 第 14–21 行：头注释

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

后台“控制台 → 插件”页面列出的信息全部来自文件中的**第一个**文档注释：

| 写法 | 显示为 |
|---|---|
| 第一个 `@` 之前的文字 | 描述（`Hello World`） |
| `@package` | 插件名称 |
| `@author` | 作者 |
| `@version` | 版本 |
| `@link` | 作者名字上的链接 |
| `@since` | 最低 Typecho 版本，例如 `@since 1.3.0`。HelloWorld 没有写，表示不检查 |

### 第 27–30 行：activate()，注册钩子

```php
public static function activate()
{
    \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
}
```

在后台点击“启用”时，Typecho 调用一次 `activate()`。这一行的意思是：当 `admin/menu.php` 中名为 `navBar` 的钩子被触发时，调用 `\TypechoPlugin\HelloWorld\Plugin::render()`。

`admin/menu.php` 第 10 行触发了这个钩子：

```php title="admin/menu.php"
<?php \Typecho\Plugin::factory('admin/menu.php')->call('navBar'); ?>
```

它位于后台顶部导航栏右侧、当前用户名链接之前，所以 `render()` 输出的内容会出现在每个后台页面的这个位置。

两点需要注意：

- `activate()` **只在启用时执行一次**。Typecho 把这里注册的回调保存到数据库中（选项 `plugins`），之后每次请求从数据库读出，不会再调用 `activate()`。所以修改了 `activate()` 中的注册代码后，要先禁用、再启用插件才会生效。
- 回调要写成字符串 `'类名::方法名'` 或数组 `[类名, '方法名']`，不能是闭包：回调要以 JSON 形式保存到数据库中。

钩子的全部用法见[使用钩子](./hooks.md)，Typecho 提供的全部钩子见[钩子列表](../reference/hooks.md)。

### 第 35–37 行：deactivate()

禁用插件时调用。HelloWorld 不需要做任何清理，所以是空方法。注册的回调和插件设置由 Typecho 在禁用时自动删除，不需要在这里处理。

### 第 44–49 行：config()，设置表单

```php
public static function config(Form $form)
{
    /** 分类名称 */
    $name = new Text('word', null, 'Hello World', _t('说点什么'));
    $form->addInput($name);
}
```

`config()` 往 `$form` 中添加表单元素，后台就会在插件列表中显示“设置”链接。这里添加了一个文本框：

- `'word'`：设置项的名字，之后用 `->word` 读取；
- `null`：选项列表，文本框不需要；
- `'Hello World'`：默认值。启用插件时，Typecho 会把所有设置项的默认值存入数据库；
- `_t('说点什么')`：显示在文本框前的标签。

可用的表单元素见[表单元素](../reference/form-elements.md)。

### 第 56–58 行：personalConfig()

每个用户各自的设置，显示在“个人设置”页面。HelloWorld 不需要，是空方法。见[插件配置](./settings.md)。

### 第 66–71 行：render()，钩子的回调

```php
public static function render()
{
    echo '<span class="message success">'
        . htmlspecialchars(Options::alloc()->plugin('HelloWorld')->word)
        . '</span>';
}
```

`Options::alloc()->plugin('HelloWorld')` 取得插件的设置，`->word` 就是设置表单中 `word` 一项的值。输出前用 `htmlspecialchars()` 转义，防止设置值中的 HTML 被执行。`message success` 是后台自带的样式（绿色提示条）。

## 动手写 Greeting

照着 HelloWorld 写一个插件 `Greeting`：后台导航栏显示一句问候语，同时在前台每个页面的底部输出同一句话。

### 1. 建立目录和文件

在 `usr/plugins/` 下新建目录 `Greeting`，在其中新建 `Plugin.php`：

```text
usr/plugins/Greeting/
└── Plugin.php
```

### 2. 写入代码

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
 * 在后台导航栏和前台页脚显示一句问候语
 *
 * @package Greeting
 * @author 你的名字
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

与 HelloWorld 相比有三处变化：

- 命名空间和 `@package` 都改成了 `Greeting`，与目录名一致；
- 加了 `@since 1.3.0`：低于 1.3.0 的 Typecho 会拒绝启用它；
- 多注册了一个钩子：`Widget\Archive` 的 `footer`。主题在 `footer.php` 中调用 `$this->footer()` 时触发它，所以问候语会出现在前台页面底部（主题必须调用了 `$this->footer()`，自带的两个主题都调用了）。

### 3. 启用并检查

1. 进入后台“控制台 → 插件”，`Greeting` 出现在“禁用的插件”列表中，显示头注释中的描述、版本和作者。点击“启用”。
2. 页面提示“插件已经被启用”，`Greeting` 移到“启用的插件”列表，后面有“设置”和“禁用”两个链接。
3. 后台顶部导航栏右侧出现绿色的“你好，Typecho”。
4. 打开网站首页，页面底部出现同一句话。
5. 点击“设置”，把问候语改成别的文字并保存，页面提示“插件设置已经保存”。导航栏和前台页脚都变成新的文字。

如果启用后导航栏没有变化，依次检查：命名空间是否是 `TypechoPlugin\Greeting`、目录名是否是 `Greeting`、`implements` 后面是否写成了 `PluginInterface`。修改 `activate()` 后要禁用再启用。

## 下一步

- [插件结构与生命周期](./structure.md)：单文件插件、头注释的完整规则、启用和禁用时 Typecho 做了什么。
- [使用钩子](./hooks.md)：钩子的命名、优先级和三种调用方式。
- [插件配置](./settings.md)：设置表单的校验、自定义保存、个人设置。
