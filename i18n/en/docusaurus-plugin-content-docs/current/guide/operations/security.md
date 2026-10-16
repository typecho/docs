---
title: Security Hardening
sidebar_position: 9
translation: machine
source_commit: 0dccf9f7706701fc8b129b2d5f8efd9c36c511de
---

# Security Hardening

This page lists the security settings worth applying after deploying Typecho, in order of importance. It applies to Typecho 1.3.

## 1. Upgrade promptly {#upgrade}

This is the most effective measure. When a new version is released, the admin dashboard shows a "官方最新版本是 …" (the latest official version is …) notice; you can also follow [GitHub Releases](https://github.com/typecho/typecho/releases). For the upgrade steps, see [Upgrade](../getting-started/upgrade.md).

Themes and plugins need updating too. Avoid plugins that have gone unmaintained for a long time and do not state support for 1.3 whenever you can.

## 2. Reporting security vulnerabilities {#report}

The `SECURITY.md` of the Typecho repository specifies: report vulnerabilities by email to **security@typecho.org**.

Do not post vulnerability details on the forum, in GitHub Issues or in any other public place; disclosing details before a fixed version is released puts every site at risk. For vulnerabilities in third-party themes or plugins, contact their authors.

## 3. Remove the installer after installation {#remove-installer}

Once installation is complete, `install.php` and the `install/` directory are no longer needed: neither the Typecho runtime nor the admin upgrade program uses them.

```bash
cd /srv/www/typecho
rm -rf install.php install/
```

After the site is installed, visiting `install.php` redirects straight to the home page and cannot reinstall. Removing it guards against accidents: the installer decides "already installed or not" based on the configuration file and the database tables (`install_check()` in `install.php`). If the tables are deleted by mistake or the database is emptied, it considers the site not installed, and a visitor could use it to recreate the tables in your database and create an administrator.

When you [upgrade](../getting-started/upgrade.md), the new release package brings these two items back; simply don't copy them.

## 4. File permissions {#permissions}

At runtime Typecho only needs to write to very few locations (for the detailed list, see [Containers and cloud hosting](./containers.md#read-only)); every other file should be **read-only** for the PHP process. That way, even if a vulnerability appears, an attacker has a hard time rewriting program files.

| Path | PHP process needs | Recommendation |
|---|---|---|
| `usr/uploads/` | Read and write | Owned by the PHP process user (such as `www-data`), directory `755` |
| The SQLite database file and its directory | Read and write | Same as above; preferably outside the site directory, see the next section |
| `config.inc.php` | Read only | `640`, owned by the deploy user, group set to the PHP process's group |
| All other files and directories | Read only | Owned by the deploy user, directories `755`, files `644` |

An example (deploy user `deploy`, PHP running as `www-data`):

```bash
cd /srv/www/typecho
chown -R deploy:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 640 config.inc.php
chown -R www-data:www-data usr/uploads
```

This setup has two effects:

- The theme editor in the admin panel cannot save files. This is exactly the recommended state; see [item 6](#theme-editor);
- When you enable permalinks under Apache `mod_php`, Typecho cannot write `.htaccess` automatically, so you need to create it by hand; see [Web server configuration](./web-server.md#htaccess).

Approaches such as "chmod 777 on every file" or "chmod 644 is enough" do not apply: the former lets PHP rewrite everything, the latter makes uploads fail.

## 5. Keep sensitive files from being downloaded {#sensitive-files}

| File | Risk | What to do |
|---|---|---|
| SQLite database (placed under `usr/` by default at installation, with a random file name and the `.db` extension) | If the file name is guessed or leaked, the whole database can be downloaded directly, including users' password hashes | Put the database file outside the site directory at installation (for command-line installation, specify an absolute path with `TYPECHO_DB_FILE`); for sites already installed, deny access to `.db` files on the web server |
| `.dat` backups in `usr/backups/` | Contain all content and user data | Delete them after restoring, or deny access to this directory; you can also move the backup directory outside the site directory with `__TYPECHO_BACKUP_DIR__` |
| Scripts in `usr/uploads/` | Once an executable script is uploaded, it may be executed | Disallow executing PHP in the upload directory |
| Copies of `config.inc.php` (such as files ending in `.bak`, `.swp` or `~`) | Expose the database password in plain text | Don't leave such files in the site directory |

The corresponding rules for nginx, Apache and Caddy are in [Web server configuration](./web-server.md); the sample configurations there already cover the first three items.

## 6. Disable the online theme editor {#theme-editor}

"Dashboard → Appearance → Edit current theme" (控制台 → 外观 → 编辑当前外观) in the admin panel can modify a theme's `.php` files directly. Once an administrator account is stolen, an attacker can use it to execute arbitrary code on the server. If you don't need to edit themes online, disable it in `config.inc.php`:

```php
define('__TYPECHO_THEME_WRITEABLE__', false);
```

Once disabled, the "Edit current theme" tab on the "Appearance" page no longer appears, accessing the editor page directly returns 404, and saving through a form submission is not possible either (`var/Widget/Themes/Files.php`, `var/Widget/Themes/Edit.php`).

## 7. Custom admin path {#admin-path}

Renaming `/admin/` to something else reduces scans and brute-force attempts against the default path; see [Custom admin path](./admin-dir.md) for how. This only reduces noise: the login form still submits to the fixed `/index.php/action/login`, and a theme may also show the admin link on its pages.

## 8. Accounts and feature switches

- **Use strong passwords**, and give each administrator their own account instead of sharing one. Typecho waits 3 seconds before responding to a failed login (`var/Widget/Login.php`), but there is no limit on failed attempts and no two-factor authentication. If needed, rate-limit the login URL `/index.php/action/login` on the web server, or use a plugin (Typecho provides the `loginFailure` plugin interface).
- **Turn off registration when you don't need it**: "Settings → General → Allow registration" (设置 → 基本 → 是否允许注册). With registration open, new users are "subscribers" (`subscriber`) by default and have no write permission.
- **Turn off XML-RPC when you don't use offline clients**: "Settings → General → XMLRPC interface" (设置 → 基本 → XMLRPC 接口), with the options "Off" (关闭), "Disable only the Pingback interface" (仅关闭 Pingback 接口) and "On" (打开).
- **Allow only the attachment types you need**: the allowed upload file types under "Settings → General". Typecho judges only by an extension whitelist; do not add executable types or types that browsers parse as web pages, such as `php`, `phtml`, `phar`, `html` or `htm`, to the custom types. SVG files can also contain scripts, so be careful with them as well.

## 9. HTTPS and cookies {#https}

- Enable HTTPS for the whole site. Behind a reverse proxy or CDN, make sure Typecho recognizes HTTPS correctly; see [HTTPS, reverse proxies and CDNs](./https-proxy.md).
- When the whole site uses HTTPS only, add the `Secure` and `HttpOnly` attributes to the login cookie with `__TYPECHO_COOKIE_OPTIONS__`; see [the same page](./https-proxy.md#cookie-options).
- On a site without a proxy, visitors can forge the `X-Forwarded-For` request header to change the IP Typecho records; see [Client IP](./https-proxy.md#ip-source).

## 10. Backups {#backup}

After a compromise, the most reliable way to recover is a clean backup. Make complete backups regularly as described in [Backup and restore](./backup.md), and keep them outside the web server.

After a compromise: take the site offline first; redeploy the program files from the official release package instead of reusing the old files on the server; check the themes, plugins and upload directory under `usr/` for suspicious PHP files; change all administrator passwords and the database password; then upgrade to the latest version.
