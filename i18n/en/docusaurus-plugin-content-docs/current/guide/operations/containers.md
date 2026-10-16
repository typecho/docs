---
title: Containers and Cloud Hosting
sidebar_position: 3
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Containers and Cloud Hosting

Typecho is an ordinary PHP application and can be deployed on any environment that runs PHP 7.4+. What you need to watch out for depends on the environment:

| Environment | Key points |
|---|---|
| VPS / cloud server | Install the web server, PHP and the database yourself, and follow [Requirements](/guide/getting-started/requirements), [Installation](/guide/getting-started/installation) and [Web server configuration](./web-server.md) |
| Shared hosting | Upload the files and install through the browser. Select PHP 7.4 or later in the hosting control panel; permalinks usually rely on Apache's `.htaccess`, or the panel's URL rewriting ("伪静态") setting (choose the rule for generic PHP applications, or enter the rules from [Web server configuration](./web-server.md#htaccess)) |
| Containers | Use the official image `joyqi/typecho`, see below |

## The official image `joyqi/typecho`

The official image is published on Docker Hub: [hub.docker.com/r/joyqi/typecho](https://hub.docker.com/r/joyqi/typecho), described as "Typecho Official Image" and maintained by the Typecho Dev Team. The image build scripts live in the [github.com/typecho/Dockerfile](https://github.com/typecho/Dockerfile) repository; report image issues there.

### Tags {#tags}

As of October 2026 the image has 70 tags, and **there is no `latest` tag**; you must specify a version:

| Tag | Push date | Notes |
|---|---|---|
| `1.3.0-php7.4`, `1.3.0-php8.2` and their variants | 2026-01-20 | **1.3.0 stable release**; use this group in production |
| `nightly-php7.4`, `nightly-php8.2` and their variants | 2026-04-11 | Built by continuous integration from the `master` branch; contains **unreleased** changes; **do not use in production** |
| `1.2.1-php7.3/7.4/8.0` and their variants | 2023-06-06 | Earlier version |
| `1.2.0-php7.3/7.4/8.0` and their variants | 2022-03-31 | Earlier version |

The 1.3.0 tags support three platforms: `linux/amd64`, `linux/arm64` and `linux/arm/v7`.

Each version + PHP version combination has 7 variants (taking `1.3.0-php8.2` as an example):

| Variant | Contents | Use |
|---|---|---|
| `1.3.0-php8.2-apache` | Apache (`mod_php`) + `mod_rewrite`, listening on port 80 | **The simplest choice**; a single container can serve the site |
| `1.3.0-php8.2-fpm`, `1.3.0-php8.2-fpm-alpine` | PHP-FPM, listening on port 9000 | Needs a separate web server such as nginx in front, see [below](#fpm) |
| `1.3.0-php8.2`, `1.3.0-php8.2-alpine` | PHP's built-in `php -S` development server listening on port 80 | Only for trying out and testing; **do not use in production** |
| `1.3.0-php8.2-cli`, `1.3.0-php8.2-cli-alpine` | PHP command line only | Running one-off commands |

There is no Alpine version of the Apache variant.

### Image layout and startup

According to the image's startup script `/usr/bin/init_typecho` (its source is `scripts/init.sh` in the `typecho/Dockerfile` repository):

- The site root is `/app`, and the whole of `/app` is declared as a volume (`VOLUME /app`);
- **`config.inc.php` is written to the root of `/app`, not to `/app/usr`**. If you mount a volume only on `/app/usr`, the configuration file is not in the volume; after the container is removed and recreated it is gone, and the home page redirects with 302 to `/install.php`;
- The program files are in `/usr/src/typecho` in the image. On **every start** of the container, only the items that **do not exist** in `/app` (`admin`, `install`, `var`, `index.php`, `install.php`, and `usr/themes`, `usr/plugins`, `usr/langs`) are copied from there; existing ones are not overwritten. `usr/uploads` is created if it does not exist;
- With `TYPECHO_INSTALL=1`, every start runs `php /app/install.php` once as the `www-data` user (`su -p`, environment variables preserved), which is the [command-line installation](/guide/getting-started/cli-install). When the same container restarts, it only prints two lines of version information and the site starts as usual;
- PHP errors are not shown on the page; they go to standard error, viewable with `docker logs`.

About `.htaccess` in the Apache variant: `Dockerfile.apache` writes rewrite rules to `/app/.htaccess` at build time, but `/app` has already been declared as a volume by then. According to the Docker documentation, changes made to that directory in build steps after the volume is declared may be discarded, and the startup script does not copy `.htaccess` either, so `/app` in the container does not necessarily contain this file. If post pages return 404 after you enable permalinks, create the `.htaccess` from [Web server configuration](./web-server.md#htaccess) under `/app`; the Apache variant uses `mod_php`, so Typecho also tries to write it automatically when you enable permalinks in the admin panel.

### Environment variables

| Variable | Default | Effect |
|---|---|---|
| `TYPECHO_INSTALL` | `0` | When set to `1`, runs the command-line installation automatically on startup |
| `TIMEZONE` | empty | Time zone, such as `Asia/Shanghai`; written to PHP's `date.timezone` and to the container's `/etc/localtime` |
| `MEMORY_LIMIT` | `128m` | PHP `memory_limit` |
| `MAX_POST_BODY` | `10m` | Sets both PHP `upload_max_filesize` and `post_max_size`, that is, the attachment size limit |
| `MAX_EXECUTION_TIME` | `30` | PHP `max_execution_time` (not listed on the image description page) |
| `TYPECHO_SITE_URL`, `TYPECHO_DB_*`, `TYPECHO_USER_*`, etc. | see right | Not image variables but `install.php`'s own variables, passed through unchanged; for their meaning and defaults, [Command-line and unattended installation](/guide/getting-started/cli-install#环境变量) is authoritative |

The first five are all of the image's own variables.

:::danger Do not copy the Docker Hub description page
The image description page (updated 2026-04-11) differs from the actual behaviour of the 1.3.0 image in five places. **Do not trust** the following; this page is authoritative:

1. **`TYPECHO_DB_NEXT=force`**: rejected, with the output `dbNext: 确认您的配置`. The only valid values are `none`, `delete`, `keep` and `config`; to drop the tables and reinstall, use `delete`.
2. **Tag list and examples**: they list tags that no longer exist, such as `nightly-php7.3` and `nightly-php8.0`, and the examples use `nightly-php7.4`. For the actual tags see [above](#tags).
3. **`TIMEZONE` defaults to `UTC`**: in the image it is actually empty, in which case `date.timezone` is not written and PHP's default is used.
4. **`TYPECHO_DB_FILE` is required for SQLite**: it is not required; when unset, a random file name is generated under `usr/`.
5. **Volume example `-v /var/typecho:/app/usr`**: if you mount like this without adding `TYPECHO_DB_NEXT=keep`, `config.inc.php` is lost when the container is recreated and the site jumps to the installer.

The only parts of the description page that remain reliable are: the image name, the automatic installation done by `TYPECHO_INSTALL=1`, and the meaning of `MEMORY_LIMIT` and `MAX_POST_BODY`.
:::

## Deploying with Compose (Apache variant + MySQL)

The example below uses MySQL 8.0 as the database and keeps `usr/` (themes, plugins, language packs, uploaded files) in the host directory `./usr`:

```yaml title="compose.yaml"
services:
  typecho:
    image: joyqi/typecho:1.3.0-php8.2-apache
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      TYPECHO_INSTALL: "1"
      TYPECHO_DB_NEXT: keep
      TYPECHO_SITE_URL: https://blog.example.com
      TYPECHO_DB_ADAPTER: Pdo_Mysql
      TYPECHO_DB_HOST: db
      TYPECHO_DB_USER: typecho
      TYPECHO_DB_PASSWORD: 请换成数据库密码
      TYPECHO_DB_DATABASE: typecho
      TYPECHO_USER_NAME: admin
      TYPECHO_USER_PASSWORD: 请换成管理员密码
      TYPECHO_USER_MAIL: admin@example.com
      TIMEZONE: Asia/Shanghai
    volumes:
      - ./usr:/app/usr
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: typecho
      MYSQL_USER: typecho
      MYSQL_PASSWORD: 请换成数据库密码
      MYSQL_RANDOM_ROOT_PASSWORD: "1"
    volumes:
      - db:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 20

volumes:
  db:
```

```bash
docker compose up -d
docker compose logs typecho   # 第一次启动时会打印“安装成功”和管理员帐号
```

Some notes:

- **Why `TYPECHO_DB_NEXT: keep` is needed**: `config.inc.php` is written to the root of `/app`, but here only `/app/usr` is mounted from the host. After the container is removed and recreated (for example when switching to a newer image), `/app` is a new, empty volume, and `config.inc.php` is gone with it. `TYPECHO_INSTALL=1` plus `TYPECHO_DB_NEXT=keep` regenerates the configuration from the environment variables on every start; when the database already holds complete data, the installer writes the configuration and stops, without recreating tables or touching the administrator account. If you recreate without `keep`, you get "The installer found that the existing data tables already exist." (安装程序检查到原有数据表已经存在.) and are sent to the installer page. `keep` only takes effect when the tables already exist, so it does not affect the first installation on an empty database.
- **Wait for the database to be ready**: `depends_on` with `condition: service_healthy` makes the Typecho container wait until MySQL accepts connections before starting. Otherwise MySQL is not ready on the first start and the automatic installation fails.
- As with a regular installation, the MySQL / PostgreSQL database must exist beforehand; here `MYSQL_DATABASE` creates it.
- After a successful installation, the administrator password appears in plain text in `docker compose logs`. You can leave `TYPECHO_USER_PASSWORD` unset to have it generated randomly, and change it right after logging in.
- If `./usr` does not exist on the host, Docker creates it as root, and the startup script only sets `www-data` as owner of the subdirectories it creates itself. With SQLite (whose database file is placed under `usr/` by default), the installer runs as `www-data` and may be unable to create the database file in `usr/`; change the owner of `./usr` on the host first (in Debian-based images the UID of `www-data` is usually 33).
- With an HTTPS reverse proxy in front, pass `Host` and `X-Forwarded-Proto` as described in [HTTPS, reverse proxies and CDNs](./https-proxy.md).

### The problem with mounting all of `/app` {#mount-whole-app}

You can also mount the whole of `/app` as a named volume, which keeps `config.inc.php` too and makes `keep` unnecessary. The cost: the startup script only copies files that **do not exist** in `/app`, so after switching to a newer image, the old `admin/`, `var/` and so on in the volume are not updated, and **the upgrade never actually happens**. For example, after replacing `1.2.1-php8.0-apache` with `1.3.0-php8.2-apache`, PHP becomes 8.2, but `var/Typecho/Common.php` in the volume still has `VERSION = '1.2.1'` and the page generator still says Typecho 1.2.1. If you choose this approach, before upgrading delete `admin`, `var`, `install`, `index.php` and `install.php` from the volume, start the new image, then complete the upgrade in the admin panel (see [Upgrading](/guide/getting-started/upgrade)).

### Upgrading {#upgrade}

Mount only `usr/`, and keep `TYPECHO_INSTALL: "1"`, all of `TYPECHO_SITE_URL` / `TYPECHO_DB_*`, and `TYPECHO_DB_NEXT: keep` (the Compose file above already has them):

1. [Back up](./backup.md) the database and `./usr`;
2. Change `image` to the new version tag and run `docker compose pull && docker compose up -d`;
3. Log in to the admin panel as an administrator; you are taken to the upgrade page, where you follow the prompts to complete the upgrade.

With this procedure, the new container regenerates the configuration on startup, logging in to the admin panel redirects to `upgrade.php`, and once that has run the version is the new one. If `TYPECHO_DB_NEXT=keep` or `TYPECHO_INSTALL=1` is missing, the new container has no `config.inc.php` and the site jumps to the installer; add the variables and recreate the container, and the database is not affected.

Note that directories such as `usr/themes` already exist, so new themes shipped with a newer image do not appear in `./usr/themes` automatically.

## FPM variant + nginx {#fpm}

The FPM variant runs only PHP-FPM; static files and URL rewriting are handled by a separate nginx container. The key points: **both containers must see the same `/app`**, and the `SCRIPT_FILENAME` nginx passes to PHP-FPM must be the path **inside the FPM container**; otherwise you get "No input file specified." or "File not found.".

```yaml title="compose.yaml（片段）"
services:
  typecho:
    image: joyqi/typecho:1.3.0-php8.2-fpm
    environment:
      TYPECHO_INSTALL: "1"
      TYPECHO_DB_NEXT: keep
      # 其余 TYPECHO_* 变量同上
    volumes:
      - app:/app
      - ./usr:/app/usr

  web:
    image: nginx:1.27
    ports:
      - "8080:80"
    volumes:
      - app:/app:ro
      - ./usr:/app/usr:ro
      - ./typecho.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - typecho

volumes:
  app:
```

`typecho.conf` is the same as the nginx configuration in [Web server configuration](./web-server.md#nginx); just change `root` to `/app` and `fastcgi_pass` to `typecho:9000`.

Here `/app` uses a named volume so that both containers can share it, so the upgrade problem described [above](#mount-whole-app) applies as well.

## Read-only file systems {#read-only}

On Kubernetes and similar platforms, a common requirement is a read-only container root file system (`readOnlyRootFilesystem`, `docker run --read-only`), with only mounted volumes writable.

**The official image cannot run like this as-is**: its startup script writes `/usr/local/etc/php/conf.d/custom.ini` and `/etc/localtime` and copies files into `/app` on every start, and Apache and PHP-FPM themselves need writable runtime directories. The official image does not document which paths need a `tmpfs` mount. A more controllable approach is to build your own image on top of the official `php` image, with the Typecho program and `config.inc.php` baked into the image.

Whatever image you use, Typecho 1.3 itself writes to the following locations at runtime. They must be writable; everything else can be read-only:

| Location | When it is written | Handling in a read-only environment |
|---|---|---|
| `usr/uploads/` (or `__TYPECHO_UPLOAD_ROOT_DIR__`) | Uploading or modifying attachments | **Must** be a writable volume. You can point `__TYPECHO_UPLOAD_ROOT_DIR__` at the volume's mount point, see [Attachment storage location](./uploads-storage.md) |
| The SQLite database file and its directory | Every database write | Switch to MySQL / PostgreSQL, or put the database file in a writable volume (SQLite also needs to create temporary journal files in the same directory) |
| PHP temporary directory (`sys_get_temp_dir()`) | Exporting a backup in the admin panel writes a temporary file first; PHP handling uploads | Mount a `tmpfs` on `/tmp` |
| PHP session directory (`session.save_path`) | Every request from a logged-in user starts a session (`var/Widget/Init.php`) | Mount a `tmpfs` on the session directory |
| `config.inc.php` in the site root | Only during installation | Put it in at image build time, or mount an existing configuration read-only |
| `.htaccess` in the site root | Only when toggling permalinks under Apache `mod_php` | Put it into the image at build time, or define `__TYPECHO_REWRITE__` |
| Theme files under `usr/themes/` | When saving in the admin theme editor | Define `define('__TYPECHO_THEME_WRITEABLE__', false);` to disable online editing |

In addition, `__TYPECHO_BACKUP_DIR__` (default `usr/backups`) is only read: when restoring "from the server" in the admin panel, the `.dat` files in it are listed. It does not need to be writable.

Plugins may have their own write requirements (caches, logs and so on); check each one.
