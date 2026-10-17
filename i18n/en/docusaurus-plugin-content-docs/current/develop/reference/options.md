---
title: $this->options
sidebar_position: 5
translation: machine
source_commit: 1d967a0c62b5a260a8146b63efb3c9323aa91cc3
---

# $this->options

`$this->options` is a `Widget\Options` object that holds all of the site's settings. In templates, use `$this->options` directly; in `functions.php` or in a plugin, use `\Widget\Options::alloc()` to get the same object.

## Where the values come from

`$this->options->xxx` merges values from three places, in order:

1. Site-wide settings in the database `options` table with `user = 0`; for names and defaults see [Option names](./option-names.md);
2. Settings of the current theme (the JSON stored in `theme:<theme directory name>`), which **override site-wide settings of the same name**; see [Theme settings](../theme/theme-options.md);
3. Personal settings of the currently logged-in user (rows with `user = <user ID>`, for example `markdown`, `autoSave`, `defaultAllowComment`), merged only when the user is logged in, overriding the previous two.

There are also the magic properties in the tables below, which Typecho computes, for example various URLs.

## Reading and outputting

```php
<?php echo $this->options->title; ?>   <!-- get the value -->
<?php $this->options->title(); ?>      <!-- output directly; same as above -->
<?php $this->options->siteUrl('archives/'); ?>   <!-- method with an argument -->
```

Calling a method with the name of any option outputs its value directly (this is `Typecho\Config` behavior, with no HTML escaping). The methods in the table below have their own parameters; the table describes their behavior.

## Methods

| Method | Description |
|---|---|
| `adminStaticUrl(string $type, ?string $file = null, bool $return = false)` | Get or output the path of an admin static file |
| `adminUrl(?string $path = null, bool $return = false)` | Output the admin path |
| `commentsHTMLTagAllowed()` | Output, encoded, the HTML tags allowed in comments |
| `execute()` | Execute function |
| `index(?string $path = null)` | Output the resolved URL |
| `personalPlugin($pluginName)` | Get a plugin's personal settings |
| `plugin($pluginName)` | Get a plugin's settings |
| `pluginDir(?string $plugin = null): string` | Get the plugin directory |
| `pluginUrl(?string $path = null)` | Output the plugin path |
| `siteUrl(?string $path = null)` | Output the site path |
| `themeFile(string $theme, string $file = ''): string` | Get a theme file |
| `themeUrl(?string $path = null, ?string $theme = null)` | Output the theme path |

- `siteUrl($path)`, `index($path)`, `themeUrl($path)` and `pluginUrl($path)` **output** the assembled URL directly; `themeUrl($path, $theme)` with the second argument instead **returns** the URL of a file in another theme's directory.
- The difference between `index($path)` and `siteUrl($path)`: when URL rewriting is off, `index` includes `index.php` (`https://example.com/index.php/archives/1/`), so use it to build links to pages; `siteUrl` is always the site root URL, suitable for static files.
- For `adminUrl($path, $return = false)` and `adminStaticUrl($type, $file, $return = false)`, the method returns the value when the last argument is `true` and outputs it otherwise.
- `plugin($pluginName)` returns a plugin's site-wide settings (`Typecho\Config`), for example `$this->options->plugin('HelloWorld')->word`. If the plugin is **not enabled or has no settings**, it throws `Typecho\Plugin\Exception` ("the configuration of plugin X was not found"); you can wrap the read in `try`. `personalPlugin($pluginName)` returns the current user's personal plugin settings.
- `themeFile($theme, $file)` and `pluginDir($plugin)` return absolute paths on the server.

## Magic properties

| Property | Type | Description |
|---|---|---|
| `actionTable` | `array` | Registered actions (added by plugins via `Helper::addAction()`) |
| `adminUrl` | `string` | Get the admin path |
| `allowedAttachmentTypes` | `array` | File types allowed for upload |
| `commentsFeedAtomUrl` | `string` | Comments Atom 1.0 feed |
| `commentsFeedRssUrl` | `string` | Comments RSS 1.0 feed |
| `commentsFeedUrl` | `string` | Comments RSS 2.0 feed |
| `contentType` | `string` | Get the content type |
| `feedAtomUrl` | `string` | Atom 1.0 |
| `feedRssUrl` | `string` | RSS 1.0 |
| `feedUrl` | `string` | RSS 2.0 |
| `gmtTime` | `int` | Get the GMT time |
| `index` | `string` | Get the prefix for resolved paths |
| `loginAction` | `string` | Get the login submit URL |
| `loginUrl` | `string` | Get the login URL |
| `logoutUrl` | `string` | Get the logout URL |
| `missingTheme` | `?string` | Dynamically determine the theme directory |
| `originalSiteUrl` | `string` | The site URL actually in use: the site URL saved in the settings; the constant `__TYPECHO_SITE_URL__` when it is defined, or `rootUrl` when `__TYPECHO_DYNAMIC_SITE_URL__` is enabled. `siteUrl` is normalized from it, with `http://` changed to `https://` on HTTPS requests |
| `panelTable` | `array` | Admin panels registered by plugins |
| `pluginDir` | `string` | Absolute path of the plugin directory |
| `plugins` | `array` | Enabled plugins and the hooks they registered |
| `pluginUrl` | `string` | Get the plugin path |
| `profileUrl` | `string` | Get the profile URL |
| `registerAction` | `string` | Submit URL of the registration form, with a security token |
| `registerUrl` | `string` | Get the registration URL |
| `rootUrl` | `string` | Dynamically get the root directory |
| `routingTable` | `array` | The parsed routing table |
| `serverTimezone` | `int` | Get the server time zone |
| `siteDomain` | `string` | Host name in the site URL |
| `siteUrl` | `string` | Site URL (with a trailing `/`) |
| `software` | `string` | Software name |
| `theme` | `string` | Directory name of the current theme; `default` if the directory does not exist |
| `themeUrl` | `string` | Get the theme path |
| `time` | `int` | Get the time |
| `version` | `string` | Software version |
| `xmlRpcUrl` | `string` | XML-RPC API URL |

Commonly used URL properties (typical values with URL rewriting on):

| Property | Example value |
|---|---|
| `siteUrl` | `https://example.com/` |
| `index` | `https://example.com` (`https://example.com/index.php` when rewriting is off) |
| `themeUrl` | `https://example.com/usr/themes/starter` |
| `adminUrl` | `https://example.com/admin/` |
| `feedUrl` / `feedRssUrl` / `feedAtomUrl` | `https://example.com/feed/`, `…/feed/rss/`, `…/feed/atom/` |
| `commentsFeedUrl` | `https://example.com/feed/comments/` |
| `xmlRpcUrl` | `https://example.com/action/xmlrpc` |
| `loginUrl` / `registerUrl` / `profileUrl` | `https://example.com/admin/login.php` and so on |
| `logoutUrl` | Logout URL with a security token |

Others: `time` is the current timestamp, `timezone` is the site time zone's offset from GMT in seconds, `version` is the Typecho version number (such as `1.3.0`), `software` is `Typecho`, and `theme` is the current theme's directory name (`default` if the directory does not exist, with `missingTheme` holding the name of the missing directory).

You can read ordinary options from the database (`title`, `description`, `pageSize`, `commentsThreaded` and so on) the same way; for the full list see [Option names](./option-names.md). `plugins`, `routingTable`, `actionTable` and `panelTable` read as decoded arrays.
