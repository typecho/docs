---
title: Releasing a Theme
sidebar_position: 13
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Releasing a Theme

Typecho currently has no officially maintained theme directory; authors publish their themes themselves. The common approach is to put the theme on GitHub: users download an archive from the repository or a Release and upload it to `usr/themes/`.

## Checks before release

- **The `index.php` header comment is complete**: `@package` (name), `@author`, `@version`, `@link`, and a short description on the first line; see [Theme structure and header information](./anatomy.md#header-comment). Update `@version` before every release.
- **Declare the compatible Typecho version**: write `@since 1.3.0` and state it in the README. Typecho does not refuse to activate a theme based on its `@since`; it is information for users. Users decide whether a theme works on 1.3 mainly from this and from when the repository was last updated.
- **Screenshot**: put `screenshot.png` (or jpg, webp, etc.) in the theme root directory; the theme list in the admin panel shows it. See the [screenshot rules](./anatomy.md#screenshot).
- **Every PHP file starts with** `if (!defined('__TYPECHO_ROOT_DIR__')) exit;`.
- **Check with debug mode on**: with `__TYPECHO_DEBUG__`, visit the home page, a post, a page, a category, a tag, search and 404 one by one and make sure there are no warnings; try both PHP 7.4 and a recent PHP 8.
- **Sensible defaults for theme options**: right after users activate the theme, before they have opened "Theme settings", the theme should display correctly.
- **Do not depend on anything the site owner must create manually**: when you use a dedicated template tied to a category slug or page slug (such as `category/news.php`), document it in the README.

## Packaging

Extracting the archive should produce **one directory named after the theme directory**, which directly contains `index.php` and the other files:

```text
starter-1.0.0.zip
└── starter/
    ├── index.php
    ├── screenshot.png
    └── ...
```

The directory name is the theme's identifier and theme options are saved under it, so it must stay fixed. Remind users in the README that when they download the source archive from GitHub, the extracted directory name carries a suffix such as `-main` or `-master`, and must be renamed back to `starter` before uploading. Packaging with Git in the repository gives you the correct structure directly, without `.git` and similar files:

```bash
git archive --format=zip --prefix=starter/ -o starter-1.0.0.zip HEAD
```

Do not put development files (SCSS sources, `node_modules`, test scripts and so on) into the archive.

## Publishing on GitHub

1. In the repository README, state: the theme name, screenshots, the compatible Typecho and PHP versions, installation (upload to `usr/themes/`, activate under "Console → Appearance"), a description of the theme options, and the license;
2. Add the topic `typecho-theme` to the repository. It is the most widely used topic for Typecho themes on GitHub, and users search by it as described in [Finding plugins and themes](../../guide/extend/finding-plugins.md);
3. Create a Release for each version, attach the archive packaged as described above, and list the changes in the notes;
4. Post an introduction of the theme on the [Typecho forum](https://forum.typecho.org/), with the repository URL and screenshots.

## Upgrading versions

When users upgrade a theme, they usually delete the old directory and upload the new one. As long as **the directory name stays the same**, the settings users saved under "Theme settings" are kept (the settings are in the database, not in the theme directory). In a new version:

- Newly added options have no value until the user saves the settings once, so templates need default handling;
- For renamed or removed options, the old values stay in the database; in particular, do not use fields with the same name as site settings, see [Theme options](./theme-options.md);
- When a predefined custom field is renamed, the values saved in old posts are not migrated automatically.
