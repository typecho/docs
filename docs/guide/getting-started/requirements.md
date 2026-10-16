---
title: 环境要求
sidebar_position: 1
---

# 环境要求

本页列出运行 Typecho 1.3 所需的服务器环境。安装程序第一步会自动检查其中的 PHP 扩展和上传目录，不满足时会在页面上方给出提示。

:::caution 旧文档的说法已经过时
旧版文档写的“PHP 5.1 及以上”早已不适用。Typecho 1.3 需要 **PHP 7.4.0 或更高版本**；1.2.x 的要求是 PHP 7.2.0 或更高版本。
:::

## 一览

| 项目 | 要求 | 是否由程序检查 |
|---|---|---|
| PHP | 7.4.0 或更高版本 | 不做版本比较；低于 7.4 的 PHP 会直接报语法错误 |
| PHP 扩展 | `mbstring`、`json`、`Reflection`，外加至少一个数据库扩展 | 安装第一步检查 |
| 数据库 | MySQL / MariaDB 5.5.3+、SQLite 3.7.11+、PostgreSQL 9.1+ | **不检查**，见下文 |
| 目录权限 | `usr/uploads` 可写 | 安装第一步检查 |
| Web 服务器 | 任何能运行 PHP 的服务器（Apache、nginx、Caddy 等） | 不检查 |

## PHP 版本

Typecho 1.3 的源码使用了 PHP 7.4 才支持的语法（如带类型的类属性），所以在更低版本的 PHP 上无法运行。程序本身不做版本比较，PHP 版本过低时看到的是语法错误，而不是一条友好的提示。

官方持续集成在 PHP 7.4、8.0、8.1、8.2 上做语法检查。更新的 PHP 版本（8.3 及以上）不在 CI 矩阵中，但 1.3 已修复了一批 PHP 8.4 / 8.5 的弃用警告。

:::info 实测
2026-10-16 用 1.3.0 正式版实测：在 **PHP 7.4.33** 下以 `error_reporting=-1` 运行命令行安装成功，237 个 `.php` 文件 `php -l` 全部通过，浏览的前台 4 个页面和后台 8 个页面都是 200，**没有任何 notice、deprecation 或 warning**。PHP 8.2.30（官方镜像）和 PHP 8.5.11 下的安装与浏览同样没有告警。“低于 7.4 会报语法错误”没有实测。
:::

## PHP 扩展

安装第一步会逐项检查下面的扩展，缺少任何一项都无法继续：

| 扩展 | 说明 |
|---|---|
| `mbstring` | 多字节字符串处理 |
| `json` | PHP 8.0 起内置，无需单独安装 |
| `Reflection` | PHP 默认内置 |
| 数据库扩展（任选其一） | `pdo_mysql`、`mysqli`、`pdo_sqlite`、`sqlite3`、`pdo_pgsql`、`pgsql` |

安装程序会把服务器上可用的数据库适配器列在第二步的下拉框里，默认选中排在最前面的那个。排列顺序为：

| 适配器 | 对应扩展 | 数据库 |
|---|---|---|
| `Pdo_Mysql` | `pdo_mysql` | MySQL / MariaDB |
| `Pdo_SQLite` | `pdo_sqlite` | SQLite |
| `Pdo_Pgsql` | `pdo_pgsql` | PostgreSQL |
| `Mysqli` | `mysqli` | MySQL / MariaDB |
| `SQLite` | `sqlite3` | SQLite |
| `Pgsql` | `pgsql` | PostgreSQL |

:::tip
后台的网址输入框（“站点地址”“个人主页地址”）把 IDN（中文等国际化）域名转回原文显示时，用的是 `intl` 扩展提供的 `idn_to_utf8()`。没有 `intl` 也能正常运行，只是这类域名会以 `xn--` 开头的编码形式显示。
:::

## 数据库

Typecho 支持 MySQL（含 MariaDB 等兼容分支）、SQLite 和 PostgreSQL。官方 README 给出的最低版本是：

| 数据库 | 最低版本 |
|---|---|
| MySQL / MariaDB | 5.5.3 |
| SQLite | 3.7.11 |
| PostgreSQL | 9.1 |

:::note
这些版本号**只写在 README 里，程序并不检查**：安装程序和数据库适配器都不会读取或比较数据库服务器的版本。在更旧的版本上安装可能“看起来成功”，然后在使用中出错。
:::

实测过的只有较新的版本（2026-10-16，1.3.0 正式版，`Pdo_Mysql` / `Pdo_Pgsql`）：**MySQL 8.0.46** 和 **PostgreSQL 16.15** 上命令行安装都成功，各建了 7 张表，首页正常渲染，管理员能登录后台。SQLite 在 PHP 7.4 下也安装成功。上表的最低版本（MySQL 5.5.3、PostgreSQL 9.1、SQLite 3.7.11）**没有实测**，`Mysqli`、`Pgsql`、`SQLite`（非 PDO）适配器也没有测过。

MySQL 默认使用 `utf8mb4` 字符集（可以存储 emoji），安装界面上的说明是“选择 utf8mb4 编码至少需要 MySQL 5.5.3 版本”。

使用 MySQL 或 PostgreSQL 时，**数据库需要事先手动创建**，安装程序只建表，不建库。SQLite 不需要，安装程序会直接创建数据库文件。

## 目录权限

安装第一步会检查 `usr/uploads` 目录：

- 目录不存在时，尝试以 `0755` 权限创建；
- 目录存在但不可读写时，尝试改为 `0755`；
- 都失败时报错：“上传目录无法写入, 请手动将安装目录下的 /usr/uploads 目录的权限设置为可写然后继续升级”。

此外，安装程序会尝试在网站根目录写入 `config.inc.php`。根目录不可写时安装不会失败，而是给出配置文件内容让你手动创建，详见[安装](./installation.mdx)。

使用 SQLite 时，数据库文件默认放在 `usr/` 目录下，所以 `usr/` 本身也需要可写。

## 下一步

- [安装](./installation.mdx)：通过浏览器完成安装。
- [命令行与无人值守安装](./cli-install.md)：在容器、CI 或脚本中安装。
