---
title: 模板中的自定义字段
sidebar_position: 9
---

# 模板中的自定义字段

自定义字段是附加在文章或页面上的额外数据，例如副标题、封面图、阅读次数。作者在编辑器的“自定义字段”面板中填写（见[自定义字段（编辑器侧）](../../guide/content/custom-fields.md)），主题在模板中读取。主题还可以预先声明字段，让它们以表单的形式出现在编辑器中。

## 读取字段

在文章页、页面中，或者文章列表的循环中，用 `$this->fields->字段名`：

```php
<?php if ($this->fields->subtitle): ?>
    <p class="subtitle"><?php $this->fields->subtitle(); ?></p>
<?php endif; ?>

<?php if ($this->fields->cover): ?>
    <img src="<?php $this->fields->cover(); ?>" alt="">
<?php endif; ?>
```

- `$this->fields->subtitle` 返回值；`$this->fields->subtitle()` 直接输出，**不做 HTML 转义**。
- 字段不存在时 `$this->fields->subtitle` 为 `null`，所以可以直接放在 `if` 中判断。但对不存在的字段调用输出方法（`$this->fields->nothing()`）会产生一条 `Undefined array key` 警告，先判断再输出。
- 读到的值的类型由字段类型决定：“字符”为字符串，“整数”为 `int`，“小数”为 `float`，“JSON 结构”为解码后的数组或值。例如作者把 `views` 设为整数 42，`$this->fields->views + 1` 得到 `43`。
- 在其他 Widget 的循环中同样可用，例如 `$posts->fields->cover`。

:::caution 编辑器面板中的“JSON 结构”
1.3.0 中，作者在编辑器面板里手动添加、类型选“JSON 结构”的字段，模板读到的是原样的文本字符串，而不是数组（详见编辑器侧页面的[说明](../../guide/content/custom-fields.md#types)）。如果主题依赖结构化数据，要么让作者用“字符”类型填写 JSON、模板中自己 `json_decode()`，要么像下文那样由主题预置复选框等多值字段。
:::

## 预置字段

在 `functions.php` 中定义下面三个函数中的任意几个，声明的字段会以表单的形式出现在编辑器“自定义字段”面板的最上方：

| 函数 | 字段出现在 |
|---|---|
| `themeFields($layout)` | 文章和独立页面 |
| `themePostFields($layout)` | 只在文章 |
| `themePageFields($layout)` | 只在独立页面 |

```php title="usr/themes/starter/functions.php"
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

function themePostFields($layout)
{
    $source = new \Typecho\Widget\Helper\Form\Element\Text(
        'source',
        null,
        null,
        _t('转载来源')
    );
    $layout->addItem($source);
}

function themePageFields($layout)
{
    $hideTitle = new \Typecho\Widget\Helper\Form\Element\Radio(
        'hideTitle',
        ['0' => _t('显示'), '1' => _t('隐藏')],
        '0',
        _t('页面标题')
    );
    $layout->addItem($hideTitle);
}
```

这样一来，文章编辑器中有“封面图地址”和“转载来源”两个字段，页面编辑器中有“封面图地址”和“页面标题”两个字段。

- 表单元素的写法与[主题设置](./theme-options.md)相同，区别是这里用 `$layout->addItem()`。
- 预置字段一般保存为“字符”类型；`Checkbox` 这类多值元素保存为“JSON 结构”，模板中读到数组，再次打开编辑器时选项保持勾选。
- 作者没有填写的预置字段，保存后值为空字符串。
- 预置字段的名字不会再出现在面板下方的自由字段行中；换成没有声明这些字段的主题后，已经保存的值仍在，会作为普通字段行显示。

模板中读取：

```php
<?php if ($this->is('page') && '1' === $this->fields->hideTitle): ?>
    <!-- 隐藏标题 -->
<?php else: ?>
    <h1><?php $this->title(); ?></h1>
<?php endif; ?>
```

## 字段名的规则

字段名只能由字母、数字和下划线组成，不能以数字开头。预置字段的名字由主题决定，建议加上主题前缀（如 `starter_cover`），避免与插件或作者手动添加的字段冲突。
