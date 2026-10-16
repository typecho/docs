---
title: Getting help
sidebar_position: 5
translation: machine
source_commit: 97eccd0fe4871e7b33f99f7b47436e058e9520d3
---

# Getting help

When the documentation does not answer your question, you can ask for help through the three channels below. The footer of the admin pages (except the login and registration pages) links to two of them: **Support forum** (支持论坛) and **Report a bug** (报告错误).

| Channel | Address | Good for |
|---|---|---|
| Support forum | [forum.typecho.org](https://forum.typecho.org) | Usage questions, how to use themes and plugins, sharing experience. **Support forum** in the admin footer |
| GitHub Issues | [github.com/typecho/typecho/issues](https://github.com/typecho/typecho/issues) | Bugs in the program itself and feature requests. **Report a bug** in the admin footer |
| Telegram | [t.me/typechodev](https://t.me/typechodev) | Typecho's Telegram channel, listed in the Community section of `CONTRIBUTING.md` in the Typecho repository; good for following development news and chatting |

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

A problem that can be solved quickly is one that others can **reproduce**. The bug template on GitHub ("上报 BUG", Report a bug) asks for the parts below; when you ask on the forum, following the same structure is recommended:

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
| Database version | MySQL 8.0.39 | `mysql --version`, or run `SELECT VERSION();` in the database |
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
描述：升级到 1.3.0 后，点击“完成升级”页面一直加载，最终 504。

复现方式：
1. 从 1.2.1 替换 admin/ var/ install/ index.php install.php
2. 管理员登录后台，被带到升级页
3. 点击“完成升级”，页面持续加载约 60 秒后返回 504

期望的结果：显示“升级已经完成”

平台：
- 操作系统版本：Debian 12
- Apache/Nginx 版本：nginx 1.22.1
- 数据库版本：MariaDB 10.11.6
- PHP 版本：PHP 8.2.20
- Typecho 版本：1.2.1 → 1.3.0
- 浏览器版本：Firefox 131

已尝试：禁用全部插件后重试，结果相同。站点约有 800 个附件。
```

(This example is exactly the known issue described on the [Upgrading](../getting-started/upgrade.md#已知问题附件超过-100-个的站点升级时卡住) page.)
