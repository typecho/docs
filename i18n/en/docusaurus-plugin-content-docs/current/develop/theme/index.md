---
title: Theme Development Overview
sidebar_position: 1
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Theme Development Overview

A theme determines how the front end of a Typecho site looks. A theme is a directory under `usr/themes/` that contains a number of PHP template files plus static files such as stylesheets, scripts and images. Site owners switch themes in the admin panel under "Dashboard → Appearance"; for how to do that, see [Installing and configuring themes](../../guide/extend/themes.md) in the Guide.

## `$this` in templates

When a visitor opens any front-end page, Typecho first works out from the URL what kind of page it is (home page, post, category ...), queries the data it needs, and then picks a template file to produce the output. All of this is done by a `Widget\Archive` object, and template files are `require`d from inside a method of that object, so **`$this` in a template is that `Widget\Archive` object**:

```php
<?php while ($this->next()): ?>
    <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
<?php endwhile; ?>
```

- On list pages (home page, category, tag, search, archive), `$this` holds the posts of the current page; take them out one by one with `$this->next()`;
- On post and page views, `$this` is that single item; call `$this->title()` and `$this->content()` directly;
- `$this->options` is the site settings (including the settings of the current theme), and `$this->user` is the currently logged-in user;
- Most output methods `echo` directly, for example `$this->title()`; the property without parentheses returns the value, for example `$this->title`, which you can use in conditions and string concatenation.

In a template you can also call other widgets to get additional data, such as the latest posts or the category list for a sidebar; see [Calling widgets in templates](./widgets.md).

## Reading order for this section

1. [Your first theme](./quick-start.md): write a complete, working minimal theme from an empty directory; follow along once first;
2. [Theme structure and header information](./anatomy.md): directory requirements, the `index.php` header comment, screenshots, static assets;
3. [Template hierarchy](./template-hierarchy.md): the order in which Typecho selects template files;
4. [Page context and is()](./context.md): determining in a template what kind of page is being shown;
5. [functions.php callbacks](./functions-php.md): when callbacks such as `themeInit` and `themeConfig` are called and what their signatures are;
6. Read as needed: [Custom templates](./custom-templates.md), [Theme options](./theme-options.md), [Custom fields in templates](./custom-fields.md), [Comment templates](./comments.md), [Calling widgets in templates](./widgets.md), [Header output: header()](./header.md);
7. [Releasing a theme](./releasing.md): packaging and publishing.

"Theme recipes" contains small features you can copy directly: [Custom page titles](./recipes/page-title.md), [Breadcrumb navigation](./recipes/breadcrumbs.md), [Related posts](./recipes/related-posts.md), [Category descriptions](./recipes/category-description.md), [Tag cloud](./recipes/tag-cloud.md), [404 and error pages](./recipes/error-pages.md).

## Development tips

- Develop locally or on a test site and turn on debug mode (define `__TYPECHO_DEBUG__` in `config.inc.php`); when a template fails you see the error message and line number directly. See [Debugging and troubleshooting](../../guide/operations/debugging.md#debug).
- Use `usr/themes/classic-22/`, which ships with Typecho, as a reference. It covers the common template patterns and is small.
- Themes need the same PHP version as Typecho 1.3: PHP 7.4 or later.
