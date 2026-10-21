---
title: functions.php 回调
sidebar_position: 6
---

# functions.php 回调

主题目录中的 `functions.php` 不是模板，而是一个在需要时被加载的函数文件。Typecho 会查找其中特定名称的函数，在特定的时机调用它们。除了这些回调，你也可以在这里写自己的辅助函数，供模板调用（例如 `classic-22` 的 `postMeta()`）。

```php
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// 回调函数和自己的辅助函数写在这里
```

## 回调一览

| 函数 | 调用时机 | 用途 |
|---|---|---|
| `themeInit($archive)` | 前台确定页面类型后、执行列表查询和输出模板前；此时单篇内容已经取出。首页设为模板文件时不调用 | 修改当前页面的参数，例如每页篇数 |
| `themeConfig($form)` | 打开“设置外观”页面、启用主题时 | 定义主题设置表单 |
| `themeConfigHandle($settings, $isInit)` | 保存主题设置、启用主题时 | 自己处理设置的保存 |
| `themeFields($layout)` | 编辑器生成自定义字段区域时 | 为文章和页面预置自定义字段 |
| `themePostFields($layout)` | 同上，只用于文章 | 只给文章预置字段 |
| `themePageFields($layout)` | 同上，只用于独立页面 | 只给独立页面预置字段 |
| `threadedComments($comments, $options)` | `listComments()` 输出每一条评论时 | 自定义单条评论的 HTML |
| `singlePing($pings, $options)` | `listPings()` 输出每一条引用时 | 自定义单条引用的 HTML（1.3.0 中不可用，见下文） |

所有回调都是可选的，没有定义时使用 Typecho 的默认行为。

`themeFields`、`themePostFields`、`themePageFields` 只用来生成编辑器中的字段控件，保存文章或页面时不会调用，不能在其中校验或处理提交的字段值。

## themeInit

```php
function themeInit($archive)
{
    if ($archive->is('category', 'tech')) {
        $archive->parameter->pageSize = 2;
    }
}
```

- 参数 `$archive` 就是模板中的 `$this`（`Widget\Archive`）。
- 调用时机：Typecho 已经根据地址确定了页面类型、准备好了查询，但**还没有执行列表查询**，所以这里修改 `pageSize` 对当前列表页有效。上面的例子让 `tech` 分类每页只显示 2 篇，`/category/tech/2/` 是第二页。
- 文章页、独立页面在调用 `themeInit` 之前已经取出了内容，这时可以读取 `$archive->title` 等属性来做判断。
- 只在前台页面中调用；后台、订阅源不会调用。
- 首页设为“直接调用模板文件”时，Typecho 不加载 `functions.php`，也不调用 `themeInit`，见[首页模板](./custom-templates.md#首页模板package-index)。

更多例子见[自定义模板](./custom-templates.md#page-size)。

## themeConfig 与 themeConfigHandle

```php
function themeConfig($form)
{
    $footerText = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerText',
        null,
        null,
        _t('页脚文字'),
        _t('显示在每个页面底部，可以留空')
    );
    $form->addInput($footerText);
}
```

- `$form` 是 `\Typecho\Widget\Helper\Form` 对象，用 `addInput()` 添加表单元素。定义了这个函数，后台外观页面就会出现“设置外观”标签。
- 保存的值以 JSON 存在 `theme:主题目录名` 选项中，模板中用 `$this->options->footerText` 读取。

如果同时定义了 `themeConfigHandle()`，Typecho 就**不再自己保存**设置，而是把表单值交给它：

```php
function themeConfigHandle(array $settings, bool $isInit)
{
    // $settings：表单中所有字段的值
    // $isInit：启用主题时为 true，在“设置外观”中保存时为 false
}
```

启用主题时，`$settings` 是各字段的默认值。如果用它把设置写到别处（例如生成一个 CSS 文件），存储要由主题自己负责，而且这些值不会出现在 `$this->options` 中。

表单元素的种类和读取方式见[主题设置](./theme-options.md)。

## themeFields、themePostFields、themePageFields

```php
function themeFields($layout)
{
    $cover = new \Typecho\Widget\Helper\Form\Element\Text(
        'cover',
        null,
        null,
        _t('封面图地址'),
        _t('留空则不显示封面')
    );
    $layout->addItem($cover);
}
```

- `$layout` 是 `\Typecho\Widget\Helper\Layout` 对象，用 `addItem()` 添加表单元素。
- `themeFields()` 中的字段同时出现在文章和独立页面的编辑器中；`themePostFields()` 只出现在文章编辑器中，`themePageFields()` 只出现在页面编辑器中。三者可以同时定义，字段会合并显示。
- 字段出现在编辑器“自定义字段”面板的最上方，模板中用 `$this->fields->cover` 读取。

详见[模板中的自定义字段](./custom-fields.md)。

## threadedComments

```php
function threadedComments($comments, $options)
{
    // $comments：当前这条评论（Widget\Comments\Archive）
    // $options：listComments() 的选项（Typecho\Config），如 $options->avatarSize
}
```

定义了这个函数，`$comments->listComments()` 就不再输出 Typecho 默认的评论 HTML，而是对每条评论调用它。函数中要自己输出这条评论，并在有子评论时调用 `$comments->threadedComments()` 递归输出回复。完整示例见[评论模板](./comments.md#callback)。

## singlePing

`singlePing($pings, $options)` 本应用来自定义 `$this->pings()->listPings()` 中每条引用的输出。1.3.0 中 `$this->pings()` 无法使用（调用时出现致命错误，见[评论模板](./comments.md#pings)），这个回调也就不会被调用。需要单独列出引用时，按评论模板一页中的做法从 `comments()` 中筛选。
