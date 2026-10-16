---
title: Debugging and Troubleshooting
sidebar_position: 10
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Debugging and Troubleshooting

This page explains how to troubleshoot 500 errors, blank pages and broken features: look at the error message first, then narrow it down by "disabling plugins + switching back to the default theme".

## Typecho's error page

By default, when Typecho catches an unhandled exception, it shows an error page with only the status code and a short description (`error()` in `var/Typecho/Common.php`), without the specific cause. Database error messages are replaced with generic text to avoid leaking database details:

| Text on the page | Status code | Meaning |
|---|---|---|
| `Error establishing a database connection` | 503 | Cannot connect to the database: the database service is not running, the host or password in `config.inc.php` is wrong, or the SQLite file path does not exist or is not writable |
| `Database Query Error` | 500 | An SQL statement failed: commonly a missing table, or a table structure that does not match the program version (the upgrade was not completed) |
| `Database Server Error` | 500 | Other database errors |
| `Server Error` or another description | 500, etc. | An error thrown by the program, a plugin or a theme |

To see the specific cause, turn on debug mode or look at the PHP error log.

## Debug mode `__TYPECHO_DEBUG__` {#debug}

Add this at the end of `config.inc.php`:

```php
define('__TYPECHO_DEBUG__', true);
```

Once enabled, Typecho no longer uses the error page above (`var/Widget/Init.php`); uncaught exceptions directly output the error message and the full stack trace, for example:

```text
SQLSTATE[HY000] [14] unable to open database file
Typecho\Db\Adapter\ConnectionException: SQLSTATE[HY000] [14] unable to open database file in /srv/www/typecho/var/Typecho/Db/Adapter/Pdo.php:62
Stack trace:
#0 /srv/www/typecho/var/Typecho/Db.php(218): Typecho\Db\Adapter\Pdo->connect(Object(Typecho\Config))
...
```

Note:

- Debug mode only affects how **exceptions** are displayed. Whether PHP warnings and notices (Warning, Notice, Deprecated) appear on the page is determined by PHP's `display_errors`, independent of this constant.
- In debug mode, a page with an error returns status code **200**, not 500.
- The stack trace exposes server paths, class names and other information. **Remove this line as soon as you have found the problem**, and do not leave it on for long on a production site.

## Custom error page `__TYPECHO_EXCEPTION_FILE__` {#exception-file}

To make the error page match your site's style, specify a PHP file to replace the built-in error page:

```php
define('__TYPECHO_EXCEPTION_FILE__', __DIR__ . '/usr/error.php');
```

When an error occurs, Typecho `require_once`s this file, and these variables are available:

| Variable | Contents |
|---|---|
| `$code` | The HTTP status code, already set on the response |
| `$message` | The error description (with line breaks converted); for database errors it is also the replaced text from the table above |
| `$exception` | The exception object |

```php title="usr/error.php"
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><title><?php echo $code; ?></title></head>
<body>
  <h1>出错了（<?php echo $code; ?>）</h1>
  <p><?php echo $message; ?></p>
</body>
</html>
```

Do not output the contents of `$exception` in this file, for the same reason as with debug mode. `404` errors do not go through this file; they are displayed by the current theme's `404.php`. This constant has no effect when `__TYPECHO_DEBUG__` is enabled.

## Checking the logs {#logs}

When no error appears on the page (for example a blank page), the answer is usually in the logs.

| Log | Common location | Notes |
|---|---|---|
| PHP error log | Determined by PHP's `error_log` setting; PHP-FPM may also write to FPM's own log, such as `/var/log/php8.2-fpm.log` | Use `phpinfo()` to check the values of `error_log` and `log_errors` in the **web environment**. `php -i` on the command line shows the CLI configuration, which may differ from the web one |
| nginx error log | `/var/log/nginx/error.log` | FastCGI errors such as "Primary script unknown" and "upstream timed out" are here |
| Apache error log | `/var/log/apache2/error.log` (Debian / Ubuntu), `/var/log/httpd/error_log` (RHEL family) | `.htaccess` syntax errors, modules not loaded, etc. |
| Containers | `docker logs 容器名` | The official image sends PHP errors to standard error; see [Containers and cloud hosting](./containers.md) |

