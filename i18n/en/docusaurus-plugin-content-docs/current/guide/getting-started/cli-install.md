---
title: Command-line and unattended installation
sidebar_position: 3
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Command-line and unattended installation

Typecho has no separate command-line tool. You run the installer directly on the command line:

```bash
php install.php
```

When `install.php` detects that it is running under the CLI, it skips the web interface, reads its entire configuration from **environment variables**, and runs the three steps of the web installation in one go (environment check → database → administrator account). Container images, CI pipelines and deployment scripts all install this way.

## Quick examples

SQLite, with the minimum configuration:

```bash
cd /srv/www/typecho
TYPECHO_SITE_URL=https://blog.example.com \
TYPECHO_DB_ADAPTER=Pdo_SQLite \
TYPECHO_DB_FILE=site.db \
php install.php
```

MySQL:

```bash
cd /srv/www/typecho
export TYPECHO_SITE_URL=https://blog.example.com
export TYPECHO_DB_ADAPTER=Pdo_Mysql
export TYPECHO_DB_HOST=127.0.0.1
export TYPECHO_DB_USER=typecho
export TYPECHO_DB_PASSWORD='your-db-password'
export TYPECHO_DB_DATABASE=typecho
export TYPECHO_USER_NAME=admin
export TYPECHO_USER_PASSWORD='your-admin-password'
export TYPECHO_USER_MAIL=admin@example.com
php install.php
```

Output on success:

```text
Typecho 1.3.0
PHP 8.2.x
Installation successful
Your user name is admin
Your password is your-admin-password
```

:::caution
As with the web installation, for MySQL and PostgreSQL **the database must be created beforehand**; otherwise you get "Database: "xxx" does not exist, please create it manually and try again".
:::

## Environment variables {#environment-variables}

The tables below list every environment variable that `install.php` reads in CLI mode. "Default" is the value used when the variable is not set.

### Site and language

| Variable | Default | Purpose |
|---|---|---|
| `TYPECHO_SITE_URL` | none, **required in practice** | The site URL; written to the option `siteUrl` and also used as the homepage URL of the administrator account. If it is not set, the first two steps run as usual and the installer only exits with "Please enter the site URL" in step 3 |
| `TYPECHO_LANG` | `zh_CN` | Written to the option `lang` (the admin interface language), e.g. `en_US`. The interface and the installer output only switch to that language when `usr/langs` contains the matching language pack |

### Database

