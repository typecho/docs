---
title: Upgrading
sidebar_position: 4
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Upgrading

Upgrading Typecho takes two steps: first **replace the program files**, then **run the upgrade program** in the admin panel (which updates the database). This page uses an upgrade from 1.2.x to 1.3.0 as the example.

:::danger Back up first
The upgrade modifies the database and cannot be rolled back automatically. Before you start, back up the **database** (with the database's own export tool; it contains all settings), the **`usr/` directory** (themes, plugins, attachments; with SQLite the database file is also here by default) and **`config.inc.php`**. The **Backup** feature in the admin panel only exports content, without settings or files, and cannot replace these three. See [Backup and restore](../operations/backup.md) for how to do it.
:::

## Where upgrades start: the new version notice on the dashboard

When a new version is released, editors and administrators who open the admin dashboard (the **Overview** page) see a notice below the page title:

> 您当前使用的版本是 1.2.1
> **官方最新版本是 1.3.0**

(You are currently using version 1.2.1 / **The latest official version is 1.3.0**.) The second line links to the official download page [typecho.org/download](https://typecho.org/download). Where the notice comes from:

- the admin panel requests `https://typecho.org/version.json` through `/action/ajax?do=checkVersion` (`Widget\Ajax::checkVersion()`), and shows the notice only when the officially released version is newer than the current one;
- the server must be able to reach typecho.org; the request times out after 10 seconds, and nothing is shown on failure;
- the result is cached in the browser's `sessionStorage`, so it is not checked again within the same browser session.

Not seeing the notice does not mean there is no new version; you can also check [GitHub Releases](https://github.com/typecho/typecho/releases) directly.

## Can I upgrade directly? {#direct-upgrade}

The 1.3 upgrade program only supports upgrading from **1.1.0 or later** (`Widget\Upgrade::MIN_VERSION = '1.1.0'`).

| Current version | Upgrade path |
|---|---|
| 1.2.x | Upgrade directly to 1.3.0 |
| 1.1.x | **Upgrading to 1.2.1 first and then to 1.3.0 is recommended** (reason below) |
| Before 1.1.0 (0.x, 1.0) | You must upgrade to 1.2.1 first and then to 1.3.0. Running the 1.3 upgrade program directly reports "Please upgrade to version 1.1.0 first" |

**Why 1.1 should go through 1.2.1**: 1.3 removed all historical upgrade scripts of 1.2.1 and earlier, keeping only the single step for 1.3.0 itself. A 1.1 site's version number passes the `MIN_VERSION` check, but the two table structure changes in 1.2.1 (`comments.agent` changed to `varchar(511)`, `comments.url` changed to `varchar(255)`) no longer exist in 1.3, so these two columns are not modified when 1.1 upgrades directly to 1.3. Upgrading once with 1.2.1 first fills that gap.

Download the 1.2.1 package from the [v1.2.1 page on GitHub Releases](https://github.com/typecho/typecho/releases/tag/v1.2.1) (asset `typecho.zip`); the upgrade steps are the same as on this page.

## Step 1: Replace the program files

1. Download the new package and unpack it into a temporary directory:

   ```bash
   wget https://github.com/typecho/typecho/releases/latest/download/typecho.zip
   unzip typecho.zip -d /tmp/typecho-new
   ```

2. Delete the old program files in the site directory, then copy them over from the new package (the site directory is `/srv/www/typecho` below):

   ```bash
   cd /srv/www/typecho
   rm -rf admin var install index.php install.php
   cp -R /tmp/typecho-new/admin /tmp/typecho-new/var /tmp/typecho-new/install \
         /tmp/typecho-new/index.php /tmp/typecho-new/install.php .
   ```

These 5 items are what you replace:

| Replace | Notes |
|---|---|
| `admin/` | The admin panel. If your site has renamed the admin directory (`__TYPECHO_ADMIN_DIR__`), replace the directory with your own name |
| `var/` | The program core |
| `install/` | The installer's table-creation scripts and forms |
| `index.php` | The entry point |
| `install.php` | The installer |

| Do not overwrite | Reason |
|---|---|
| `config.inc.php` | Holds the database connection; the new package does not contain this file |
| `usr/` | Your themes, plugins and uploaded files |

Sites that deleted `install.php` and `install/` after installation, as recommended in [Security hardening](../operations/security.md#remove-installer), can skip copying these two items: the upgrade program in the admin panel does not use them.

:::tip About the new theme
The new package's `usr/themes/` contains `classic-22`, the official theme added in 1.3. To use it, copy just `usr/themes/classic-22` into the site directory. Do not overwrite `usr/themes/default` as a whole, or your changes to the default theme will be lost.
:::

Deleting before copying clears out old files that the new version has removed. If you only overwrite without deleting, old files stay behind in `admin/` and `var/`.

## Step 2: Run the upgrade in the admin panel {#step-2-run-the-upgrade}

After replacing the files, log in to the admin panel with an **administrator** account. As long as the program version is newer than the version recorded in the database, visiting any admin page takes you to the upgrade page (`admin/upgrade.php`), except the **Backup** page, so that you can make one last backup:

> **检测到新版本!**
> 您已经更新了系统程序, 我们还需要执行一些后续步骤来完成升级
> 此程序将把您的系统从 **1.2.1** 升级到 **1.3.0**

(**New version detected!** / You have updated the system program; some follow-up steps are needed to complete the upgrade / This program will upgrade your system from **1.2.1** to **1.3.0**.) Click <kbd>完成升级 »</kbd> (Complete upgrade »); seeing "升级已经完成" (The upgrade has been completed) means it succeeded. Non-administrator accounts are not taken to the upgrade page, so the upgrade must be done by an administrator.

The 1.3.0 upgrade program does the following:

| Change | Notes |
|---|---|
| Routes | Rewrites the two routes `feed` and `comment_page`, handing them to `Widget\Feed` and `Widget\CommentPage` respectively |
| Option renamed | `commentsRequireURL` becomes `commentsRequireUrl` |
| Drafts | Drafts of published content (`post_draft` / `page_draft` with a parent) become the new type `revision` |
| Attachment data | Attachment information is converted from PHP serialized format to JSON, in batches of 100 |
| Plugin and theme settings | `plugins`, `actionTable`, `panelTable` and all `plugin:*` and `theme:*` options are converted from PHP serialized format to JSON |

For how each change affects your site, see [What's new and changed in 1.3](./whats-new-1-3.md).

## Known issue: upgrade hangs on sites with more than 100 attachments {#known-issue-attachments}

**Affected**: the 1.3.0 release. On sites with many attachments (100 or more), after you click **Complete upgrade** the page keeps loading until PHP's execution time limit is reached, and the upgrade never finishes.

**Cause**: the upgrade program converts attachment data in batches of 100, but only advances its read position when it meets an attachment in the old format that needs converting. If 100 attachments in a row need no conversion (for example because they are already JSON), the read position stops moving, the same batch is read over and over, and the loop never ends.

**Status**: fixed on the `master` branch (commit [`4c97a37`](https://github.com/typecho/typecho/commit/4c97a373664771f4a8a9e8094f9be0fef77f4c70), "Fix infinite loop in attachment upgrade when batch has no serialized data"), but as of 1.3.0 there is no release that includes the fix.

**Workaround**: before running step 2, replace the single file `var/Utils/Upgrade.php` in the new program with the fixed version. That commit only changes this loop and touches no other file:

```bash
cd /srv/www/typecho
curl -fL -o var/Utils/Upgrade.php \
  https://raw.githubusercontent.com/typecho/typecho/4c97a373664771f4a8a9e8094f9be0fef77f4c70/var/Utils/Upgrade.php
```

If you have already hit the hang: replace this file, log in to the admin panel again, and click **Complete upgrade** once more. The upgrade program does not update the version number in the database until it finishes, so the changes it has already applied can safely be applied again.

## After upgrading

- On the **Dashboard**, check that the version number in the footer is the new version.
- Check each active plugin and the current theme. 1.3 stores plugin and theme settings as JSON and adjusts some internal classes, so older plugins may need updating.
- If you get a blank page or a 500 error, first disable plugins and switch back to a bundled theme to narrow it down; see [Debugging and troubleshooting](../operations/debugging.md#isolate) for the steps.

## Sites deployed with containers

The official image `joyqi/typecho` writes `config.inc.php` to the root of `/app`, **not inside `/app/usr`**; and when the image starts, it only copies in program files that do not exist in `/app`. This leads to two pitfalls:

- Mount only `/app/usr` and recreate the container as usual: `config.inc.php` disappears with the old container, and the home page redirects to `/install.php`;
- Mount the whole of `/app`: after switching to an image with a new tag, the program files in the volume are not updated, and the site stays on the pre-upgrade version.

A working approach (using an upgrade from `1.2.1-php8.0-apache` to `1.3.0-php8.2-apache` as the example):

1. [Back up](../operations/backup.md) the database and the directory mounted at `/app/usr`;
2. Recreate the container with the new tag, **mounting only `/app/usr`**, and pass `TYPECHO_INSTALL=1`, all the `TYPECHO_SITE_URL` and `TYPECHO_DB_*` variables used at installation, plus `TYPECHO_DB_NEXT=keep`:

   ```bash
   docker run -d --name typecho -p 8080:80 -v /srv/typecho-usr:/app/usr \
     -e TYPECHO_INSTALL=1 -e TYPECHO_DB_NEXT=keep \
     -e TYPECHO_SITE_URL=https://blog.example.com \
     -e TYPECHO_DB_ADAPTER=Pdo_Mysql -e TYPECHO_DB_HOST=db \
     -e TYPECHO_DB_USER=typecho -e TYPECHO_DB_PASSWORD='数据库密码' -e TYPECHO_DB_DATABASE=typecho \
     joyqi/typecho:1.3.0-php8.2-apache
   ```

   `keep` makes the installer regenerate `config.inc.php` from the environment variables; when the data is complete, it neither creates tables nor changes the administrator account. Without `keep` you get "The installer has detected that the existing tables already exist.", and the site likewise jumps to the installation page.
3. Log in to the admin panel as an administrator; you are taken to the upgrade page, where you carry out [step 2](#step-2-run-the-upgrade). On sites with more than 100 attachments, first use `docker cp` to put the fixed `Upgrade.php` into `/app/var/Utils/` in the container, as described under [Known issue](#known-issue-attachments).

The image has no `latest` tag, so always specify the version; the `nightly-*` tags come from the `master` branch and must not be used in production. For sites that already mount the whole of `/app`, see [Container and cloud host deployment](../operations/containers.md#mount-whole-app).
