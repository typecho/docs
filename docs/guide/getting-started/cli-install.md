---
title: 命令行与无人值守安装
sidebar_position: 3
---

# 命令行与无人值守安装

Typecho 没有单独的命令行工具。在命令行里直接运行安装程序：

```bash
php install.php
```

`install.php` 发现自己运行在 CLI 下时，会跳过网页界面，从**环境变量**读取全部配置，一次跑完 Web 安装的三个步骤（环境检查 → 数据库 → 管理员帐号）。容器镜像、CI 流水线和部署脚本都用这种方式安装。

## 快速示例

请用 Web 服务器运行 PHP 的帐号（例如 `www-data`）执行安装。安装程序的环境检查只检查执行命令的帐号能否写入；用 root 等其他帐号安装时，`usr/uploads`、SQLite 数据库文件及其所在目录会属于那个帐号，之后网页上传附件、写入数据库可能失败。不方便切换帐号时，安装后把这些路径的所有者改成 Web 服务器的帐号。

SQLite，最少的配置。数据库文件放在网站目录之外，并事先建好它所在的目录、让 Web 服务器的帐号可写（放在 `usr/` 下的 `.db` 文件可能被直接下载，见[安全加固](../operations/security.md#sensitive-files)）：

```bash
cd /srv/www/typecho
sudo -u www-data env \
  TYPECHO_SITE_URL=https://blog.example.com \
  TYPECHO_DB_ADAPTER=Pdo_SQLite \
  TYPECHO_DB_FILE=/srv/data/typecho/site.db \
  php install.php
```

MySQL：

```bash
cd /srv/www/typecho
export TYPECHO_SITE_URL=https://blog.example.com
export TYPECHO_DB_ADAPTER=Pdo_Mysql
export TYPECHO_DB_HOST=127.0.0.1
export TYPECHO_DB_USER=typecho
export TYPECHO_DB_PASSWORD='数据库密码'
export TYPECHO_DB_DATABASE=typecho
export TYPECHO_USER_NAME=admin
export TYPECHO_USER_PASSWORD='管理员密码'
export TYPECHO_USER_MAIL=admin@example.com
php install.php
```

成功时的输出：

```text
Typecho 1.3.0
PHP 8.2.x
安装成功
您的用户名是 admin
您的密码是 管理员密码
```

:::caution
和 Web 安装一样，MySQL 和 PostgreSQL 的**数据库必须事先创建**，否则会报“数据库: "xxx"不存在，请手动创建后重试”。
:::

## 环境变量

下表是 `install.php` 在 CLI 模式下读取的全部环境变量。“默认值”指变量未设置时使用的值。

### 站点与语言

| 变量 | 默认值 | 作用 |
|---|---|---|
| `TYPECHO_SITE_URL` | 无，**实际必填** | 站点地址，写入选项 `siteUrl`，同时作为管理员帐号的个人主页地址。未设置时前两步照常执行，到第三步才报“请填写站点地址”并退出 |
| `TYPECHO_LANG` | `zh_CN` | 写入选项 `lang`（后台界面语言），例如 `en_US`。只有 `usr/langs` 里有对应语言包时，界面和安装程序的输出才会变成该语言 |

### 数据库

| 变量 | 默认值 | 作用 |
|---|---|---|
| `TYPECHO_DB_ADAPTER` | 服务器上第一个可用的适配器 | 取值 `Pdo_Mysql`、`Pdo_SQLite`、`Pdo_Pgsql`、`Mysqli`、`SQLite`、`Pgsql`，必须是当前 PHP 能用的。默认顺序见[环境要求](./requirements.md#php-扩展)，装了 `pdo_mysql` 时默认就是 `Pdo_Mysql` |
| `TYPECHO_DB_PREFIX` | `typecho_` | 表前缀。安装程序接受 1–16 位字母、数字、下划线或连字符，但 SQLite 下含连字符或以数字开头的前缀会建表失败；请以字母或下划线开头，只用字母、数字和下划线 |
| `TYPECHO_DB_HOST` | `localhost` | MySQL / PostgreSQL 地址。MySQL 下含有 `/` 时视为 Unix socket 路径，并忽略端口 |
| `TYPECHO_DB_PORT` | MySQL `3306`，PostgreSQL `5432` | 端口，必须是整数 |
| `TYPECHO_DB_USER` | 无 | MySQL / PostgreSQL 必填 |
| `TYPECHO_DB_PASSWORD` | 无 | 数据库密码，可为空 |
| `TYPECHO_DB_DATABASE` | 无 | 数据库名，MySQL / PostgreSQL 必填 |
| `TYPECHO_DB_CHARSET` | MySQL `utf8mb4`，PostgreSQL `utf8` | MySQL 可选 `utf8`、`utf8mb4`；PostgreSQL 只接受 `utf8` |
| `TYPECHO_DB_ENGINE` | `InnoDB` | 仅 MySQL，可选 `InnoDB`、`MyISAM` |
| `TYPECHO_DB_SSL_CA` | 无 | 仅 MySQL：CA 证书路径，文件必须存在。设置后会同时启用服务端证书验证 |
| `TYPECHO_DB_SSL_VERIFY` | `off` | `on` 或 `off`。MySQL：是否验证服务端证书；PostgreSQL：`on` 时以 `sslmode=require` 连接 |
| `TYPECHO_DB_FILE` | `usr/` 下随机文件名的 `.db` 文件 | 仅 SQLite：数据库文件路径。可写绝对路径；只写文件名（如 `site.db`）时放在 `usr/` 下。绝对路径只能包含字母、数字、`/`、`.`、`_`、`-`；只写文件名时只能用字母、数字加扩展名 |
| `TYPECHO_DB_NEXT` | `none` | 数据表已存在时怎么办，见下文 |
| `TYPECHO_DB_DSN` | 无 | 会被读取，但 1.3.0 的安装程序**不会使用它**：生成的配置里没有 DSN 一项，设置了也不起作用 |

`TYPECHO_DB_NEXT` 的取值与 Web 安装中的隐藏字段 `dbNext` 相同：

| 取值 | 效果 |
|---|---|
| `none` | 正常安装；同前缀的数据表已存在时报“安装程序检查到原有数据表已经存在.”并退出 |
| `delete` | 先删除同前缀的 7 张表再重新安装，**原有数据全部丢失**。PostgreSQL 下删表不会删除安装时建立的序列，新表因此建不起来，安装无法完成；在 PostgreSQL 上重装请改用新建的空数据库 |
| `keep` | 保留已存在的表：如果原有数据完整，直接结束并以 0 退出（不再创建管理员）；如果表在而数据不完整，继续执行第三步 |
| `config` | 要求网站根目录已有 `config.inc.php`，没有时报“没有检测到您手动创建的配置文件, 请检查后再次创建”并退出 |

其他值会报 `dbNext: 确认您的配置` 并退出。

### 管理员帐号

| 变量 | 默认值 | 作用 |
|---|---|---|
| `TYPECHO_USER_NAME` | `typecho` | 管理员用户名，不超过 32 个字符 |
| `TYPECHO_USER_PASSWORD` | 随机 8 位 | 管理员密码。未设置时随机生成，并打印在输出中 |
| `TYPECHO_USER_MAIL` | `admin@localhost.local` | 管理员邮箱，不超过 200 个字符 |

:::warning
安装成功后，管理员密码会以明文打印到标准输出。在 CI 中运行时，请注意构建日志的可见范围，或在安装后立即修改密码。
:::

## 输出与退出码

每次运行都会先打印两行版本信息（`Typecho 1.3.0` 和 `PHP x.y.z`），然后：

| 情况 | 输出 | 退出码 |
|---|---|---|
| 安装成功 | “安装成功”及用户名、密码 | `0` |
| `TYPECHO_DB_NEXT=keep` 且原有数据完整 | 无更多输出 | `0` |
| 配置校验失败 | 每行一条 `字段名: 错误信息`，如 `dbUser: 确认您的配置`、`userUrl: 请填写站点地址` | `1` |
| 环境检查失败、无法连接数据库、数据库不存在、数据表已存在等 | 一行或多行错误信息 | `1` |
| 站点已经安装完成 | 无更多输出 | `1` |

脚本里可以据此判断是否需要安装：

```bash
php install.php || echo "安装失败，或站点已经安装"
```

注意“已经安装”与“安装失败”的退出码都是 `1`，需要区分时请检查输出。

## 半途失败后重试

CLI 安装在数据库这一步就会写入 `config.inc.php` 并建表，如果之后第三步失败（最常见的是忘了设置 `TYPECHO_SITE_URL`），站点会处于“有配置、有表、没有数据”的状态。这时直接重跑会报“安装程序检查到原有数据表已经存在.”。请用**第一次安装时的全部环境变量**（适配器、表前缀、`TYPECHO_DB_*` 等）重跑，补上缺少的变量并加上 `TYPECHO_DB_NEXT=keep`。以 MySQL 为例：

```bash
TYPECHO_SITE_URL=https://blog.example.com \
TYPECHO_DB_ADAPTER=Pdo_Mysql \
TYPECHO_DB_HOST=127.0.0.1 \
TYPECHO_DB_USER=typecho \
TYPECHO_DB_PASSWORD='数据库密码' \
TYPECHO_DB_DATABASE=typecho \
TYPECHO_DB_NEXT=keep \
php install.php
```

第一次用 `export` 设置的变量还在当前 shell 中时，只补上缺少的变量和 `TYPECHO_DB_NEXT=keep` 也可以。安装程序会先校验这些变量（MySQL / PostgreSQL 缺少 `TYPECHO_DB_USER`、`TYPECHO_DB_DATABASE` 时直接报错退出），表前缀也以变量为准，未设置时按 `typecho_` 处理；校验通过后，如果根目录已经存在 `config.inc.php`，才直接使用其中的数据库连接，不再按 `TYPECHO_DB_HOST` 等变量重新连接。

## 容器

官方镜像 `joyqi/typecho` 的启动脚本以 `www-data` 用户运行同一个 `install.php`，并保留全部环境变量。所以 `TYPECHO_SITE_URL`、`TYPECHO_DB_*`、`TYPECHO_USER_*` 就是上面表格里的变量，原样传入即可，含义和取值以本页为准。

镜像自己定义的变量只有五个：

| 变量 | 作用 |
|---|---|
| `TYPECHO_INSTALL` | 设为 `1` 时，每次启动都运行一次 `install.php` |
| `TIMEZONE` | PHP 的 `date.timezone`，默认为空 |
| `MEMORY_LIMIT` | PHP `memory_limit`，默认 `128m` |
| `MAX_POST_BODY` | PHP `upload_max_filesize` 和 `post_max_size`，默认 `10m` |
| `MAX_EXECUTION_TIME` | PHP `max_execution_time`，默认 `30` |

镜像**没有 `latest` 标签**（截至 2026 年 10 月，Docker Hub 上共 70 个标签），请写明版本，如 `1.3.0-php8.2-apache`。`nightly-*` 标签由 `master` 分支构建，包含未发布的改动，不要用于生产。镜像说明页上有几处已经过时（例如 `TYPECHO_DB_NEXT=force` 是无效值，要用 `delete`），差异和推荐的挂载方式见[容器与云主机部署](../operations/containers.md)。