| Variable | Default | Purpose |
|---|---|---|
| `TYPECHO_DB_ADAPTER` | The first adapter available on the server | One of `Pdo_Mysql`, `Pdo_SQLite`, `Pdo_Pgsql`, `Mysqli`, `SQLite`, `Pgsql`; it must be usable by the current PHP. For the default order see [Requirements](./requirements.md#php-extensions); when `pdo_mysql` is installed the default is `Pdo_Mysql` |
| `TYPECHO_DB_PREFIX` | `typecho_` | Table prefix, 1–16 letters, digits, underscores or hyphens |
| `TYPECHO_DB_HOST` | `localhost` | MySQL / PostgreSQL host. For MySQL, a value containing `/` is treated as a Unix socket path and the port is ignored |
| `TYPECHO_DB_PORT` | MySQL `3306`, PostgreSQL `5432` | Port; must be an integer |
| `TYPECHO_DB_USER` | none | Required for MySQL / PostgreSQL |
| `TYPECHO_DB_PASSWORD` | none | Database password; may be empty |
| `TYPECHO_DB_DATABASE` | none | Database name; required for MySQL / PostgreSQL |
| `TYPECHO_DB_CHARSET` | MySQL `utf8mb4`, PostgreSQL `utf8` | MySQL accepts `utf8` or `utf8mb4`; PostgreSQL only accepts `utf8` |
| `TYPECHO_DB_ENGINE` | `InnoDB` | MySQL only; `InnoDB` or `MyISAM` |
| `TYPECHO_DB_SSL_CA` | none | MySQL only: CA certificate path; the file must exist. Setting it also enables server certificate verification |
| `TYPECHO_DB_SSL_VERIFY` | `off` | `on` or `off`. MySQL: whether to verify the server certificate; PostgreSQL: `on` connects with `sslmode=require` |
| `TYPECHO_DB_FILE` | A `.db` file with a random name under `usr/` | SQLite only: database file path. You can give an absolute path; a bare file name (such as `site.db`) is placed under `usr/`. An absolute path may only contain letters, digits, `/`, `.`, `_` and `-`; a bare file name may only use letters and digits plus an extension |
| `TYPECHO_DB_NEXT` | `none` | What to do when the tables already exist, see below |
| `TYPECHO_DB_DSN` | none | Read, but the 1.3.0 installer **does not use it**: the generated configuration has no DSN entry, so setting it has no effect |

`TYPECHO_DB_NEXT` takes the same values as the hidden field `dbNext` in the web installation:

| Value | Effect |
|---|---|
| `none` | Normal installation; if tables with the same prefix already exist, reports "The installer has detected that the existing tables already exist." and exits |
| `delete` | Drops the 7 tables with the same prefix first and then installs again; **all existing data is lost** |
| `keep` | Keeps the existing tables: if the existing data is complete, it finishes immediately and exits with 0 (no administrator is created); if the tables exist but the data is incomplete, it goes on with step 3 |
| `config` | Requires a `config.inc.php` in the site root; if there is none, reports "No manually created configuration file was detected, please check and create it again" and exits |

Any other value reports `dbNext: Please check your configuration` and exits.

### Administrator account

| Variable | Default | Purpose |
|---|---|---|
| `TYPECHO_USER_NAME` | `typecho` | Administrator user name, at most 32 characters |
| `TYPECHO_USER_PASSWORD` | random, 8 characters | Administrator password. If not set, one is generated randomly and printed in the output |
| `TYPECHO_USER_MAIL` | `admin@localhost.local` | Administrator email, at most 200 characters |

:::warning
After a successful installation, the administrator password is printed in plain text to standard output. When you run this in CI, mind who can see the build log, or change the password right after installation.
:::

## Output and exit codes

Every run first prints two lines of version information (`Typecho 1.3.0` and `PHP x.y.z`), then:

| Situation | Output | Exit code |
|---|---|---|
| Installation succeeded | "Installation successful" with the user name and password | `0` |
| `TYPECHO_DB_NEXT=keep` and the existing data is complete | nothing more | `0` |
| Configuration validation failed | One `field: error message` per line, e.g. `dbUser: Please check your configuration`, `userUrl: Please enter the site URL` | `1` |
| Environment check failed, cannot connect to the database, database does not exist, tables already exist, etc. | One or more lines of error messages | `1` |
| The site is already installed | nothing more | `1` |

A script can use this to decide whether installation is needed:

```bash
php install.php || echo "Installation failed, or the site is already installed"
```

Note that "already installed" and "installation failed" both exit with `1`; check the output when you need to tell them apart.

## Retrying after a partial failure

The CLI installation writes `config.inc.php` and creates the tables during the database step. If step 3 then fails (most commonly because `TYPECHO_SITE_URL` was not set), the site is left with a configuration, tables, but no data. Running it again as is reports "The installer has detected that the existing tables already exist."; add the missing variable and `TYPECHO_DB_NEXT=keep`:

```bash
TYPECHO_SITE_URL=https://blog.example.com TYPECHO_DB_NEXT=keep php install.php
```

When `config.inc.php` already exists in the site root, the installer uses the database connection in it and does not reconnect according to `TYPECHO_DB_HOST` and the other variables.

## Containers

The start-up script of the official image `joyqi/typecho` runs the same `install.php` as the `www-data` user and passes all environment variables through. So `TYPECHO_SITE_URL`, `TYPECHO_DB_*` and `TYPECHO_USER_*` are exactly the variables in the tables above: pass them as they are; this page is authoritative for their meaning and values.

The image defines only five variables of its own:

| Variable | Purpose |
|---|---|
| `TYPECHO_INSTALL` | When set to `1`, runs `install.php` once on every start |
| `TIMEZONE` | PHP `date.timezone`, empty by default |
| `MEMORY_LIMIT` | PHP `memory_limit`, default `128m` |
| `MAX_POST_BODY` | PHP `upload_max_filesize` and `post_max_size`, default `10m` |
| `MAX_EXECUTION_TIME` | PHP `max_execution_time`, default `30` |

The image has **no `latest` tag** (as of October 2026 there are 70 tags on Docker Hub); always specify a version, such as `1.3.0-php8.2-apache`. The `nightly-*` tags are built from the `master` branch and contain unreleased changes; do not use them in production. Several points on the image's description page are out of date (for example, `TYPECHO_DB_NEXT=force` is an invalid value; use `delete`). For the differences and the recommended way to mount volumes, see [Container and cloud host deployment](../operations/containers.md).