For services managed by `systemd`, you can also use `journalctl -u php8.2-fpm -u nginx --since "10 min ago"`.

To watch in real time:

```bash
tail -f /var/log/nginx/error.log /var/log/php8.2-fpm.log
```

Then reproduce the problem in the browser.

## Troubleshooting procedure: disable plugins, switch back to the default theme {#isolate}

500 errors, blank pages, admin buttons that stop working and similar problems that appear after an upgrade, after enabling a plugin or after switching themes are mostly caused by a plugin or theme. Narrow it down in this order:

1. **Disable all plugins**: in the admin panel, go to "Dashboard → Plugins" and click "Disable" on each one.
2. **Switch to a bundled theme**: in the admin panel, go to "Dashboard → Appearance" and enable `default` (or `classic-22`, new in 1.3).
3. Once the problem is gone, re-enable the plugins **one at a time**, testing after each, to find the one causing the problem; finally switch back to the original theme and test.
4. Once found: upgrade that plugin or theme to a version that supports 1.3, contact its author, or stop using it.

Plugin settings are stored in the database, so they are not lost when you disable and re-enable a plugin.

### When you cannot get into the admin panel: working through the database

If the error also makes the admin panel inaccessible, you can change the options directly in the database (use the table prefix in `config.inc.php`; the default is `typecho_`). **Back up the database first.** The statements below only use syntax supported by MySQL, PostgreSQL and SQLite.

**Disable all plugins at once** (the same as the initial values of a newly installed site):

```sql
UPDATE typecho_options SET value = 'a:0:{}' WHERE name = 'plugins'     AND user = 0;
UPDATE typecho_options SET value = '[]'     WHERE name = 'actionTable' AND user = 0;
UPDATE typecho_options SET value = '[]'     WHERE name = 'panelTable'  AND user = 0;
```

- `plugins` records the enabled plugins and the interfaces they hook into; the other two are the action URLs and admin menus registered by plugins. If you don't clear them, menu items left behind by plugins still show.
- Each plugin's settings (the `plugin:插件名` options) are not affected; re-enable the plugin in the admin panel later to get them back.

**Switch to the default theme**:

```sql
UPDATE typecho_options SET value = 'default' WHERE name = 'theme' AND user = 0;
```

:::warning Don't just delete the plugin directory
While a plugin is enabled, deleting or renaming its directory under `usr/plugins/` does not disable it: Typecho still calls the interfaces the plugin hooks into, and the related pages return 500. For example, if you enable the bundled `HelloWorld` plugin and then move its directory away, every admin page returns 500. Disable the plugin in the admin panel, or use the SQL above.
:::

## Quick reference for common problems

| Symptom | Look at first |
|---|---|
| All posts 404, No input file specified, 404 / 500 / 403 on login | [Web server configuration](./web-server.md#troubleshooting) |
| After an upgrade, the admin panel always redirects to the upgrade page, or "完成升级" (Complete upgrade) keeps loading | [Upgrade](../getting-started/upgrade.md), especially the known issue with more than 100 attachments |
| 503 `Error establishing a database connection` | Check the database service and the connection parameters in `config.inc.php`; see [config.inc.php and constants](./config-file.md) |
| Missing styles, mixed content, sent back to the login page after logging in | [HTTPS, reverse proxies and CDNs](./https-proxy.md) |
| Uploading attachments fails | Whether `usr/uploads/` is writable, and PHP's `upload_max_filesize`; see [Attachment storage location](./uploads-storage.md) |
| Wrong times displayed | The time zone under "Settings → General" in the admin panel |

If you still can't solve it, ask for help with the error messages and logs collected on this page; see [Getting help](../help/getting-help.md).
