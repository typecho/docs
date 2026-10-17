---
title: 面包屑导航
sidebar_position: 2
---

# 面包屑导航

**问题**：在页面顶部显示“首页 » 技术 » 文章标题”这样的位置导航。

**做法**：在 `header.php` 的 `</header>` 之后（或任何模板中）加入：

```php
<nav class="breadcrumbs">
    <a href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
    <?php if ($this->is('post')): ?>
        &raquo; <?php $this->category(' / '); ?>
        &raquo; <?php $this->title(); ?>
    <?php elseif ($this->is('front')): ?>
        &raquo; <?php _e('文章列表'); ?>
    <?php elseif (!$this->is('index')): ?>
        &raquo; <?php $this->archiveTitle([
            'category' => _t('分类 %s'),
            'search'   => _t('搜索 %s'),
            'tag'      => _t('标签 %s'),
            'author'   => _t('作者 %s'),
        ], '', ''); ?>
    <?php endif; ?>
</nav>
```

**效果**：

| 页面 | 输出 |
|---|---|
| 首页 | 首页 |
| 文章 | 首页 » 技术 » 文章标题（文章有多个分类时用 ` / ` 分隔，每个分类都是链接） |
| 独立页面 | 首页 » 页面标题 |
| 分类、标签、搜索、作者 | 首页 » 分类 技术 |
| 日期归档 | 首页 » 2026年10月 |
| 文章列表页 | 首页 » 文章列表 |
| 404 | 首页 » 页面没找到 |

**原理**：

- `$this->is('post')` 只在文章页为真，这时用 `$this->category(' / ')` 输出文章所属分类的链接。第二个参数为 `false` 时只输出分类名、不带链接。
- 文章列表页（`front`，见[页面上下文](../context.md)）没有归档标题，单独输出一个固定的名称。
- 其他非首页的页面都有归档标题，直接用 `archiveTitle()` 输出，数组中的格式按页面类型选择。
- 首页用 `$this->is('index')` 判断，什么都不追加。以独立页面作为首页时，`is('index')` 同样为真。
