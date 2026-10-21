---
title: Getting help
sidebar_position: 5
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# Getting help

When the documentation does not answer your question, you can ask for help through the three channels below. The footer of the admin pages (except the login and registration pages) links to two of them: **Support forum** and **Report a bug**.

| Channel | URL | Good for |
|---|---|---|
| Support forum | [forum.typecho.org](https://forum.typecho.org) | Usage questions, how to use themes and plugins, sharing experience. **Support forum** in the admin footer |
| GitHub Issues | [github.com/typecho/typecho/issues](https://github.com/typecho/typecho/issues) | Bugs in the program itself and feature requests. **Report a bug** in the admin footer |
| Telegram | [t.me/typechodev](https://t.me/typechodev) | Following development news and talking with the developers. This is Typecho's Telegram channel, listed in the Community section of `CONTRIBUTING.md` in the Typecho repository |

:::tip Choose the right channel
- For "how do I…" and "why does my site do this" questions, go to the forum first.
- Only file a GitHub issue once you have confirmed a bug in the Typecho program and can reproduce it reliably.
- For problems with a third-party theme or plugin, contact its author first.
:::

:::warning Do not report security vulnerabilities in public
If you find a security vulnerability, do not post the details publicly on the forum or in Issues. Follow the instructions in `SECURITY.md` in the Typecho repository and email `security@typecho.org`.
:::

## Troubleshoot first

Before you ask, these steps often reveal the cause directly, and they also make your description more precise:

1. **Check the version**: the admin footer shows the current version number. Upgrade to the latest stable release first and see whether the problem persists; see [Upgrading](../getting-started/upgrade.md).
2. **Check the environment**: compare the PHP version and extensions against the [requirements](../getting-started/requirements.md).
3. **Rule out plugins and themes**: disable all plugins, switch back to the bundled `default` theme, and see whether the problem still occurs.
4. **Look at the error messages**: note the error shown on the page, and the PHP and web server error logs.

## How to describe the problem

A problem that can be solved quickly is one that others can **reproduce**. The bug template on GitHub ("Report a bug") asks for the parts below; when you ask on the forum, following the same structure is recommended:

| Part | What to write |
|---|---|
| Description | Say clearly and briefly what went wrong |
| Steps to reproduce | Which page you started from, what you clicked, what you saw, step by step |
| Expected result | What you expected to see |
| Screenshots | Attach screenshots or a screen recording if you can |
| Platform | The 6 items of environment information below |

### Environment information

| Field | Example | Where to find it |
|---|---|---|
| Operating system version | Ubuntu 24.04 | Run `cat /etc/os-release` on the server |
| Apache/Nginx version | nginx 1.26.2 | `nginx -v` or `apachectl -v` |
| Database version | MySQL 8.0.39 | Connect to the database Typecho uses and run `SELECT VERSION();` (MySQL, MariaDB, PostgreSQL) or `SELECT sqlite_version();` (SQLite). `mysql --version` shows the client version, not the database server's version |
| PHP version | PHP 8.2.20 | `php -v`. Note that the command line and the web server may not use the same PHP |
| Typecho version | 1.3.0 | The admin footer |
| Browser version | Chrome 130 | The browser's "About" page |

Also include:

- **the complete error message**, copied as text exactly as shown, not just a screenshot;
- **recent changes**, such as a fresh upgrade, a newly enabled plugin, or a change of server or domain;
- **what you have already tried**, and the results.

:::caution
Before you paste configuration or logs, remove sensitive content such as database passwords, the connection details in `config.inc.php`, and cookies.
:::

### Example

```text
Description: after upgrading to 1.3.0, clicking "Complete upgrade" keeps the page loading, and it ends with a 504.

Steps to reproduce:
1. On a 1.2.1 site, replace admin/ var/ install/ index.php install.php with the new files
2. Log in to the admin panel as an administrator; you are redirected to the upgrade page
3. Click "Complete upgrade"; the page keeps loading for about 60 seconds, then returns 504

Expected result: "The upgrade has been completed" is shown

Platform:
- OS version: Debian 12
- Apache/Nginx version: nginx 1.22.1
- Database version: MariaDB 10.11.6
- PHP version: PHP 8.2.20
- Typecho version: 1.2.1 → 1.3.0
- Browser version: Firefox 131

Already tried: disabled all plugins and retried, same result. The site has about 800 attachments.
```

(The symptoms in this example match the known issue described on the [Upgrading](../getting-started/upgrade.md#known-issue-attachments) page.)
