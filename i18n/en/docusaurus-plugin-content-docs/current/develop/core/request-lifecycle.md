---
title: Request Lifecycle
sidebar_position: 2
translation: machine
source_commit: 1d967a0c62b5a260a8146b63efb3c9323aa91cc3
---

# Request Lifecycle

This page walks through, in execution order, the steps a front-end request goes through from entering `index.php` to producing a response, and why admin pages take a different path.

## Overview

```text
index.php
 ├─ include config.inc.php
 │   ├─ require var/Typecho/Common.php   register the autoloader
 │   ├─ \Typecho\Common::init()          output buffering + basic exception handling
 │   └─ new \Typecho\Db(...); Db::set()  database object (not connected yet)
 ├─ \Widget\Init::alloc()                9-step initialization
 ├─ Plugin::factory('index.php')->call('begin')
 ├─ \Typecho\Router::dispatch()          match the route, run the widget
 └─ Plugin::factory('index.php')->call('end')
```

## 1. Loading the configuration file

The first thing `index.php` does is `include_once 'config.inc.php'`. If the file does not exist, it redirects to the installer if `install.php` is still present; otherwise it outputs `Missing Config File` and exits.

The configuration file does three things in order:

1. `require_once` loads `var/Typecho/Common.php`. Loading this file registers the autoloader and defines the global functions `_t()`, `_e()` and `_n()`;
2. It calls `\Typecho\Common::init()`;
3. It creates the `\Typecho\Db` object, adds the server and calls `Db::set($db)`. The database is not connected yet; the connection is made on the first query.

## 2. Common::init()

`Common::init()` (`var/Typecho/Common.php`) only does preparation:

- It turns off automatic header sending in `Response` and instead starts output buffering with `ob_start()`; when the buffer is first flushed, a callback calls `Response::sendHeaders()`. As a result, widgets can still set the status code, headers and cookies after page output has started;
- It sets a minimal exception handler that outputs the exception message and the stack trace. It only takes effect when the friendly error page of the next step does not take over (for example when debug mode is on, or when the exception happens before `Widget\Init`).

## 3. The 9 steps of Widget\Init

`\Widget\Init::alloc()` creates `Widget\Init` and runs its `execute()` (`var/Widget/Init.php`), in this order:

| Step | What it does | Notes |
|---|---|---|
| 1 | Sets the friendly exception handler | Only when `__TYPECHO_DEBUG__` is not defined or is false. Exceptions with code 404 go to `Widget\ExceptionHandle`, which renders the current theme as a 404 page (using `404.php` if the theme has one); other exceptions go to `Common::error()`, which shows an error page |
| 2 | Defines `__TYPECHO_CLASS_ALIASES__` | The legacy class name alias table; see the [legacy class name table](../reference/class-aliases.md) |
| 3 | `Widget\Options::alloc()` | The first database query; reads all site options |
| 4 | Loads the language pack | When the `lang` option is not empty and not `zh_CN`, reads `usr/langs/<lang>.mo` (or the directory given by `__TYPECHO_LANG_DIR__`) |
| 5 | Backup directory | Defines `__TYPECHO_BACKUP_DIR__` as `usr/backups` if it is not defined |
| 6 | Cookies | Sets the cookie prefix from the site root URL; sets cookie options when `__TYPECHO_COOKIE_OPTIONS__` is defined |
| 7 | Routes and plugins | `Router::setRoutes($options->routingTable)` loads the routing table; `Plugin::init($options->plugins)` loads the hooks registered by enabled plugins |
| 8 | Response and time zone | Sets the response charset and `Content-Type`, and sets the time zone offset |
| 9 | Session | Calls `session_start()` when the site is installed and the current user is logged in |

A few notes:

- Plugin hooks only take effect after step 7, so plugins cannot intervene in the first 6 steps;
- Step 9 starts a session for **every** request from a logged-in user, including front-end page visits; no session is started for visitors who are not logged in;
- For how to use the constants, see [Configuration file](../../guide/operations/config-file.md).

## 4. index.php:begin

```php
\Typecho\Plugin::factory('index.php')->call('begin');
```

Runs before route dispatching, with no arguments. At this point options, language and plugins are all ready, but it is not yet known which route the request will hit.

## 5. Router::dispatch() {#dispatch}

`Router::dispatch()` (`var/Typecho/Router.php`):

