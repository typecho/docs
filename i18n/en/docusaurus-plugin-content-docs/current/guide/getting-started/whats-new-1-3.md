---
title: What's new and changed in 1.3
sidebar_position: 5
translation: machine
source_commit: 0dccf9f7706701fc8b129b2d5f8efd9c36c511de
---

# What's new and changed in 1.3

Typecho 1.3.0 was released on 20 January 2026. This page is for site owners: it lists the changes you will notice directly after upgrading from 1.2 to 1.3, and the renames and removals to watch out for. For the upgrade steps, see [Upgrading](./upgrade.md).

:::note
This page only lists what is in the 1.3.0 release. Changes merged on the `master` branch after 1.3.0 (for example, selecting several attachments to upload at once) are not listed here.
:::

## New features

| Feature | Description |
|---|---|
| Revisions | When you edit and save a post or page that is already published, the changes are saved as a revision (the new content type `revision`); the published content stays unchanged until you publish again |
| Page tree | A page can have a parent page, forming a multi-level structure. **Manage → Pages** shows the number of child pages of each page; click it to open the list of child pages (`manage-pages.php?parent=`). You can also add a child page to a page directly |
| New uploader | Attachment upload uses a new implementation and no longer depends on plupload / moxie (or the Flash file `Moxie.swf` in it). You can drag files onto the upload area; the size limit for a single file comes from PHP's `upload_max_filesize` |
| New official theme classic-22 | A new theme based on Pico CSS 2, shipped in the package alongside `default`, located at `usr/themes/classic-22` |
| Save with Ctrl/Cmd+S | In the Markdown editor, press <kbd>Ctrl</kbd>+<kbd>S</kbd> (<kbd>Cmd</kbd>+<kbd>S</kbd> on macOS) to save the draft without clicking a button |
| Internationalized domain names (IDN) | **Site URL** and **Homepage URL** now use URL input boxes; internationalized domain names (e.g. Chinese) stored in their `xn--` encoded form are shown in their original form in the admin panel (requires the PHP `intl` extension) |
| High-resolution avatars | Avatars can additionally output Gravatar URLs at 2× and 3× size (`srcset`), so they look sharper on high-resolution screens. This is enabled by default in **Manage → Comments** in the admin panel; on the front end the comment list does not enable it by default: the theme has to pass `avatarHighRes => true` to `listComments()`, and neither of the two bundled themes turns it on |
| PostgreSQL SSL | PostgreSQL connections can use SSL (`sslmode=require`); just select it during installation |
| MySQL socket | During installation the **Database host** can be a Unix socket path, see [Installation](./installation.mdx#使用-mysql-socket) |

## Higher requirements

1.3 requires **PHP 7.4.0 or later** (1.2.x requires 7.2.0). Check your PHP version before upgrading; otherwise the whole site reports syntax errors once the files are replaced. See [Requirements](./requirements.md).

## Renames and removals

The upgrade program handles these changes in the database automatically. But if your theme, plugins or your own scripts use the old names directly, you have to change them by hand.

| 1.2 | 1.3 | Impact |
|---|---|---|
| Option `commentsRequireURL` | `commentsRequireUrl` | Themes or plugins that read `$this->options->commentsRequireURL` get an empty value; change them to the new name |
| Drafts of published content: type `post_draft` / `page_draft` (with a parent) | Type `revision` | Code that queries drafts in the `contents` table directly must also take `revision` into account. `post_draft` / `page_draft` without a parent (drafts that have never been published) are unchanged |
| Route `feed` handled by `Widget\Archive` | Handled by `Widget\Feed` | Feed URLs are unchanged; plugins that customize the route table or take over feed output need checking |
| Route `comment_page` handled by `Widget\Archive` | Handled by `Widget\CommentPage` | Comment pagination URLs are unchanged; same as above |
| The default theme has `archive.php` | `usr/themes/default/archive.php` has been removed | Archive pages for categories, tags, search, etc. are now rendered by `index.php`. Sites that replace only the program files and keep their old `usr/themes/default` during the upgrade are not affected |
| Attachment information and plugin and theme settings stored with PHP `serialize` | Stored as JSON | Plugins that read the `options` table or the attachment `text` field directly must parse JSON |

## Things to watch when upgrading

- 1.3 can only be upgraded to from 1.1.0 or later; for 1.1 sites, upgrading to 1.2.1 first is recommended. See [Upgrading](./upgrade.md#能否直接升级).
- The 1.3.0 release can hang during the upgrade on sites with more than 100 attachments; for the workaround, see [Upgrading](./upgrade.md#已知问题附件超过-100-个的站点升级时卡住).
