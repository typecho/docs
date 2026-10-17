---
title: Template Hierarchy
sidebar_position: 4
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Template Hierarchy

A theme can consist of a single `index.php`, or it can have a separate template for categories, tags, or even one particular post. When Typecho outputs a page (`Widget\Archive::render()`), it looks for template files in a fixed order and uses the first one that exists.

## Lookup order

Let the current page's **type** be `{type}` and its **slug** be `{slug}` (the values are listed in the next section). Typecho looks for, in order:

1. **A template that has already been assigned**: when a page has selected a [custom template](./custom-templates.md), when the home page is set to "use a template file directly", or for the 404 page (assigned `404.php`), that file is tried first;
2. `{type}/{slug}.php`, for example `category/tech.php`, `page/about.php`, `post/12.php`;
3. `{type}.php`, for example `category.php`, `page.php`, `post.php`;
4. For attachment pages (`{type}` is `attachment`): `page.php`, then `post.php`;
5. `single.php` for posts, pages and attachments, `archive.php` for all other pages (the home page and the post list page skip this step);
6. `index.php`.

If none is found, Typecho reports "file does not exist" (HTTP 500), which is why `index.php` is required.

Note that step 2 uses a **subdirectory**: `category/tech.php` lives in the `category` subdirectory of the theme directory, not in a file named `category-tech.php`.

## Type and slug of each page

| Page | Example URL | `{type}` | `{slug}` | Dedicated templates available |
|---|---|---|---|---|
| Home page | `/`, `/page/2/` | `index` | none | `index.php` |
| Post list page | `/blog/`, `/blog/page/2/` | `front` | none | `front.php`, `index.php` |
| Post | `/archives/12/` | `post` | post ID | `post/12.php`, `post.php`, `single.php` |
| Page | `/about.html` | `page` | page slug | `page/about.php`, `page.php`, `single.php` |
| Attachment | `/attachment/15/` | `attachment` | attachment ID | `attachment/15.php`, `attachment.php`, `page.php`, `post.php`, `single.php` |
| Category | `/category/tech/` | `category` | category slug | `category/tech.php`, `category.php`, `archive.php` |
| Tag | `/tag/php/` | `tag` | tag slug | `tag/php.php`, `tag.php`, `archive.php` |
| Author | `/author/1/` | `author` | user ID | `author/1.php`, `author.php`, `archive.php` |
| Date archive | `/2026/10/` | `date` | `year`, `month` or `day` | `date/month.php`, `date.php`, `archive.php` |
| Search | `/search/typecho/` | `search` | keyword | `search.php`, `archive.php` |
| 404 | any URL that does not exist | `archive` | `404` | `404.php`, otherwise `archive/404.php`, `archive.php` |

- The slug of a post is the **post ID**, not its URL slug, so a template for one particular post must be named `post/<post ID>.php`;
- The slug of a date archive is its granularity; `date/year.php`, `date/month.php` and `date/day.php` correspond to yearly, monthly and daily archives;
- When a page is used as the home page, the home page looks up templates as that page (`page/<slug>.php`, `page.php` ...);
- The post list page exists only when the home page is set to a page or a template file and the [post list page](../../guide/settings/reading.md#front-archive) is enabled. It does not use `archive.php`; without `front.php` it uses `index.php` directly.

## Example

A theme has these files:

```text
index.php
single.php
archive.php
search.php
category/tech.php
page/about.php
post.php
```

| Request | Uses |
|---|---|
| Home page | `index.php` |
| Any post | `post.php` (`single.php` is never used while `post.php` exists) |
| The page with slug `about` | `page/about.php` |
| Other pages | `single.php` |
| Category `tech` | `category/tech.php` |
| Other categories, tags, authors, date archives, 404 | `archive.php` |
| Search | `search.php` |

## Included files

Shared parts such as `header.php`, `footer.php` and `comments.php` take no part in the lookup; templates include them themselves with `$this->need('file.php')`:

```php
<?php $this->need('header.php'); ?>
```

`need()` `require`s the file inside a method of `Widget\Archive`, so `$this` is unchanged in the included file; but **variables newly defined in the included file are only valid in that file**, and the outer template cannot read them. For data that has to be shared between several files, write a function in `functions.php`, or output it together in the included file.

## Using is() instead of splitting files

When different pages differ only slightly, you do not need to split them into several files; check inside the same template:

```php
<?php if ($this->is('category', 'tech')): ?>
    <p>This is the Tech category</p>
<?php endif; ?>
```

For the full usage of `is()`, see [Page context and is()](./context.md).