1. Gets the request path (`Request::getPathInfo()`, the part after the site root URL and before `?`, without `/index.php`);
2. Matches the path against each route's compiled regular expression, in routing table order;
3. For the first matching route, builds a parameter array from the placeholders captured by the regular expression and calls `Widget::widget($route['widget'], null, $params)` to create the handler class. These parameters become the widget's request parameters and take precedence over GET/POST parameters of the same name;
4. If the route defines an `action`, it then calls `$widget->{action}()`, for example `render()` of `Widget\Archive`;
5. If creating the widget or running the `action` throws an exception with code 404, the exception is discarded and the **following** matching routes are tried;
6. When all matching routes fail or no route matches, a `Typecho\Router\Exception` with code 404 is thrown, and the handler from step 1 of section 3 shows the 404 page.

Taking `/archives/999/` (a post that does not exist) as an example, the following happens in turn:

```text
pathInfo = /archives/999/
  match post       → Widget\Archive, cid=999 → 404, continue
  match feedback   → Widget\Feedback, permalink=/archives, type=999 → 404, continue
  no more matches  → throw 404
```

For the structure of the routing table, the rule syntax and reverse URL generation, see [Routing](./router.md).

## 6. index.php:end

```php
\Typecho\Plugin::factory('index.php')->call('end');
```

Runs only when `dispatch()` returns normally. `end` does **not** run in any of these cases:

- A 404 page or an error page is shown (the exception handler ends by calling `exit`);
- The widget calls a response method such as `redirect()`, `goBack()` or `throwJson()`; these methods `exit` right after output; see [Request, Response and common utilities](./request-response.md#response). Most `/action/*` requests end in one of these methods, so `end` does not run for them; but when an action returns normally (for example a GET request to `/action/upload`, which only sets status 403 and returns), `end` runs as usual.

If you need to do something at the end of every request, you cannot rely on `index.php:end` alone.

## 7. Output

Page content is `echo`ed into the output buffer while the code runs. When the script ends (or the code flushes the buffer itself), the callback first sends the status code, headers and cookies, and then outputs the content.

## Admin pages do not go through the router

Each admin page is a standalone PHP file under `admin/`, executed directly by the web server; the request never passes through `index.php`, so:

- There is no route matching, and `index.php:begin` and `index.php:end` are not triggered;
- An admin page does `include 'common.php'` on its first line, and `admin/common.php` performs the initialization.

The steps of `admin/common.php`:

1. Defines the constant `__TYPECHO_ADMIN__` (`User::pass()` uses it to decide whether a user who is not logged in is redirected to the login page or to the home page);
2. Loads `config.inc.php` and runs `\Widget\Init::alloc()`, the same process as on the front end;
3. Triggers the hook `admin/common.php:begin`;
4. Creates `Widget\Options`, `Widget\User`, `Widget\Security` and `Widget\Menu`, assigns them to `$options`, `$user`, `$security` and `$menu`, and prepares `$request` and `$response`;
5. When it is created, `Widget\Menu` finds the menu item for the current page and calls `$user->pass()` with the role that menu item requires: users who are not logged in are redirected to the login page, and users without sufficient permission see a 403; see [Permission model](./permissions.md);
6. Users logging in for the first time are directed to `welcome.php`; administrators are directed to `upgrade.php` when the program version is higher than the version recorded in the database; see [Writing upgrade scripts](./upgrade-scripts.md).

After that, the page template decides which widgets to create and what to output. Form submissions from the admin panel go to `/action/<name>`, which brings them back to `index.php` and the router (the `do` route).

## Observing a request yourself

Registering callbacks on `index.php:begin` and `index.php:end` shows whether a request went all the way through:

```php title="usr/plugins/RequestTrace/Plugin.php"
<?php

namespace TypechoPlugin\RequestTrace;

use Typecho\Plugin\PluginInterface;
use Typecho\Router;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Logs the stages a request goes through to the PHP error log
 *
 * @package RequestTrace
 * @version 1.0.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('index.php')->begin = __CLASS__ . '::begin';
        \Typecho\Plugin::factory('index.php')->end = __CLASS__ . '::end';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function begin()
    {
        error_log('begin ' . ($_SERVER['REQUEST_URI'] ?? ''));
    }

    public static function end()
    {
        error_log('end, route = ' . Router::$current);
    }
}
```

After enabling it, visit the home page, a post and a URL that does not exist, then click **Logout** in the admin panel (`/action/logout`, which ends with a redirect); only the first two requests show both `begin` and `end` in the log. `Router::$current` is the name of the last matched route.
