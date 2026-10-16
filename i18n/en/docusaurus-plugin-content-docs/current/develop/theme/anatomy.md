---
title: Theme Structure and Header Information
sidebar_position: 3
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Theme Structure and Header Information

## Directory

A theme is a directory under `usr/themes/`. **The directory name is the theme's identifier**: the current theme stored in the site settings (the `theme` option) is the directory name, and theme options are saved in an option named `theme:目录名` (`theme:` followed by the directory name). Do not rename the directory after release, or the theme options site owners have already saved will no longer match.

The only required file is `index.php`; the admin panel recognizes a theme by "the directory contains `index.php`". Common files:

| File | Purpose |
|---|---|
| `index.php` | **Required**. Its header comment declares the theme information; it is also the final fallback template for every page |
| `header.php`, `footer.php`, `sidebar.php` | Shared parts included by other templates with `$this->need()`; the file names are up to you |
| `post.php`, `page.php` | Post view, page view |
| `archive.php`, `category.php`, `tag.php`, `search.php`, `author.php`, etc. | List pages of various kinds, optional |
| `comments.php` | Comment list and form, usually included by `post.php` and `page.php` |
| `404.php` | Page not found |
| `functions.php` | Not a template; holds theme options, custom fields, callback functions and so on; see [functions.php callbacks](./functions-php.md) |
| `screenshot.png` | The screenshot shown in the theme list in the admin panel |
| Others | CSS, JavaScript, images, fonts and other static files; they can live in subdirectories |

For the order in which template files are selected, see [Template hierarchy](./template-hierarchy.md).

## The header comment of index.php {#header-comment}

The doc comment (`/** ... */`) at the top of `index.php` is the theme information:

```php
<?php
/**
 * 一个简洁的博客主题
 *
 * @package Starter
 * @author 你的名字
 * @version 1.0.0
 * @link https://example.com
 */
```

| Syntax | Shown in the admin panel as |
|---|---|
| The first block of text (the lines before any `@`) | Theme description |
| `@package` | Theme name. Without it, the list shows no name |
| `@author` | Author |
| `@link` | The author name links to this URL |
| `@version` | Version |

The theme list in the admin panel shows "name, author: …, version: …, description", and the active theme is listed first.

`@since` is also read and declares the minimum Typecho version the theme needs (for example `@since 1.3.0`). Note: a plugin's `@since` is checked when the plugin is activated, but **a theme's `@since` is not checked**; the theme can be activated even if the version is too low, so it is informational only.

## Screenshot {#screenshot}

An image in the theme directory (not in subdirectories) whose file name is `screenshot` is shown as the screenshot. The extension can be `png`, `jpg`, `jpeg`, `gif`, `bmp`, `webp` or `avif`, case-insensitive. If there are several, the first one found is used. Without a screenshot, the admin panel shows Typecho's built-in placeholder `admin/img/noscreen.png`.

Typecho has no size requirement for screenshots; the admin panel displays them as thumbnails.

## Preventing direct access

Add this line at the top of every template file:

```php
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
```

`__TYPECHO_ROOT_DIR__` is only defined when the code runs through Typecho's entry point (`index.php`). When someone requests `usr/themes/你的主题/post.php` directly in a browser, this line makes the file exit immediately instead of printing errors because `$this` does not exist.

## Loading static files: themeUrl()

CSS, scripts and images in a theme must be referenced by full URL. `$this->options->themeUrl()` outputs the URL of a file in the current theme directory:

```php
<link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
<script src="<?php $this->options->themeUrl('js/main.js'); ?>"></script>
<img src="<?php $this->options->themeUrl('img/logo.png'); ?>" alt="">
```

- The `$this->options->themeUrl` property, without arguments, is the URL of the theme directory itself and can be used for concatenation;
- The second argument can name another theme's directory; the method then **returns** the URL instead of outputting it:

  ```php
  <?php echo $this->options->themeUrl('style.css', 'classic-22'); ?>
  ```

- To serve the theme's static files from a CDN, define `__TYPECHO_THEME_URL__` in `config.inc.php`; see [Configuration file](/guide/operations/config-file).

Likewise, `$this->options->siteUrl('path')` outputs a path under the site URL, and `$this->options->adminUrl()` outputs the admin panel URL.
