---
title: Web 服务器配置
sidebar_position: 1
---

# Web 服务器配置

Typecho 的所有动态请求都由网站根目录下的 `index.php` 处理。Web 服务器要做好两件事：

1. **把 PHP 请求交给 PHP**，并且支持 `index.php/xxx` 这种带路径的地址（PATH_INFO 形式）。后台登录、保存设置、上传附件等表单都提交到 `index.php/action/...`，**不管是否开启了永久链接**，这一条都必须满足；
2. **开启永久链接（地址重写）时**，把不存在的文件和目录都转给 `index.php`，这样 `/archives/1/` 这样的地址才能打开。

本页给出 nginx、Apache 和 Caddy 2 的当前写法。GoDaddy 主机、Caddy v1、nginx 0.7 时代的配置写法已经过时，请不要再使用。

## Typecho 对服务器的要求

Typecho 不依赖服务器传入的 `PATH_INFO` 变量，而是用 `REQUEST_URI` 和 `SCRIPT_NAME`/`SCRIPT_FILENAME` 自己算出路径（`var/Typecho/Request.php` 中的 `getPathInfo()`、`getBaseUrl()`）。所以配置的关键是：

| 要求 | 说明 |
|---|---|
| `SCRIPT_FILENAME` 指向真实存在的 `index.php` | 请求 `/index.php/action/login` 时，交给 PHP 的脚本必须是 `/网站根目录/index.php`，而不是不存在的 `/网站根目录/index.php/action/login`。PHP 默认（`cgi.fix_pathinfo=1`）会自己修正这种路径；设为 `0` 时就会报 “No input file specified.” 或 403 “Access denied.”，见[常见错误](#no-input-file) |
| `REQUEST_URI` 保留原始地址 | 永久链接转发给 `index.php` 时，`REQUEST_URI` 仍要是用户请求的 `/archives/1/`。nginx 的 `fastcgi_params`、Apache 和 Caddy 默认都是这样 |
| `/index.php/...` 能到达 PHP | 登录、评论、保存设置都是 POST 到 `/index.php/action/...`。这类地址没有交给 PHP 时，登录会失败，表现为 404、500 或 403，见[登录失败](#login-fails) |

## nginx {#nginx}

nginx 通过 PHP-FPM 运行 PHP。下面是一个完整的 `server` 块，PHP-FPM 的地址请换成你服务器上的实际值（常见的有 `unix:/run/php/php8.2-fpm.sock` 或 `127.0.0.1:9000`）：

```nginx
server {
    listen 80;
    server_name blog.example.com;
    root /srv/www/typecho;
    index index.php index.html;

    # 永久链接：文件或目录不存在时交给 index.php
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # 禁止下载 SQLite 数据库文件和服务器上的备份文件
    location ~ ^/usr/(.+\.db$|backups/) {
        deny all;
    }

    # 上传目录中的 PHP 文件一律不执行
    location ~ ^/usr/uploads/.*\.php$ {
        deny all;
    }

    # 同时匹配 /index.php 和 /index.php/action/login 这样的地址
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

几处要点：

- **PHP 的 `location` 最好不要写成 `location ~ \.php$`**。这样写只匹配以 `.php` 结尾的地址，`/index.php/action/login` 会落到 `location /`。如果 `location /` 里有上面的 `try_files ... /index.php$is_args$args` 回落，请求仍会交给 `index.php`，登录正常；但没有这个回落（没开永久链接，或写成 `try_files $uri $uri/ =404`）时，登录返回 **404**。
- 上面的 PHP `location` 在 PHP 的 `cgi.fix_pathinfo` 为默认的 `1` 和加固的 `0` 时都可以使用。
- `fastcgi_split_path_info` 把 `/index.php/action/login` 拆成脚本 `/index.php` 和路径 `/action/login`，`SCRIPT_FILENAME` 才能指向真实文件。
- nginx 按出现顺序匹配正则 `location`，两条 `deny` 规则要写在 PHP 的 `location` **之前**。
- Debian / Ubuntu 的 `fastcgi_params` 不包含 `SCRIPT_FILENAME`，所以上面单独设置了一次。如果你用的是发行版自带的 `snippets/fastcgi-php.conf`，它已经包含 `fastcgi_split_path_info` 和 `PATH_INFO`，不要重复设置。
- 不要用早年流传的 `if (!-e $request_filename) { rewrite ^(.*)$ /index.php$1 last; }` 代替 `try_files`。它和 `location ~ \.php$` 一起使用时，`/index.php/action/login` 会被反复重写成 `/index.php/index.php/...`，返回 **500**（日志：`rewrite or internal redirection cycle`）。
- 网站装在子目录（例如 `/blog/`）时，把 `location /` 改成 `location /blog/`，`try_files` 的最后一项改成 `/blog/index.php$is_args$args`。

改完后用 `nginx -t` 检查语法，再 `nginx -s reload`（或 `systemctl reload nginx`）。

## Apache {#apache}

Apache 需要启用 `mod_rewrite`，并允许网站目录使用 `.htaccess`：

```apache
<VirtualHost *:80>
    ServerName blog.example.com
    DocumentRoot /srv/www/typecho

    <Directory /srv/www/typecho>
        AllowOverride All
        Require all granted
    </Directory>

    # 使用 PHP-FPM 时（mod_proxy_fcgi）；使用 mod_php 时不需要这一段
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

Debian / Ubuntu 上用 `a2enmod rewrite` 启用模块；使用 PHP-FPM 时还要 `a2enmod proxy_fcgi`。

### .htaccess {#htaccess}

在网站根目录（与 `index.php` 同级）创建 `.htaccess`：

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php/$1 [L]
</IfModule>
```

这正是 Typecho 在后台开启永久链接时尝试自动写入的内容（`var/Widget/Options/Permalink.php`）。网站装在子目录时，把 `RewriteBase /` 和 `/index.php/$1` 中的 `/` 换成子目录，例如 `/blog/` 和 `/blog/index.php/$1`。

如果这样写出现 “No input file specified.”（在部分 FastCGI / CGI 环境中会这样），改用下面这种兼容写法。它也是 Typecho 在第一种写法检测失败后自动尝试的第二种：

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

建议同时在 `usr/uploads/` 下放一个 `.htaccess`，禁止执行上传目录中的 PHP 文件：

```apache
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
```

## Caddy 2 {#caddy}

Caddy 2 的 `php_fastcgi` 指令已经包含了 Typecho 需要的处理：按 `.php` 拆分路径，文件不存在时回落到 `index.php`，并保留原始的 `REQUEST_URI`。

```caddy
blog.example.com {
    root * /srv/www/typecho

    # 禁止下载 SQLite 数据库文件、服务器上的备份文件和上传目录中的 PHP
    @blocked path_regexp ^/usr/(.+\.db$|backups/|uploads/.*\.php$)
    respond @blocked 403

    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server
}
```

Caddy 会自动申请 HTTPS 证书。Caddy v1 的 `rewrite { to ... }` 和 `fastcgi / ... php` 写法在 Caddy 2 中已经不存在。上面的配置用 4 个空格缩进，`caddy validate` 会提示 `Caddyfile input is not formatted`，不影响使用。

### PHP 设置了 `cgi.fix_pathinfo=0` 时 {#caddy-fix-pathinfo}

`cgi.fix_pathinfo=0` 是常见的 PHP 加固建议。在 Caddy 下这样设置后，**所有 `/index.php/...` 地址都返回 403 `Access denied.`**，后台无法登录。

原因：`php_fastcgi` 会传 `PATH_TRANSLATED = 网站根目录 + PATH_INFO`，例如请求 `/index.php/action/login` 时是 `/srv/www/typecho/action/login`。`cgi.fix_pathinfo=0` 时 PHP-FPM 把它当作脚本路径，因为它不以 `.php` 结尾，被 `security.limit_extensions` 拒绝。PHP-FPM 日志：

```text
NOTICE: Access to the script '/srv/www/typecho/action/login' has been denied (see security.limit_extensions)
```

解决办法：在 `php_fastcgi` 之前加两行，把 `/index.php/...` 在内部重写成 `/index.php`。Caddy 传给 PHP 的 `REQUEST_URI` 仍是原始地址，Typecho 靠它计算路径，所以不受影响：

```caddy
blog.example.com {
    root * /srv/www/typecho

    # 禁止下载 SQLite 数据库文件、服务器上的备份文件和上传目录中的 PHP
    @blocked path_regexp ^/usr/(.+\.db$|backups/|uploads/.*\.php$)
    respond @blocked 403

    # /index.php/action/login 这类地址直接交给 index.php（兼容 cgi.fix_pathinfo=0）
    @pathinfo path_regexp ^/index\.php/
    rewrite @pathinfo /index.php?{query}

    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server
}
```

两种 PHP 设置下的表现：

| `cgi.fix_pathinfo` | 原配置 | 加了这两行 |
|---|---|---|
| `1`（默认） | 全部正常 | 全部正常：首页、文章、分类 200，`/usr/typecho.db` 403，登录 302 到 `/admin/`，后台 200 |
| `0` | `/index.php/archives/1/`、`/index.php/action/login` 都是 403 `Access denied.`，登录后又回到登录页 | 首页、文章、分类、登录页 200，登录 302 到 `/admin/`，后台 200 |

这条重写只覆盖 `index.php`，其他 `xxx.php/...` 地址仍会被拒绝，Typecho 用不到它们。也可以不改 Caddyfile，而是保持 PHP 默认的 `cgi.fix_pathinfo=1`。

## 在后台开启永久链接

服务器配置好以后，到后台“设置 → 永久链接”，把“是否使用地址重写功能”设为“启用”并保存。保存时 Typecho 会从**服务器自己**向 `站点地址/action/ajax` 发一个检测请求，返回正确时才允许启用；在 Apache（`mod_php`）下，如果根目录没有 `.htaccess` 且可写，还会自动写入一份。

检测失败时页面显示“重写功能检测失败, 请检查你的服务器设置”。常见原因：

| 原因 | 处理 |
|---|---|
| 重写规则没有生效 | 按上文检查配置，手动打开一个文章地址（如 `/archives/1/`）确认能访问 |
| Apache 无法在根目录创建 `.htaccess` | 页面会额外提示“程序无法在根目录创建.htaccess文件”，手动创建上面的 `.htaccess` |
| 服务器访问不到自己的公网地址（容器、内网、只在本机 hosts 中配置的域名、CDN 回源限制等） | 确认规则在浏览器中可用后，勾选“如果你仍然想启用此功能, 请勾选这里”再保存；或者在 `config.inc.php` 中定义 `__TYPECHO_REWRITE__`，见[config.inc.php 与常量](./config-file.md#rewrite) |

## 常见错误 {#troubleshooting}

### 首页正常，所有文章链接都是 404 {#404-everywhere}

开启了永久链接，但服务器没有把请求转给 `index.php`。

- nginx：检查 `location /` 里是否有 `try_files $uri $uri/ /index.php$is_args$args;`。
- Apache：确认启用了 `mod_rewrite`，`.htaccess` 存在于根目录，并且站点的 `<Directory>` 中是 `AllowOverride All`（`AllowOverride None` 时 `.htaccess` 会被完全忽略）。
- 临时恢复：在后台“设置 → 永久链接”中关闭地址重写，链接会变回 `/index.php/archives/1/` 的形式。

如果后台“永久链接”页本身也打不开，可以直接修改数据库：把 `typecho_options` 表中 `name = 'rewrite'` 那一行的 `value` 改为 `0`。

### No input file specified {#no-input-file}

PHP 收到的脚本路径不存在。本页的 nginx 配置在 `cgi.fix_pathinfo` 为 `1` 和 `0` 时都不会出现这个错误。

- nginx：只在 PHP-FPM 设置了 `cgi.fix_pathinfo=0`、**并且** PHP 的 `location` 缺少 `fastcgi_split_path_info` 时出现（默认的 `1` 下 PHP-FPM 会自己修正路径，同样的配置也能用）。这种配置下，`GET /index.php/archives/1/` 显示 “No input file specified.”，而**登录 POST 返回 403 `Access denied.`**，PHP-FPM 日志中有 `Access to the script '.../index.php/action/login' has been denied (see security.limit_extensions)`。两种表现是同一个问题，按上文的写法补上 `fastcgi_split_path_info` 即可。
- nginx：`SCRIPT_FILENAME` 写错（例如使用了与 `root` 不一致的路径）也会这样。
- Caddy：同样在 `cgi.fix_pathinfo=0` 时出问题，表现为 403 `Access denied.`，见[上文](#caddy-fix-pathinfo)。
- nginx 与 PHP-FPM 不在同一台机器或同一个容器里时，`SCRIPT_FILENAME` 必须是 **PHP-FPM 那一侧**能看到的路径，见[容器与云主机部署](./containers.md#fpm)。
- PHP 设置了 `open_basedir` 且不包含网站目录时，也会出现这个提示。
- Apache：换用上文的兼容写法 `RewriteRule . /index.php [L]`。

### 登录失败：404、500 或 403 {#login-fails}

后台登录表单提交到 `/index.php/action/login`。这个地址没有正确交给 `index.php` 时，登录 POST 会失败。nginx 的几种常见错误写法与对应的表现：

| 返回 | 配置 | 处理 |
|---|---|---|
| **404** | `location ~ \.php$`，并且 `location /` 没有 `try_files ... /index.php$is_args$args` 回落（写成 `=404`，或者没有 `location /`） | 改用上文的 `location ~ [^/]\.php(/\|$)` |
| **500** | `location ~ \.php$` 加过时的写法 `if (!-e $request_filename) { rewrite ... /index.php$1 last; }`，日志中有 `rewrite or internal redirection cycle` | 改用上文的 `try_files` 和 PHP `location` |
| **403** `Access denied.` | PHP 设置了 `cgi.fix_pathinfo=0`，nginx 缺少 `fastcgi_split_path_info`；或者 Caddy 搭配 `cgi.fix_pathinfo=0` | 见 [No input file specified](#no-input-file) 和 [Caddy 的修正](#caddy-fix-pathinfo) |

`location ~ \.php$` 本身不一定出错：只要 `location /` 里有 `try_files ... /index.php$is_args$args` 回落，登录就正常。

上面这几种错误写法都不会返回 405。如果你遇到的是 **405 Not Allowed**，说明问题不在上述配置，请把 nginx 配置和错误日志发到[支持论坛](https://forum.typecho.org)。

### 后台能打开，登录后又回到登录页

这通常与 Cookie 或站点地址有关，常见于 HTTPS、反向代理和 CDN 环境，见[HTTPS、反向代理与 CDN](./https-proxy.md#login-loop)。
