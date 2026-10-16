---
title: 主题与插件迁移到 1.3
sidebar_position: 2
---

# 主题与插件迁移到 1.3

本页面向主题和插件作者，列出把为 Typecho 1.1 / 1.2 写的代码迁移到 1.3 时需要检查的地方。站长关心的变化见[1.3 新特性与变更](../guide/getting-started/whats-new-1-3.md)。

1.3 需要 PHP 7.4 及以上，内部大量使用了类型声明。大部分旧写法仍然可以运行，但新代码请使用本页推荐的写法。

## 类名：`Widget_*` → `\Widget\*::alloc()`

旧代码通过类名字符串取得 Widget：

```php
<?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=5')->parse('<li>{title}</li>'); ?>
<?php Typecho_Widget::widget('Widget_Metas_Category_List')->listCategories(); ?>
<?php $options = Typecho_Widget::widget('Widget_Options'); ?>
```

1.3 的写法是带命名空间的类名加 `alloc()`：

```php
<?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->parse('<li>{title}</li>'); ?>
<?php \Widget\Metas\Category\Rows::alloc()->listCategories(); ?>
<?php $options = \Widget\Options::alloc(); ?>
```

- 旧写法在 1.3 中**仍然可以运行**：`$this->widget()`、`Typecho_Widget::widget()` 依然存在，旧类名会被自动加载器对应到新类，`Widget_Metas_Category_List` 等改了名字的类由别名表处理。完整对照见[旧类名对照表](./reference/class-aliases.md)。
- 少数 1.1 的类已经不存在（如 `Typecho_Http_Client_Adapter_*`、`Typecho_Db_Adapter_Exception`），用到它们的代码会报“类不存在”。
- 新类名带开头的 `\`，在主题模板中（没有 `namespace` 声明）写成 `\Widget\...` 或 `Widget\...` 都可以；在插件的命名空间中必须写开头的 `\`，或者用 `use` 导入。
- `alloc()` 与 `widget()` 一样按类名缓存：同一个类第二次调用得到的是第一次的实例。需要同一个类的多个实例时用 `allocWithAlias('别名', 参数)`，见[在模板中调用 Widget](./theme/widgets.md)。
- 插件主类建议改为命名空间写法：

  ```php
  <?php
  namespace TypechoPlugin\HelloWorld;

  use Typecho\Plugin\PluginInterface;
  use Typecho\Widget\Helper\Form;

  class Plugin implements PluginInterface
  {
      // ...
  }
  ```

  旧的 `class HelloWorld_Plugin implements Typecho_Plugin_Interface` 仍然能被识别（自动对应到 `\TypechoPlugin\HelloWorld\Plugin`）。

## 设置改用 JSON 保存

1.3 起，插件设置（`plugin:插件名`）、主题设置（`theme:主题名`）、已启用插件列表（`plugins`）、`actionTable`、`panelTable`、路由表（`routingTable`）以及附件信息（附件内容的 `text` 字段）都以 **JSON** 保存，之前是 PHP `serialize()`。

- 通过 Typecho 的接口读取选项不受影响：`$this->options->plugin('名字')`、`$this->options->主题字段` 等会自动识别两种格式。附件的 `$this->attachment` 只按 JSON 解析，旧数据由升级程序转换。
- **直接读写数据库**的代码需要修改：读取时用 `json_decode($value, true)`，写入时用 `json_encode()`；不要再对这些字段调用 `unserialize()` / `serialize()`。
- 从 1.2 及更早的版本升级时，升级程序会把已有的附件信息和上述选项从 serialize 转换为 JSON。插件的个人设置（`_plugin:插件名`）不在转换范围内，读取时仍兼容旧格式。
- 插件中用 `Helper::setOption()` 写入数组时，会以 JSON 保存。

## 触发钩子：`Plugin::__call` → `call()` / `filter()`

插件或主题自己提供钩子时，旧写法是直接调用一个“方法”：

```php
$result = \Typecho\Plugin::factory('MyPlugin')->beforeOutput($data);
```

1.3 中这种魔术调用（`Typecho\Plugin::__call()`）已标记为弃用，请改为：

```php
// 执行所有回调，返回最后一个回调的返回值
$result = \Typecho\Plugin::factory('MyPlugin')->call('beforeOutput', $data);

