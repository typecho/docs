---
title: 404 and Error Pages
sidebar_position: 6
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# 404 and Error Pages

Typecho has two kinds of "error pages": **page not found** (HTTP 404) is output by the theme; **other errors** (the database cannot be reached, a template hits a fatal error, and errors raised by Typecho components such as a rejected comment, with HTTP 403, 500, 503 and so on) are output by Typecho itself, and you can replace that page with your own using `__TYPECHO_EXCEPTION_FILE__`.

## The theme's 404.php

**Problem**: when a URL that does not exist is requested, show a 404 page styled like the rest of the site.

**Solution**: create `404.php` in the theme directory:

```php
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <h1>404</h1>
    <p><?php _e('Page not found'); ?></p>
    <form method="post" action="<?php $this->options->siteUrl(); ?>">
        <input type="search" name="s" placeholder="<?php _e('Search'); ?>">
    </form>
    <p><a href="<?php $this->options->siteUrl(); ?>"><?php _e('Back to home'); ?></a></p>
</main>
<?php $this->need('footer.php'); ?>
```

**Result**: URLs that do not exist, unpublished content, and private content the visitor is not allowed to view all use this template; the HTTP status code is 404 and the archive title is "Page not found". Password-protected posts and pages are an exception: until the visitor enters the password, the normal post or page template shows the password form, with status code 403.

**How it works**: on a 404, Typecho assigns the theme file `404.php` directly. When the theme does not have this file, it looks for `archive/404.php`, `archive.php`, `index.php` in that order (see [Template hierarchy](../template-hierarchy.md)), and the status code is still 404. In a template you can check whether the current page is the 404 page with `$this->is('archive', '404')`.

When `__TYPECHO_DEBUG__` is on, a URL that matches no route does not show `404.php`; instead the exception message and call stack are output directly (with status code 200). Turn it off when you finish debugging to restore the normal behavior.

## The page shown on application errors: `__TYPECHO_EXCEPTION_FILE__`

**Problem**: on errors such as a failed database connection, you do not want to show Typecho's default error page.

**Solution**: create a PHP file, for example `/var/www/error.php` outside the web root:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site temporarily unavailable</title>
</head>
<body>
    <h1>Site temporarily unavailable</h1>
    <p>Error code <?php echo intval($code); ?>. Please try again later.</p>
</body>
</html>
```

Then specify it in `config.inc.php`:

```php
define('__TYPECHO_EXCEPTION_FILE__', '/var/www/error.php');
```

**Result**: on application errors, Typecho no longer outputs its built-in error page but executes this file instead.

**How it works**:

- Before executing this file, Typecho has already set the HTTP status code. Three variables are available in the file: `$code` (the status code), `$message` (the error message) and `$exception` (the exception object). `$message` depends on the kind of error: database errors are replaced with fixed text such as `Database Server Error` or `Error establishing a database connection`; errors raised by Typecho components (such as the 403 for a rejected comment) keep their original message; other application errors are replaced with `Server Error`. `$message` only has its line breaks converted to `<br />` and is not HTML-escaped, so escape it yourself if you output it; `$exception`, however, holds the full error message and call stack, which may contain internal information such as the database address and file paths, so **do not output it to visitors**.
- This file is executed outside the theme; it cannot use `$this` and cannot call theme templates.
- 404s do not go through this file; they are still output by the theme's `404.php`.
- When `__TYPECHO_DEBUG__` is on, Typecho outputs the error details and call stack directly and does not use this file. For debugging, see [Debugging and troubleshooting](../../../guide/operations/debugging.md#exception-file).
