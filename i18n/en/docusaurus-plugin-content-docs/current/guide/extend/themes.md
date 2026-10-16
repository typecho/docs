---
title: Installing and Configuring Themes
sidebar_position: 1
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Installing and Configuring Themes

A theme (appearance) determines how the front end of your site looks. Themes are managed in the admin panel under "Dashboard → Appearance" (控制台 → 外观, `admin/themes.php`), which only administrators can use.

## The two bundled themes

| Directory | Name shown in the admin panel | Description |
|---|---|---|
| `usr/themes/default` | Typecho Replica Theme | The classic default theme, enabled after installation |
| `usr/themes/classic-22` | Classic 22 | Official theme added in 1.3, based on Pico CSS 2, with light / dark appearance support |

Settings of the two themes:

| Theme | UI label | Field | Description |
|---|---|---|---|
| default | Site logo URL (站点 LOGO 地址) | `logoUrl` | An image URL, shown before the site title |
| default | Sidebar display (侧边栏显示) | `sidebarBlock[]` | Checkboxes: show recent posts, show recent replies, show categories, show archives, show other miscellany (all selected by default) |
| classic-22 | Site logo (网站 Logo) | `logoUrl` | An image URL; displays the logo |
| classic-22 | Appearance style (外观风格) | `colorSchema` | Auto, light, dark, custom; "If custom is selected, the theme uses the styles from theme.css" (如果选择了自定义，主题将使用 theme.css 的样式) |

## Installing a theme

The admin panel has **no** feature for uploading themes; you need to put the files on the server yourself:

1. Download the theme and extract it to get a directory;
2. Upload the whole directory into `usr/themes/`, for example `usr/themes/mytheme/`;
3. Go to "Dashboard → Appearance"; the new theme appears in the "Available themes" (可以使用的外观) list.

For a directory to be recognized as a theme, it must contain at least an `index.php` whose opening comment describes the theme. The name, author, version and description shown in the list all come from this comment:

```php
<?php
/**
 * 主题的简短描述
 *
 * @package 主题名称
 * @author 作者
 * @version 1.0
 * @link https://example.com
 */
```

An image in the directory named `screenshot.png` (jpg, gif, webp and so on also work) is shown as the screenshot. Once the directory is in place, no installation step is needed; refresh the Appearance page to see it.

When you download a theme from GitHub, the extracted directory name often has a `-main` or `-master` suffix. Themes have no hard requirement on the directory name, but theme settings and custom templates are stored by directory name, so rename it to the name the theme author specifies before uploading.

## Switching themes

Click "Activate" (启用) next to the theme you want to use in the list; the message "Theme has been changed" (外观已经改变) appears. The theme currently in use has no "Activate" link.

:::caution Switching themes deletes the previous theme's settings
When you activate a new theme, **the settings of the previous theme are deleted**, and the new theme's settings revert to their defaults. For example, if you fill in "Site logo URL" in `default`, switch to another theme and then switch back, the logo URL has been cleared. Write down any settings you want to keep before switching.
:::

In addition, when you switch themes, if "Site front page" (站点首页) is set to "Call a template file directly" (直接调用模板文件), it is automatically reset to "Show the latest posts" (显示最新发布的文章); see [Reading settings](../settings/reading.md#front-page).

### The current theme's directory is missing

If the current theme's directory is deleted or renamed, the front end **automatically falls back to the `default` theme** (so do not delete the `default` theme), and the top of the Appearance page shows:

> 检测到您之前使用的 "某某" 外观文件不存在，您可以重新上传此外观或者启用其他外观。

(The theme files of "X" that you used before were not found; you can upload this theme again or activate another theme.)

Upload the theme again and refresh the page, and the message disappears; if you activate another theme instead, the previous theme's settings data is deleted.

## Theme settings page {#config}

When a theme defines `themeConfig()` in `functions.php`, a "Theme settings" (设置外观) tab appears at the top of the Appearance page (`admin/options-theme.php`). Fill it in and click "Save settings" (保存设置); the message "Theme settings have been saved" (外观设置已经保存) appears. The settings are stored in the `options` table of the database, in a row named `theme:<theme directory name>`.

The meaning of each setting is up to the theme; see the theme's own documentation.

## Editing theme files online {#editor}

"Edit current theme" (编辑当前外观) at the top of the Appearance page (`admin/theme-editor.php`) lets you modify theme files directly in the browser:

- The left side lists the `.php`, `.js` and `.css` files in the theme directory (subdirectories are not included);
- Click a file name to open it, edit it and click "Save file" (保存文件); the message "Changes to file X have been saved" (文件 某某 的更改已经保存) appears. When the file itself is not writable, "This file cannot be written" (此文件无法写入) is shown in place of the button;
- The "Edit" (编辑) link of other themes in the list lets you edit themes that are not active.

The editor has no undo, no version history and no syntax checking. A single wrong character in a PHP file such as `functions.php` can make both the front end and the admin panel fail to open, and then the only way to revert it is over FTP / SSH. Back up the file before editing.

### Disabling the online editor: `__TYPECHO_THEME_WRITEABLE__`

Add the following to `config.inc.php`:

```php
define('__TYPECHO_THEME_WRITEABLE__', false);
```

After this, only the "Available themes" and "Theme settings" tabs remain at the top of the Appearance page, the "Edit" links in the list disappear, and visiting `theme-editor.php` directly returns 404. The theme settings page is not affected. Disabling it is recommended in production; for the reasons see [Security hardening](../operations/security.md#theme-editor).

## When a theme causes problems

When the front end is blank or returns 500, first switch back to the `default` theme to confirm whether the theme is the cause; if the admin panel cannot be opened either, you can change the current theme through the database, as described in [Debugging and troubleshooting](../operations/debugging.md#isolate).
