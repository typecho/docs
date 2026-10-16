---
title: 容器与云主机部署
sidebar_position: 3
---

# 容器与云主机部署

Typecho 是普通的 PHP 程序，能运行 PHP 7.4+ 的环境都可以部署。按环境不同，需要注意的地方也不同：

| 环境 | 要点 |
|---|---|
| VPS / 云服务器 | 自己安装 Web 服务器、PHP 和数据库，按[环境要求](../getting-started/requirements.md)、[安装](../getting-started/installation.mdx)和[Web 服务器配置](./web-server.md)操作 |
| 共享虚拟主机 | 上传文件后用浏览器安装。在主机控制面板中选择 PHP 7.4 或更高版本；永久链接通常靠 Apache 的 `.htaccess`，或面板里的“伪静态”设置（选择通用 PHP 程序的规则，或填入[Web 服务器配置](./web-server.md#htaccess)中的规则） |
| 容器 | 使用官方镜像 `joyqi/typecho`，见下文 |

## 官方镜像 `joyqi/typecho`

官方镜像发布在 Docker Hub：[hub.docker.com/r/joyqi/typecho](https://hub.docker.com/r/joyqi/typecho)，描述为 “Typecho Official Image”，维护者为 Typecho Dev Team。镜像的构建脚本在 [github.com/typecho/Dockerfile](https://github.com/typecho/Dockerfile) 仓库，镜像相关的问题请到该仓库提交。

:::info 本节哪些经过实测
2026-10-16 用 `joyqi/typecho:1.3.0-php8.2-apache`（digest `sha256:195752c1…`）和 `1.2.1-php8.0-apache` 实际运行过：`TYPECHO_INSTALL=1` 加 MySQL 自动安装、前台和后台登录、`config.inc.php` 的位置、重建容器和换镜像升级（见[下文](#upgrade)）。标签列表来自 Docker Hub API。**没有实测**的是：Compose 文件本身（测试用的是等价的 `docker run`）、FPM / CLI / Alpine 变体、`1.3.0-php7.4-*`、只读文件系统，以及下文单独注明的几处。
:::

### 标签

截至 2026-10-16，镜像共有 70 个标签，**没有 `latest` 标签**，必须写明版本：

| 标签 | 推送日期 | 说明 |
|---|---|---|
| `1.3.0-php7.4`、`1.3.0-php8.2` 及其变体 | 2026-01-20 | **1.3.0 正式版**，生产环境请使用这一组 |
| `nightly-php7.4`、`nightly-php8.2` 及其变体 | 2026-04-11 | 由 `master` 分支的持续集成构建，包含**尚未发布**的改动，**不要用于生产环境** |
| `1.2.1-php7.3/7.4/8.0` 及其变体 | 2023-06-06 | 旧版本 |
| `1.2.0-php7.3/7.4/8.0` 及其变体 | 2022-03-31 | 旧版本 |

1.3.0 的标签支持 `linux/amd64`、`linux/arm64`、`linux/arm/v7` 三种平台。

每个版本 + PHP 版本有 7 个变体（以 `1.3.0-php8.2` 为例）：

| 变体 | 内容 | 用途 |
|---|---|---|
| `1.3.0-php8.2-apache` | Apache（`mod_php`）+ `mod_rewrite`，监听 80 端口 | **最简单的选择**，单个容器即可对外服务 |
| `1.3.0-php8.2-fpm`、`1.3.0-php8.2-fpm-alpine` | PHP-FPM，监听 9000 端口 | 前面需要另配 nginx 等 Web 服务器，见[下文](#fpm) |
| `1.3.0-php8.2`、`1.3.0-php8.2-alpine` | 用 PHP 内置的 `php -S` 开发服务器监听 80 端口 | 只适合试用和测试，**不要用于生产** |
| `1.3.0-php8.2-cli`、`1.3.0-php8.2-cli-alpine` | 只有 PHP 命令行 | 执行一次性命令 |

Apache 变体没有 Alpine 版本。

### 镜像的目录与启动过程

按镜像中的启动脚本 `/usr/bin/init_typecho`（源码即 `typecho/Dockerfile` 仓库的 `scripts/init.sh`），并经实测：

- 网站根目录是 `/app`，并且整个 `/app` 被声明为卷（`VOLUME /app`）；
- **`config.inc.php` 写在 `/app` 根目录，不在 `/app/usr` 里**。只把卷挂在 `/app/usr` 时，配置文件不在卷中，容器删除重建后就没有了，首页会 302 跳转到 `/install.php`；
- 程序文件放在镜像的 `/usr/src/typecho`，容器**每次启动**时，`/app` 中**不存在的**项目（`admin`、`install`、`var`、`index.php`、`install.php`，以及 `usr/themes`、`usr/plugins`、`usr/langs`）才会从这里复制过去，已存在的不会被覆盖；`usr/uploads` 不存在时会创建；
- `TYPECHO_INSTALL=1` 时，每次启动都以 `www-data` 用户运行一次 `php /app/install.php`（`su -p`，环境变量原样保留），即[命令行安装](../getting-started/cli-install.md)。同一个容器重启时只打印两行版本信息，站点照常启动；
- PHP 错误不显示在页面上，而是输出到标准错误，用 `docker logs` 查看。

关于 Apache 变体的 `.htaccess`：`Dockerfile.apache` 在构建时向 `/app/.htaccess` 写入重写规则，但此前 `/app` 已被声明为卷，按 Docker 文档，声明卷之后在构建步骤中对该目录的改动可能被丢弃，启动脚本也不会复制 `.htaccess`。镜像中是否真的有这个文件**未经验证**。开启永久链接后如果文章页 404，在 `/app` 下创建[Web 服务器配置](./web-server.md#htaccess)中的 `.htaccess` 即可；Apache 变体使用 `mod_php`，Typecho 在后台开启永久链接时也会尝试自动写入它。

### 环境变量

| 变量 | 默认值 | 作用 |
|---|---|---|
| `TYPECHO_INSTALL` | `0` | 设为 `1` 时启动时自动运行命令行安装 |
| `TIMEZONE` | 空 | 时区，如 `Asia/Shanghai`，写入 PHP 的 `date.timezone` 和容器的 `/etc/localtime` |
| `MEMORY_LIMIT` | `128m` | PHP `memory_limit` |
| `MAX_POST_BODY` | `10m` | 同时设置 PHP 的 `upload_max_filesize` 和 `post_max_size`，即附件大小上限 |
| `MAX_EXECUTION_TIME` | `30` | PHP `max_execution_time`（镜像说明页没有列出） |
| `TYPECHO_SITE_URL`、`TYPECHO_DB_*`、`TYPECHO_USER_*` 等 | 见右 | 不是镜像的变量，而是 `install.php` 自己的变量，原样传入；含义和默认值以[命令行与无人值守安装](../getting-started/cli-install.md#环境变量)为准 |

前五个是镜像自己的全部变量（`docker image inspect` 和启动脚本核对）。

:::danger 不要照抄 Docker Hub 说明页
镜像说明页（2026-04-11 更新）与 1.3.0 镜像的实际行为有五处不符，以下内容**不能信**，请以本页为准：

1. **`TYPECHO_DB_NEXT=force`**：会被拒绝，输出 `dbNext: 确认您的配置`。有效值只有 `none`、`delete`、`keep`、`config`；删表重装用 `delete`。
2. **标签列表和示例**：列的是 `nightly-php7.3`、`nightly-php8.0` 等已经不存在的标签，示例用 `nightly-php7.4`。实际标签见[上文](#标签)。
3. **`TIMEZONE` 默认 `UTC`**：镜像里实际为空，此时不写 `date.timezone`，只是沿用 PHP 的默认值。
4. **`TYPECHO_DB_FILE` 在 SQLite 下必填**：并非必填，不设时在 `usr/` 下生成随机文件名。
5. **卷示例 `-v /var/typecho:/app/usr`**：照这样挂载、又不加 `TYPECHO_DB_NEXT=keep`，重建容器后 `config.inc.php` 丢失，站点跳到安装页。

说明页中仍然可信的只有：镜像名、`TYPECHO_INSTALL=1` 的自动安装作用，以及 `MEMORY_LIMIT`、`MAX_POST_BODY` 的含义。
:::

## 用 Compose 部署（Apache 变体 + MySQL）

下面的例子用 MySQL 8.0 作数据库，把 `usr/`（主题、插件、语言包、上传文件）放在宿主机的 `./usr` 目录：

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

几点说明：

- **为什么要 `TYPECHO_DB_NEXT: keep`**：`config.inc.php` 写在 `/app` 根目录，而这里只把 `/app/usr` 挂载到了宿主机。容器被删除重建（例如换用新版本镜像）后，`/app` 是一个新的空卷，`config.inc.php` 也就没有了。`TYPECHO_INSTALL=1` 加 `TYPECHO_DB_NEXT=keep` 让每次启动都用环境变量重新生成配置；数据库中已有完整数据时，安装程序写好配置后直接结束，不会重建数据表，也不会改动管理员帐号。实测：去掉 `keep` 重建，报“安装程序检查到原有数据表已经存在.”并跳到安装页；加上 `keep` 重建，首页恢复正常。空库加 `keep` 的首次安装按源码（只在表已存在时才看 `keep`）不受影响，但没有单独实测。
- **等数据库就绪**：`depends_on` 加 `condition: service_healthy` 让 Typecho 容器等 MySQL 可以连接后再启动。否则第一次启动时 MySQL 还没准备好，自动安装会失败。
- 和普通安装一样，MySQL / PostgreSQL 的数据库必须事先存在，这里由 `MYSQL_DATABASE` 创建。
- 安装成功后管理员密码会以明文出现在 `docker compose logs` 中。可以不设置 `TYPECHO_USER_PASSWORD`，让它随机生成，登录后立即修改。
- 宿主机的 `./usr` 不存在时由 Docker 以 root 身份创建，启动脚本只会为它新建的子目录设置 `www-data` 所有者。使用 SQLite（数据库文件默认放在 `usr/` 下）时，安装程序以 `www-data` 运行，可能无法在 `usr/` 中创建数据库文件，需要先在宿主机上调整 `./usr` 的所有者（Debian 系镜像中 `www-data` 的 UID 通常是 33）。这一点未实测。
- 前面有 HTTPS 反向代理时，参照[HTTPS、反向代理与 CDN](./https-proxy.md) 传递 `Host` 和 `X-Forwarded-Proto`。

### 挂载整个 `/app` 的问题 {#mount-whole-app}

也可以把整个 `/app` 挂成命名卷，这样 `config.inc.php` 会一起保留，不需要 `keep`。但代价是：启动脚本只复制 `/app` 中**不存在**的文件，换用新版本镜像后，卷里旧的 `admin/`、`var/` 等不会被更新，**升级实际上不会发生**。实测把 `1.2.1-php8.0-apache` 换成 `1.3.0-php8.2-apache` 后，PHP 变成了 8.2，卷里的 `var/Typecho/Common.php` 仍是 `VERSION = '1.2.1'`，页面的 generator 也还是 Typecho 1.2.1。选择这种方式时，升级前要先删除卷中的 `admin`、`var`、`install`、`index.php`、`install.php`，再用新镜像启动，然后到后台完成升级（见[升级](../getting-started/upgrade.md)）。

### 升级 {#upgrade}

只挂载 `usr/`，并且保留 `TYPECHO_INSTALL: "1"`、全部 `TYPECHO_SITE_URL` / `TYPECHO_DB_*` 和 `TYPECHO_DB_NEXT: keep`（上面的 Compose 文件已经包含）：

1. [备份](./backup.md)数据库和 `./usr`；
2. 把 `image` 改成新版本标签，执行 `docker compose pull && docker compose up -d`；
3. 用管理员登录后台，会被带到升级页面，按提示完成升级。

这个流程用 `docker run` 实测过（1.2.1 → 1.3.0，MySQL）：新容器启动时重新生成配置，登录后台跳到 `upgrade.php`，执行后版本变为 1.3.0。漏掉 `TYPECHO_DB_NEXT=keep` 或 `TYPECHO_INSTALL=1` 时，新容器没有 `config.inc.php`，站点会跳到安装页；这时补上变量重建即可，数据库不受影响。

注意 `usr/themes` 等目录已经存在，新版本镜像带来的新主题不会自动出现在 `./usr/themes` 中。

## FPM 变体 + nginx {#fpm}

FPM 变体只运行 PHP-FPM，静态文件和 URL 重写由另一个 nginx 容器负责。关键是：**两个容器要看到同一份 `/app`**，并且 nginx 传给 PHP-FPM 的 `SCRIPT_FILENAME` 必须是 **FPM 容器里**的路径，否则会出现 “No input file specified.” 或 “File not found.”。

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

`typecho.conf` 与[Web 服务器配置](./web-server.md#nginx)中的 nginx 配置相同，只需要把 `root` 改成 `/app`，`fastcgi_pass` 改成 `typecho:9000`。

这里 `/app` 用了命名卷以便两个容器共享，因此同样存在[上文](#mount-whole-app)所说的升级问题。这个组合未经实测。

## 只读文件系统 {#read-only}

在 Kubernetes 等平台上，常见要求是容器根文件系统只读（`readOnlyRootFilesystem`、`docker run --read-only`），只有挂载的卷可写。

**官方镜像不能直接这样运行**：它的启动脚本每次启动都会写 `/usr/local/etc/php/conf.d/custom.ini`、`/etc/localtime`，并向 `/app` 复制文件；Apache、PHP-FPM 自身也需要可写的运行目录。为这些路径分别挂载 `tmpfs` 或许可行，但具体需要哪些路径**未经验证**。更可控的做法是基于官方 `php` 镜像构建自己的镜像，把 Typecho 程序和 `config.inc.php` 直接放进镜像。

无论用哪种镜像，Typecho 1.3 本身在运行时会写入下列位置（按源码整理），这些位置必须可写，其余都可以只读：

| 位置 | 何时写入 | 只读环境下的处理 |
|---|---|---|
| `usr/uploads/`（或 `__TYPECHO_UPLOAD_ROOT_DIR__`） | 上传、修改附件 | **必须**挂载可写卷。可以用 `__TYPECHO_UPLOAD_ROOT_DIR__` 指到卷的挂载点，见[附件存储位置](./uploads-storage.md) |
| SQLite 数据库文件及其所在目录 | 每次写数据库 | 改用 MySQL / PostgreSQL，或把数据库文件放在可写卷中（SQLite 还需要在同一目录创建临时日志文件） |
| PHP 临时目录（`sys_get_temp_dir()`） | 后台导出备份时先写临时文件；PHP 处理上传时 | 为 `/tmp` 挂载 `tmpfs` |
| PHP 会话目录（`session.save_path`） | 已登录用户的每个请求都会启动会话（`var/Widget/Init.php`） | 为会话目录挂载 `tmpfs` |
| 网站根目录的 `config.inc.php` | 仅安装时 | 构建镜像时放进去，或以只读方式挂载一份已有的配置 |
| 网站根目录的 `.htaccess` | 仅在 Apache `mod_php` 下开关永久链接时 | 构建时放进镜像；或定义 `__TYPECHO_REWRITE__` |
| `usr/themes/` 下的主题文件 | 在后台用主题编辑器保存时 | 定义 `define('__TYPECHO_THEME_WRITEABLE__', false);` 关闭在线编辑 |

另外，`__TYPECHO_BACKUP_DIR__`（默认 `usr/backups`）只用于读取：后台“从服务器”恢复时列出其中的 `.dat` 文件，不需要可写。

插件可能有自己的写入需求（缓存、日志等），请逐个确认。
