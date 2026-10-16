---
title: 主题设置
sidebar_position: 8
---

# 主题设置

主题可以提供自己的设置项，让站长不改代码就能调整 LOGO、配色、侧边栏等。设置项在 `functions.php` 的 `themeConfig()` 中定义，站长在后台“控制台 → 外观 → 设置外观”中填写，模板中通过 `$this->options` 读取。

## 定义设置表单

```php title="usr/themes/starter/functions.php"
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('LOGO 地址'),
        _t('填写图片地址，留空则显示站点名称')
    );
    $form->addInput($logoUrl->addRule('url', _t('请填写一个合法的URL地址')));

    $colorScheme = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorScheme',
        ['auto' => _t('跟随系统'), 'light' => _t('浅色'), 'dark' => _t('深色')],
        'auto',
        _t('配色')
    );
    $form->addInput($colorScheme);

    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        ['recent' => _t('最新文章'), 'comments' => _t('最近回复'), 'categories' => _t('分类')],
        ['recent', 'categories'],
        _t('侧边栏显示')
    );
    $form->addInput($sidebarBlock->multiMode());

    $footerText = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerText',
        null,
        null,
        _t('页脚文字'),
        _t('显示在每个页面底部，可以使用 HTML')
    );
    $form->addInput($footerText);
}
```

表单元素都在 `\Typecho\Widget\Helper\Form\Element\` 命名空间下，构造参数依次为：

```text
new Element(string $name, ?array $options, $value, ?string $label, ?string $description)
```

| 参数 | 说明 |
|---|---|
| `$name` | 字段名，也是模板中读取时的名字 |
| `$options` | 选项数组 `值 => 显示文字`，用于 `Radio`、`Checkbox`、`Select`；文本类元素传 `null` |
| `$value` | 默认值；`Checkbox` 为默认勾选的值组成的数组 |
| `$label` | 字段标题 |
| `$description` | 字段下方的说明，可以留空 |

常用元素：

| 类 | 界面 | 读到的值 |
|---|---|---|
| `Text` | 单行文本框 | 字符串 |
| `Textarea` | 多行文本框 | 字符串 |
| `Radio` | 单选按钮 | 选中项的值 |
| `Select` | 下拉框 | 选中项的值 |
| `Checkbox` | 复选框 | 选中项的值组成的数组 |

- `addRule()` 添加校验规则，例如 `addRule('url', '提示')`、`addRule('required', '提示')`。校验不通过时表单不会保存，并在字段下方显示提示文字。
- `multiMode()` 让单选 / 复选的每个选项各占一行显示，只影响排版。

## 值的存储

站长点击“保存设置”后，所有字段的值以 JSON 保存在数据库 `options` 表中名为 `theme:主题目录名` 的一行，例如：

```text
theme:starter  {"logoUrl":"https://example.com/logo.png","colorScheme":"auto","sidebarBlock":["recent","categories"],"footerText":""}
```

- **启用主题时**，Typecho 会按表单的默认值先保存一份；
- 在主题已经启用之后给 `themeConfig()` 新增的字段，在站长下次保存设置之前**没有值**（读到 `null`），模板中要做好判断；
- 站长切换到其他主题时，这一行会被删除，切换回来后恢复为默认值。

## 在模板中读取

主题设置会合并进站点设置，所以用 `$this->options->字段名` 读取：

```php
<?php if ($this->options->logoUrl): ?>
    <img src="<?php $this->options->logoUrl(); ?>" alt="<?php $this->options->title(); ?>">
<?php else: ?>
    <?php $this->options->title(); ?>
<?php endif; ?>

<html data-theme="<?php $this->options->colorScheme(); ?>">

<?php if (!empty($this->options->sidebarBlock) && in_array('recent', $this->options->sidebarBlock)): ?>
    <!-- 显示最新文章 -->
<?php endif; ?>
```

- `$this->options->logoUrl` 返回值，`$this->options->logoUrl()` 直接输出。直接输出**不做 HTML 转义**，用户填写的内容会原样进入页面；放在 HTML 属性中、又不希望站长写入 HTML 时，用 `echo htmlspecialchars($this->options->logoUrl);`。
- `Checkbox` 的值是数组，用 `in_array()` 判断；没有保存过时为 `null`，所以先用 `!empty()` 判断。
- 在 `functions.php` 的函数中，可以用 `\Widget\Options::alloc()->logoUrl` 读取同样的值。

:::caution 字段名不要与站点设置重名
主题设置会**覆盖**同名的站点设置。例如主题定义了一个名为 `description` 的字段，站长填写之后，`$this->options->description` 和页面头部的 `<meta name="description">` 都变成这个值，“设置 → 基本”中的站点描述不再生效；“设置外观”页面中这个字段也会预先填入站点描述。`title`、`description`、`keywords`、`siteUrl`、`pageSize`、`theme` 等都是站点设置的名字，主题字段请加上前缀或换一个名字。即使之后从 `themeConfig()` 中删掉了这个字段，已经保存的值仍会继续覆盖，直到站长重新保存一次主题设置。
:::

## 自己处理保存：themeConfigHandle

定义了 `themeConfigHandle(array $settings, bool $isInit)` 时，Typecho 不再把设置写入 `theme:主题目录名`，而是调用这个函数，见 [functions.php 回调](./functions-php.md#themeconfig-与-themeconfighandle)。
