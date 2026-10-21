---
title: 模板层级
sidebar_position: 4
---

# 模板层级

同一个主题可以只有一个 `index.php`，也可以给分类、标签、某一篇文章各写一个模板。Typecho 输出页面时（`Widget\Archive::render()`）按固定的顺序查找模板文件，找到第一个存在的就用它。

## 查找顺序

设当前页面的**类型**为 `{type}`、**缩略名**为 `{slug}`（取值见下一节），依次查找：

1. **已经指定的模板**：独立页面选择了[自定义模板](./custom-templates.md)、首页设为“直接调用模板文件”、404 页面（指定为 `404.php`）时，先用这个文件；
2. `{type}/{slug}.php`，例如 `category/tech.php`、`page/about.php`、`post/12.php`；
3. `{type}.php`，例如 `category.php`、`page.php`、`post.php`；
4. 附件页面（`{type}` 为 `attachment`）：`page.php`，再 `post.php`；
5. 文章、独立页面、附件用 `single.php`，其他页面用 `archive.php`（首页和文章列表页不查这一步）；
6. `index.php`。

都找不到时报错“文件不存在”（HTTP 500），所以 `index.php` 是必需的。

注意第 2 步是**子目录**：`category/tech.php` 在主题目录下的 `category` 子目录中，而不是名为 `category-tech.php` 的文件。

## 各页面的类型和缩略名

| 页面 | 地址示例 | `{type}` | `{slug}` | 可用的专属模板 |
|---|---|---|---|---|
| 首页 | `/`、`/page/2/` | `index` | 无 | `index.php` |
| 文章列表页 | `/blog/`、`/blog/page/2/` | `front` | 无 | `front.php`、`index.php` |
| 文章 | `/archives/12/` | `post` | 文章 ID | `post/12.php`、`post.php`、`single.php` |
| 独立页面 | `/about.html` | `page` | 页面缩略名 | `page/about.php`、`page.php`、`single.php` |
| 附件 | `/attachment/15/` | `attachment` | 附件 ID | `attachment/15.php`、`attachment.php`、`page.php`、`post.php`、`single.php` |
| 分类 | `/category/tech/` | `category` | 分类缩略名 | `category/tech.php`、`category.php`、`archive.php` |
| 标签 | `/tag/php/` | `tag` | 标签缩略名 | `tag/php.php`、`tag.php`、`archive.php` |
| 作者 | `/author/1/` | `author` | 用户 ID | `author/1.php`、`author.php`、`archive.php` |
| 日期归档 | `/2026/10/` | `date` | `year`、`month` 或 `day` | `date/month.php`、`date.php`、`archive.php` |
| 搜索 | `/search/typecho/` | `search` | 关键字 | `search.php`、`archive.php` |
| 404 | 任何不存在的地址 | `archive` | `404` | `404.php`，没有时 `archive/404.php`、`archive.php` |

- 文章的缩略名是**文章 ID**，不是网址缩略名，所以为某篇文章单独做模板要用 `post/文章ID.php`；
- 日期归档的缩略名是归档粒度，`date/year.php`、`date/month.php`、`date/day.php` 分别对应年、月、日归档；
- 以独立页面作为首页时，首页按这个独立页面查找模板（`page/缩略名.php`、`page.php`……）；
- 只有当首页改为页面或模板文件、并且开启了[文章列表页](../../guide/settings/reading.md#front-archive)时，才存在文章列表页。它不使用 `archive.php`；没有 `front.php` 时直接使用 `index.php`。

## 示例

一个主题有这些文件：

```text
index.php
single.php
archive.php
search.php
category/tech.php
page/about.php
post.php
```

| 访问 | 使用 |
|---|---|
| 首页 | `index.php` |
| 任意文章 | `post.php`（有 `post.php` 时不会用到 `single.php`） |
| 缩略名为 `about` 的页面 | `page/about.php` |
| 其他独立页面 | `single.php` |
| 分类 `tech` | `category/tech.php` |
| 其他分类、标签、作者、日期归档、404 | `archive.php` |
| 搜索 | `search.php` |

## 被引入的文件

`header.php`、`footer.php`、`comments.php` 这类公共部分不参与查找，由模板自己用 `$this->need('文件名')` 引入：

```php
<?php $this->need('header.php'); ?>
```

`need()` 在 `Widget\Archive` 的方法内部 `require` 文件，所以被引入的文件中 `$this` 不变；但被引入文件中**新定义的变量只在那个文件里有效**，外层模板无法读取。需要在多个文件之间共享的数据，可以在 `functions.php` 中写成函数，也可以直接在被引入的文件中输出。

## 用 is() 代替拆分文件

不同页面差别很小时，不必拆成多个文件，在同一个模板中判断即可：

```php
<?php if ($this->is('category', 'tech')): ?>
    <p>这里是技术分类</p>
<?php endif; ?>
```

`is()` 的完整用法见[页面上下文与 is()](./context.md)。