// 依次把值交给每个回调处理，返回最终的值
$data = \Typecho\Plugin::factory('MyPlugin')->filter('beforeOutput', $data);
```

- `call($component, ...$args)`：调用该钩子上注册的全部回调；
- `filter($component, $value, ...$args)`：把 `$value` 依次传给每个回调，每个回调的返回值作为下一个回调的 `$value`，最后返回结果；
- 可以先调用 `trigger($signal)` 判断是否有回调被执行：`Plugin::factory('X')->trigger($plugged)->call('hook', ...)`，之后 `$plugged` 为 `true` 表示有插件接管；
- **注册**回调的写法不变：`\Typecho\Plugin::factory('Widget\Archive')->header = [Plugin::class, 'header'];`。句柄名写 `Widget\Archive` 或 `Widget_Archive` 都可以。

## 类型声明可能破坏子类

1.3 给核心类的属性和方法加上了类型声明。继承核心类（例如继承 `\Widget\Base\Contents`、`\Widget\Archive` 来写自己的 Widget）的代码，如果重新声明了父类已有的属性或方法，就必须与父类的类型一致，否则在类加载时就会致命错误：

```text
PHP Fatal error: Type of MyContents::$options must be Widget\Options (as in class Widget\Base)
PHP Fatal error: Declaration of MyContents::___permalink() must be compatible with Widget\Base\Contents::___permalink(): string
```

检查方法：

- 子类中重新声明的属性，类型要与父类相同（例如 `protected \Widget\Options $options;`），或者干脆不要重新声明；
- 覆盖的方法要带上与父类相同或兼容的参数类型和返回类型，例如 `protected function ___permalink(): string`；
- 父类的类型化属性在赋值之前不能读取，否则抛出 `must not be accessed before initialization`。例如在首页上读取 `Widget\Archive` 的归档缩略名就会这样，见[页面上下文与 is()](./theme/context.md)。

## 默认主题移除了 archive.php

1.3 的默认主题（`usr/themes/default`）删除了 `archive.php`，分类、标签、搜索等列表页改由 `index.php` 输出。以默认主题为基础改出的主题：

- 自带 `archive.php` 的主题不受影响，Typecho 仍然按[模板层级](./theme/template-hierarchy.md)优先使用它；
- 想与新版默认主题保持一致，可以删除 `archive.php`，在 `index.php` 中用 `$this->is('index')` 区分首页和其他列表页，例如只在非首页输出归档标题。

## comments() 不接受类型参数

一些旧主题这样只取普通评论：

```php
<?php $this->comments('comment')->to($comments); ?>
```

`comments()` 没有参数，传入的 `'comment'` 会被忽略，返回的列表**包括引用**（Trackback、Pingback）。只显示普通评论有两种办法：

- 由站长在“设置 → 评论”中勾选“仅显示评论, 不显示 Pingback 和 Trackback”；
- 在主题中按 `$comments->type` 筛选，写法见[评论模板](./theme/comments.md#pings)。

另外，1.3.0 中 `$this->pings()` 无法使用（会导致致命错误），不要用它单独取引用。

## 其他检查项

- 草稿：已发布内容的草稿改为 `revision` 类型（`parent` 指向原内容），直接查询 `contents` 表的代码要考虑这个类型；
- 选项 `commentsRequireURL` 改名为 `commentsRequireUrl`；
- 订阅源改由 `\Widget\Feed` 输出，评论分页改由 `\Widget\CommentPage` 处理，接管这些输出的插件需要检查；
- 自定义的表单元素：`Textarea`、`Select` 在 1.3.0 中必须提供标题参数，见[表单元素](./reference/form-elements.md)；
- 在 `__TYPECHO_DEBUG__` 下逐页访问前台和后台，PHP 8 下的弃用提示和类型错误都会直接显示出来。
