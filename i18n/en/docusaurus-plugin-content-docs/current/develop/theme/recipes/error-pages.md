---
title: 404 and Error Pages
sidebar_position: 6
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# 404 and Error Pages

Typecho has two kinds of "error pages": **page not found** (HTTP 404) is output by the theme; **application errors** (the database cannot be reached, a template hits a fatal error, etc., HTTP 500 or 503) are output by Typecho itself, and you can replace that page with your own using `__TYPECHO_EXCEPTION_FILE__`.

## The theme's 404.php

**Problem**: when a URL that does not exist is requested, show a 404 page styled like the rest of the site.

**Solution**: create `404.php` in the theme directory:

```php
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <h1>404</h1>
    <p><?php _e('页面没找到'); ?></p>
    <form method="post" action="<?php $this->options->siteUrl(); ?>">
        <input type="search" name="s" placeholder="<?php _e('搜索'); ?>">
    </form>
    <p><a href="<?php $this->options->siteUrl(); ?>"><?php _e('返回首页'); ?></a></p>
</main>
<?php $this->need('footer.php'); ?>
```

**Result**: URLs that do not exist, and posts that are unpublished or that the visitor is not allowed to view, all use this template; the HTTP status code is 404 and the archive title is "页面没找到" (page not found).

**How it works**: on a 404, Typecho assigns the theme file `404.php` directly. When the theme does not have this file, it looks for `archive/404.php`, `archive.php`, `index.php` in that order (see [Template hierarchy](../template-hierarchy.md)), and the status code is still 404. In a template you can check whether the current page is the 404 page with `$this->is('archive', '404')`.

When `__TYPECHO_DEBUG__` is on, a URL that matches no route does not show `404.php`; instead the exception message and call stack are output directly (with status code 200). Turn it off when you finish debugging to restore the normal behavior.

## The page shown on application errors: `__TYPECHO_EXCEPTION_FILE__`

**Problem**: on errors such as a failed database connection, you do not want to show Typecho's default error page.

**Solution**: create a PHP file, for example `/var/www/error.php` outside the web root:

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>站点暂时无法访问</title>
</head>
<body>
    <h1>站点暂时无法访问</h1>
    <p>错误代码 <?php echo intval($code); ?>，请稍后再试。</p>
</body>
</html>
```

Then specify it in `config.inc.php`:

```php
define('__TYPECHO_EXCEPTION_FILE__', '/var/www/error.php');
```

**Result**: on application errors, Typecho no longer outputs its built-in error page but executes this file instead.

**How it works**:

- Before executing this file, Typecho has already set the HTTP status code. Three variables are available in the file: `$code` (the status code), `$message` (the error message) and `$exception` (the exception object). `$message` has already been sanitized: database errors appear as fixed text such as `Error establishing a database connection`, and other application errors as `Server Error`; `$exception`, however, holds the full error message and call stack, which may contain internal information such as the database address and file paths, so **do not output it to visitors**.
- This file is executed outside the theme; it cannot use `$this` and cannot call theme templates.
- 404s do not go through this file; they are still output by the theme's `404.php`.
- When `__TYPECHO_DEBUG__` is on, Typecho outputs the error details and call stack directly and does not use this file. For debugging, see [Debugging and troubleshooting](../../../guide/operations/debugging.md#exception-file).
