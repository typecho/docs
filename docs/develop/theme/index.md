---
title: 主题开发概览
sidebar_position: 1
---

# 主题开发概览

主题决定 Typecho 站点前台的样子。一个主题就是 `usr/themes/` 下的一个目录，里面是若干 PHP 模板文件，以及样式、脚本、图片等静态文件。站长在后台“控制台 → 外观”中切换主题，使用方法见使用指南中的[安装与设置主题](../../guide/extend/themes.md)。

## 模板中的 `$this`

访客打开前台的任何页面时，Typecho 先根据地址判断这是什么页面（首页、文章、分类……），查询出需要的数据，再选一个模板文件来输出。这些工作都由一个 `Widget\Archive` 对象完成，模板文件就是在这个对象的方法里被 `require` 进来的，所以**模板中的 `$this` 就是这个 `Widget\Archive` 对象**：

```php
<?php while ($this->next()): ?>
    <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
<?php endwhile; ?>
```

- 列表页（首页、分类、标签、搜索、归档）中，`$this` 装着当前页的多篇文章，用 `$this->next()` 逐篇取出；
- 文章页和独立页面中，`$this` 就是那一篇内容，直接调用 `$this->title()`、`$this->content()`；
- `$this->options` 是站点设置（包括当前主题的设置），`$this->user` 是当前登录的用户；
- 大多数输出方法直接 `echo`，例如 `$this->title()`；不带括号的属性返回值，例如 `$this->title`，可以用在判断和拼接中。

在模板中还可以调用其他 Widget 取得额外的数据，例如侧边栏的最新文章、分类列表，见[在模板中调用 Widget](./widgets.md)。

## 本节的阅读顺序

1. [第一个主题](./quick-start.md)：从空目录写出一个完整可用的最小主题，先跟着做一遍；
2. [主题结构与头部信息](./anatomy.md)：目录要求、`index.php` 头注释、截图、静态资源；
3. [模板层级](./template-hierarchy.md)：Typecho 按什么顺序选择模板文件；
4. [页面上下文与 is()](./context.md)：在模板中判断当前是什么页面；
5. [functions.php 回调](./functions-php.md)：`themeInit`、`themeConfig` 等回调的调用时机和签名；
6. 按需阅读：[自定义模板](./custom-templates.md)、[主题设置](./theme-options.md)、[模板中的自定义字段](./custom-fields.md)、[评论模板](./comments.md)、[在模板中调用 Widget](./widgets.md)、[头部输出 header()](./header.md)；
7. [发布主题](./releasing.md)：打包和发布。

“主题技巧”中是可以直接复制的小功能：[自定义页面标题](./recipes/page-title.md)、[面包屑导航](./recipes/breadcrumbs.md)、[相关文章](./recipes/related-posts.md)、[分类描述](./recipes/category-description.md)、[标签云](./recipes/tag-cloud.md)、[404 与错误页](./recipes/error-pages.md)。

## 开发时的建议

- 在本地或测试站点上开发，打开调试模式（`config.inc.php` 中定义 `__TYPECHO_DEBUG__`），模板出错时可以直接看到错误信息和行号，见[调试与排错](../../guide/operations/debugging.md#debug)。
- 以随 Typecho 发布的 `usr/themes/classic-22/` 为参照。它覆盖了常见的模板写法，代码量不大。
- 主题需要的 PHP 版本与 Typecho 1.3 相同，至少 PHP 7.4。
