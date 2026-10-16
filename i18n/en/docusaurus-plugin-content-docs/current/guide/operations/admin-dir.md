---
title: Custom Admin Path
sidebar_position: 5
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Custom Admin Path

Typecho's admin panel is at `/admin/` by default. Renaming it blocks part of the automated scanning and brute-force attempts aimed at `/admin/login.php`. Two things are needed: **change the constant** and **rename the directory**.

After renaming, `/admin/` returns 404, and login, the dashboard and the settings pages are all used under the new path.

## Steps

Taking `/manage-x7/` as an example.

1. Edit `config.inc.php` in the site root and find this line generated at installation:

   ```php
   define('__TYPECHO_ADMIN_DIR__', '/admin/');
   ```

   Change it to:

   ```php
   define('__TYPECHO_ADMIN_DIR__', '/manage-x7/');
   ```

2. Rename the `admin` directory in the site root to the same name:

   ```bash
   cd /srv/www/typecho
   mv admin manage-x7
   ```

3. Log in at `https://your-domain/manage-x7/`.

Both steps must be done together: if you only change the constant, admin links point to a directory that does not exist; if you only rename the directory, the URLs generated in admin pages still point to `/admin/`.

## Rules

| Rule | Reason |
|---|---|
| The new directory must sit directly in the site root, one level deep | Admin pages load the configuration with `__DIR__ . '/../config.inc.php'` (`admin/common.php`); one level deeper and the configuration file cannot be found |
| The constant's value matches the directory name, written as `/directory-name/` | Typecho uses it to build admin URLs (`___adminUrl()` in `var/Widget/Options.php`), and in admin pages it strips this segment from the end of the current URL to compute the site root URL (`___rootUrl()`). If the two do not match, admin links and form URLs break |
| Use only letters, digits and hyphens in the name | Avoids characters that need URL encoding |

## After the change

- **The login form's submission URL does not change**. Whatever the admin directory is called, login submits to `/index.php/action/login`; changing the admin path only hides the login page and is no substitute for a strong password.
- **Themes may expose the new path**. For example, the bundled `default` theme shows a "Login" link in the "Other" block of the sidebar (the theme setting "Show miscellaneous" (显示其它杂项)), and that link is the new admin URL. If you do not want it public, untick this item in the theme settings.
- **When upgrading, replace the directory you named yourself**. When [upgrading](../getting-started/upgrade.md), overwrite `manage-x7/` with the new version's `admin/`: delete `manage-x7` first, then copy `admin` from the new package and rename it to `manage-x7`. Do not put the new `admin/` straight into the site directory, or you will end up with two sets of admin files.
- **Rules for `/admin/` in the web server or CDN** (no caching, access restrictions and so on) must be changed to the new path as well.
- Third-party plugins or themes that hard-code `/admin/` stop working after the rename. Typecho's own code does not hard-code the admin path (confirmed by searching the source); plugins and themes need to be checked individually.

## Restoring the default

Change the constant back to `'/admin/'` and rename the directory back to `admin`.
