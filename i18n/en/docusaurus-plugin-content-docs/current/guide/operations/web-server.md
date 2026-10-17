---
title: Web Server Configuration
sidebar_position: 1
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Web Server Configuration

Every dynamic request in Typecho is handled by `index.php` in the site root. The web server has two jobs:

1. **Pass PHP requests to PHP**, including URLs that carry a path after the script, such as `index.php/xxx` (the PATH_INFO form). The admin login, saving settings, uploading attachments and other forms all submit to `index.php/action/...`, so this must work **whether or not permalinks are enabled**;
2. **When permalinks (URL rewriting) are enabled**, pass every request for a file or directory that does not exist to `index.php`, so that URLs such as `/archives/1/` can be opened.

This page gives the current configuration for nginx, Apache and Caddy 2. Configurations written for GoDaddy hosting, Caddy v1 and the nginx 0.7 era are outdated; do not use them.

## What Typecho needs from the server

Typecho does not rely on the `PATH_INFO` variable passed by the server. It computes the path itself from `REQUEST_URI` and `SCRIPT_NAME`/`SCRIPT_FILENAME` (`getPathInfo()` and `getBaseUrl()` in `var/Typecho/Request.php`). The key points of the configuration are therefore:

| Requirement | Details |
|---|---|
| `SCRIPT_FILENAME` points to the real `index.php` | For a request to `/index.php/action/login`, the script handed to PHP must be `/site-root/index.php`, not the non-existent `/site-root/index.php/action/login`. By default (`cgi.fix_pathinfo=1`) PHP corrects such paths itself; with `0` you get "No input file specified." or 403 "Access denied.", see [Common errors](#no-input-file) |
| `REQUEST_URI` keeps the original URL | When a permalink is forwarded to `index.php`, `REQUEST_URI` must still be the `/archives/1/` the user requested. nginx's `fastcgi_params`, Apache and Caddy all do this by default |
| `/index.php/...` reaches PHP | Login, comments and saving settings all POST to `/index.php/action/...`. If these URLs are not passed to PHP, login fails with 404, 500 or 403, see [Login fails](#login-fails) |

## nginx {#nginx}

nginx runs PHP through PHP-FPM. Below is a complete `server` block; replace the PHP-FPM address with the actual value on your server (common ones are `unix:/run/php/php8.2-fpm.sock` or `127.0.0.1:9000`):

```nginx
server {
    listen 80;
    server_name blog.example.com;
    root /srv/www/typecho;
    index index.php index.html;

    # Permalinks: hand the request to index.php when no such file or directory exists
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # Block downloads of SQLite database files and backup files on the server
    location ~ ^/usr/(.+\.db$|backups/) {
        deny all;
    }

    # Never execute PHP files in the upload directory
    location ~ ^/usr/uploads/.*\.php$ {
        deny all;
    }

    # Matches both /index.php and addresses such as /index.php/action/login
    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        if (!-f $document_root$fastcgi_script_name) {
            return 404;
        }

        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

Key points:

- **Preferably do not write the PHP `location` as `location ~ \.php$`**. That form only matches URLs ending in `.php`, so `/index.php/action/login` falls into `location /`. If `location /` has the `try_files ... /index.php$is_args$args` fallback shown above, the request still reaches `index.php` and login works; without that fallback (permalinks not enabled, or written as `try_files $uri $uri/ =404`), login returns **404**.
- The PHP `location` above works both with PHP's default `cgi.fix_pathinfo` of `1` and with the hardened `0`.
- `fastcgi_split_path_info` splits `/index.php/action/login` into the script `/index.php` and the path `/action/login`, so that `SCRIPT_FILENAME` can point to the real file.
- nginx matches regex `location` blocks in the order they appear, so the two `deny` rules must come **before** the PHP `location`.
- The `fastcgi_params` shipped with Debian / Ubuntu does not include `SCRIPT_FILENAME`, which is why it is set separately above. If you use the distribution's `snippets/fastcgi-php.conf`, it already contains `fastcgi_split_path_info` and `PATH_INFO`; do not set them again.
- Do not replace `try_files` with the old, widely copied `if (!-e $request_filename) { rewrite ^(.*)$ /index.php$1 last; }`. Combined with `location ~ \.php$`, it rewrites `/index.php/action/login` over and over into `/index.php/index.php/...` and returns **500** (log: `rewrite or internal redirection cycle`).
- When the site is installed in a subdirectory (for example `/blog/`), change `location /` to `location /blog/`, and change the last item of `try_files` to `/blog/index.php$is_args$args`.

After editing, check the syntax with `nginx -t`, then run `nginx -s reload` (or `systemctl reload nginx`).

## Apache {#apache}

Apache needs `mod_rewrite` enabled and must allow `.htaccess` in the site directory:

```apache
<VirtualHost *:80>
    ServerName blog.example.com
    DocumentRoot /srv/www/typecho

    <Directory /srv/www/typecho>
        AllowOverride All
        Require all granted
    </Directory>

    # With PHP-FPM (mod_proxy_fcgi); not needed with mod_php
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

On Debian / Ubuntu, enable the module with `a2enmod rewrite`; with PHP-FPM you also need `a2enmod proxy_fcgi`.

### .htaccess {#htaccess}

Create `.htaccess` in the site root (next to `index.php`):

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php/$1 [L]
</IfModule>
```

This is exactly what Typecho tries to write automatically when you enable permalinks in the admin panel (`var/Widget/Options/Permalink.php`). When the site is installed in a subdirectory, replace the `/` in `RewriteBase /` and `/index.php/$1` with the subdirectory, for example `/blog/` and `/blog/index.php/$1`.

If this form produces "No input file specified." (which happens in some FastCGI / CGI environments), use the compatible form below instead. It is also the second form Typecho tries automatically when the first one fails the check:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

It is also a good idea to put a `.htaccess` in `usr/uploads/` that prevents PHP files in the upload directory from being executed:

```apache
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
```

## Caddy 2 {#caddy}

Caddy 2's `php_fastcgi` directive already does what Typecho needs: it splits the path at `.php`, falls back to `index.php` when a file does not exist, and keeps the original `REQUEST_URI`.

```caddy
blog.example.com {
    root * /srv/www/typecho

    # Block downloads of SQLite database files, backup files on the server and PHP files in the upload directory
    @blocked path_regexp ^/usr/(.+\.db$|backups/|uploads/.*\.php$)
    respond @blocked 403

    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server
}
```

The Caddy v1 forms `rewrite { to ... }` and `fastcgi / ... php` no longer exist in Caddy 2. The configuration above is indented with 4 spaces, so `caddy validate` reports `Caddyfile input is not formatted`; this does not affect how it works.

### When PHP sets `cgi.fix_pathinfo=0` {#caddy-fix-pathinfo}

`cgi.fix_pathinfo=0` is a common PHP hardening recommendation. With this setting under Caddy, **every `/index.php/...` URL returns 403 `Access denied.`** and you cannot log in to the admin panel.

The cause: `php_fastcgi` passes `PATH_TRANSLATED = site root + PATH_INFO`; for a request to `/index.php/action/login` that is `/srv/www/typecho/action/login`. With `cgi.fix_pathinfo=0`, PHP-FPM treats it as the script path, and because it does not end in `.php`, `security.limit_extensions` rejects it. The PHP-FPM log shows:

```text
NOTICE: Access to the script '/srv/www/typecho/action/login' has been denied (see security.limit_extensions)
```

The fix: add two lines before `php_fastcgi` that internally rewrite `/index.php/...` to `/index.php`. The `REQUEST_URI` Caddy passes to PHP is still the original URL, and Typecho computes the path from it, so nothing else is affected:

```caddy
blog.example.com {
    root * /srv/www/typecho

    # Block downloads of SQLite database files, backup files on the server and PHP files in the upload directory
    @blocked path_regexp ^/usr/(.+\.db$|backups/|uploads/.*\.php$)
    respond @blocked 403

    # Hand addresses such as /index.php/action/login straight to index.php (works with cgi.fix_pathinfo=0)
    @pathinfo path_regexp ^/index\.php/
    rewrite @pathinfo /index.php?{query}

    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server
}
```

Behaviour under the two PHP settings:

| `cgi.fix_pathinfo` | Original configuration | With these two lines |
|---|---|---|
| `1` (default) | Everything works | Everything works: home page, posts and categories 200, `/usr/typecho.db` 403, login 302 to `/admin/`, admin panel 200 |
| `0` | `/index.php/archives/1/` and `/index.php/action/login` both 403 `Access denied.`; after logging in you are sent back to the login page | Home page, posts, categories and login page 200, login 302 to `/admin/`, admin panel 200 |

This rewrite only covers `index.php`; other `xxx.php/...` URLs are still rejected, and Typecho does not use them. Alternatively, leave the Caddyfile unchanged and keep PHP's default `cgi.fix_pathinfo=1`.

## Enabling permalinks in the admin panel

Once the server is configured, go to "Settings → Permalinks" in the admin panel, set "Use URL rewriting" to "Enable" and save. When saving, Typecho sends a test request **from the server itself** to `site URL/action/ajax`, and only allows enabling if the response is correct; under Apache (`mod_php`), if the root directory has no `.htaccess` and is writable, it also writes one automatically.

When the check fails, the page shows "Rewrite check failed, please check your server settings". Common causes:

| Cause | Fix |
|---|---|
| The rewrite rules are not in effect | Check the configuration as described above, and open a post URL (such as `/archives/1/`) by hand to confirm it works |
| Apache cannot create `.htaccess` in the root directory | The page additionally says "The program cannot create the .htaccess file in the root directory"; create the `.htaccess` above by hand |
| The server cannot reach its own public address (containers, internal networks, a domain configured only in the local hosts file, CDN origin restrictions, and so on) | After confirming the rules work in a browser, tick "If you still want to enable this feature, check here" and save; or define `__TYPECHO_REWRITE__` in `config.inc.php`, see [config.inc.php and constants](./config-file.md#rewrite) |

## Common errors {#troubleshooting}

### Home page works, every post link returns 404 {#404-everywhere}

Permalinks are enabled, but the server does not forward requests to `index.php`.

- nginx: check that `location /` contains `try_files $uri $uri/ /index.php$is_args$args;`.
- Apache: make sure `mod_rewrite` is enabled, `.htaccess` exists in the root directory, and the site's `<Directory>` has `AllowOverride All` (with `AllowOverride None`, `.htaccess` is ignored entirely).
- Temporary workaround: turn off URL rewriting in "Settings → Permalinks" in the admin panel; links go back to the `/index.php/archives/1/` form.

If the "Permalinks" page itself cannot be opened either, edit the database directly: in the `typecho_options` table, set `value` of the row with `name = 'rewrite'` to `0`.

### No input file specified {#no-input-file}

The script path PHP received does not exist. The nginx configuration on this page does not produce this error with `cgi.fix_pathinfo` set to either `1` or `0`.

- nginx: this only happens when PHP-FPM sets `cgi.fix_pathinfo=0` **and** the PHP `location` lacks `fastcgi_split_path_info` (with the default `1`, PHP-FPM corrects the path itself and the same configuration works). In that configuration, `GET /index.php/archives/1/` shows "No input file specified.", while **the login POST returns 403 `Access denied.`**, and the PHP-FPM log contains `Access to the script '.../index.php/action/login' has been denied (see security.limit_extensions)`. Both symptoms are the same problem; add `fastcgi_split_path_info` as shown above.
- nginx: a wrong `SCRIPT_FILENAME` (for example a path that does not match `root`) causes the same error.
- Caddy: also breaks with `cgi.fix_pathinfo=0`, showing 403 `Access denied.`, see [above](#caddy-fix-pathinfo).
- When nginx and PHP-FPM are not on the same machine or in the same container, `SCRIPT_FILENAME` must be a path visible **on the PHP-FPM side**, see [Containers and cloud hosting](./containers.md#fpm).
- This message also appears when PHP sets `open_basedir` and it does not include the site directory.
- Apache: switch to the compatible form `RewriteRule . /index.php [L]` described above.

### Login fails: 404, 500 or 403 {#login-fails}

The admin login form submits to `/index.php/action/login`. If this URL is not correctly passed to `index.php`, the login POST fails. Common incorrect nginx configurations and their symptoms:

| Response | Configuration | Fix |
|---|---|---|
| **404** | `location ~ \.php$`, and `location /` has no `try_files ... /index.php$is_args$args` fallback (written as `=404`, or no `location /` at all) | Use the `location ~ [^/]\.php(/\|$)` shown above |
| **500** | `location ~ \.php$` plus the outdated `if (!-e $request_filename) { rewrite ... /index.php$1 last; }`; the log contains `rewrite or internal redirection cycle` | Use the `try_files` and PHP `location` shown above |
| **403** `Access denied.` | PHP sets `cgi.fix_pathinfo=0` and nginx lacks `fastcgi_split_path_info`; or Caddy combined with `cgi.fix_pathinfo=0` | See [No input file specified](#no-input-file) and [the Caddy fix](#caddy-fix-pathinfo) |

`location ~ \.php$` is not wrong in itself: as long as `location /` has the `try_files ... /index.php$is_args$args` fallback, login works.

None of these incorrect configurations returns 405. If you get **405 Not Allowed**, the problem is not in the configurations above; post your nginx configuration and error log on the [support forum](https://forum.typecho.org).

### The admin panel opens, but after logging in you are back at the login page

This is usually related to cookies or the site URL, and is common with HTTPS, reverse proxies and CDNs, see [HTTPS, reverse proxies and CDNs](./https-proxy.md#login-loop).
