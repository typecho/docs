---
title: Requirements
sidebar_position: 1
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# Requirements

This page lists the server environment that Typecho 1.3 needs. The first step of the installer checks the PHP extensions and the upload directory listed here, and shows a message at the top of the page when something is missing.

:::caution PHP 7.4 is the minimum version
Typecho 1.3 requires **PHP 7.4.0 or later**; 1.2.x requires PHP 7.2.0 or later. PHP 5.x and 7.0–7.3 cannot run 1.3.
:::

## Overview

| Item | Requirement | Checked by the program? |
|---|---|---|
| PHP | 7.4.0 or later | No version comparison; on PHP older than 7.4 you get a syntax error straight away |
| PHP extensions | `mbstring`, `json`, `Reflection`, plus at least one database extension | Checked in step 1 of the installer |
| Database | MySQL / MariaDB 5.5.3+, SQLite 3.7.11+, PostgreSQL 9.1+ | **Not checked** (see below) |
| Directory permissions | `usr/uploads` writable | Checked in step 1 of the installer |
| Web server | Any server that can run PHP (Apache, nginx, Caddy, etc.) | Not checked |

## PHP version

The Typecho 1.3 source code uses syntax that only PHP 7.4 supports (such as typed class properties), so it cannot run on older PHP versions. The program does not compare versions itself: when PHP is too old, you see a syntax error rather than a friendly message.

The official continuous integration runs syntax checks on PHP 7.4, 8.0, 8.1 and 8.2. Newer PHP versions (8.3 and later) are not in the CI matrix, but 1.3 fixes a number of PHP 8.4 / 8.5 deprecation warnings.

## PHP extensions {#php-extensions}

In step 1, the installer checks for the extensions below. If any of them is missing, the installation cannot continue:

| Extension | Notes |
|---|---|
| `mbstring` | Multibyte string handling |
| `json` | Built in since PHP 8.0, no separate installation needed |
| `Reflection` | Built into PHP by default |
| Database extension (any one) | `pdo_mysql`, `mysqli`, `pdo_sqlite`, `sqlite3`, `pdo_pgsql`, `pgsql` |

The installer lists the database adapters available on the server in the drop-down in step 2, and selects the first one by default. The order is:

| Adapter | Extension | Database |
|---|---|---|
| `Pdo_Mysql` | `pdo_mysql` | MySQL / MariaDB |
| `Pdo_SQLite` | `pdo_sqlite` | SQLite |
| `Pdo_Pgsql` | `pdo_pgsql` | PostgreSQL |
| `Mysqli` | `mysqli` | MySQL / MariaDB |
| `SQLite` | `sqlite3` | SQLite |
| `Pgsql` | `pgsql` | PostgreSQL |

:::tip
The **Site URL** and **Homepage URL** fields in the admin panel use `idn_to_utf8()` from the `intl` extension to display IDN (internationalized, e.g. Chinese) domain names in their original form. Typecho works without `intl`; such domain names are then shown in their encoded form starting with `xn--`.
:::

## Database

Typecho supports MySQL (including compatible forks such as MariaDB), SQLite and PostgreSQL. The minimum versions given in the official README are:

| Database | Minimum version |
|---|---|
| MySQL / MariaDB | 5.5.3 |
| SQLite | 3.7.11 |
| PostgreSQL | 9.1 |

:::note
These version numbers **appear only in the README; the program does not check them**: neither the installer nor the database adapters read or compare the database server version. Installing on an older version may "appear to succeed" and then fail during use.
:::

MySQL uses the `utf8mb4` character set by default (it can store emoji). The installer's note says "the utf8mb4 encoding requires at least MySQL 5.5.3".

With MySQL or PostgreSQL, **you must create the database manually beforehand**; the installer only creates tables, not the database. SQLite does not need this: the installer creates the database file itself.

## Directory permissions

Step 1 of the installer checks the `usr/uploads` directory:

- if the directory does not exist, it tries to create it with permissions `0755`;
- if the directory exists but is not readable and writable, it tries to change it to `0755`;
- if both fail, it reports the error "The upload directory cannot be written; please manually make the /usr/uploads directory under the installation directory writable and then continue the upgrade".

The installer also tries to write `config.inc.php` to the site root. If the root is not writable, the installation does not fail; instead it shows the contents of the configuration file so you can create it manually. See [Installation](./installation.mdx).

With SQLite, the database file is placed in the `usr/` directory by default, so `usr/` itself must be writable as well.

## Next steps

- [Installation](./installation.mdx): install through the browser.
- [Command-line and unattended installation](./cli-install.md): install in a container, in CI or from a script.
