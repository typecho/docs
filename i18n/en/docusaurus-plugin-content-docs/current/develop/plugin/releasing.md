---
title: Releasing a Plugin
sidebar_position: 10
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Releasing a Plugin

Typecho currently has no officially maintained plugin directory; authors publish their plugins themselves. The common approach is to host them on GitHub: users download an archive from the repository or a Release, extract it and upload it to `usr/plugins/`.

## Pre-release checklist

- **Directory name, namespace and `@package` match**: the directory `Guestbook` corresponds to the namespace `TypechoPlugin\Guestbook`; see [Plugin Structure and Lifecycle](./structure.md).
- **The header comment is complete**: description, `@package`, `@author`, `@version`, `@link`. Update `@version` before each release.
- **Add `@since`**: write `@since 1.3.0` when you use classes and methods that only exist in 1.3. Unlike themes, Typecho **does** refuse to activate a plugin based on its `@since`: when the current version is lower, clicking "Activate" only produces the message "cannot work properly under this version of Typecho"; see [@since check](./structure.md#since-check).
- **`implements PluginInterface` without a leading backslash**: `use Typecho\Plugin\PluginInterface;` first, otherwise the admin treats the plugin as "plug and play" and does not call `activate()`; see [Header comment](./structure.md#header).
- **Every PHP file starts with** `if (!defined('__TYPECHO_ROOT_DIR__')) exit;` (admin panel files check `__TYPECHO_ADMIN__`).
- **`deactivate()` undoes the changes made by `activate()`**: routes, actions, menus and panels are all removed; see [Routes, Actions and Admin Panels](./routes-actions.md).
- **Run through the whole lifecycle**: on a site with `__TYPECHO_DEBUG__` enabled, activate, save the settings, use every feature, deactivate and activate again, and make sure there are no warnings; try both PHP 7.4 and a recent PHP 8. If you use the database, try it on MySQL, PostgreSQL and SQLite; see [Database Operations](./database.md#create-table).
- **Callbacks that run on every admin page must not fail**: if hooks such as `navBar` in `admin/menu.php`, `begin` in `admin/common.php` or `end` in `admin/footer.php` throw an exception, every admin page breaks, and users cannot even open the page to deactivate the plugin. Catch exceptions in these callbacks.

## Language packs

Translations of a plugin's interface text go under `lang/` in the plugin directory and are released with the plugin. For how to generate the translation template, compile `.mo` files and load them in a plugin, see [Theme and Plugin Internationalization](../i18n.md).

## Packaging

Extracting the archive should yield **a single directory named after the plugin**, with `Plugin.php` and the other files directly inside it:

```text
Guestbook-1.0.0.zip
└── Guestbook/
    ├── Plugin.php
    ├── Action.php
    ├── panel.php
    └── lang/
```

The directory name is the plugin name: Typecho uses it to find the plugin class and to store the plugin settings, and renaming it makes it a different plugin. Remind users in the README that when they download a source archive from GitHub, the extracted directory name gets a suffix such as `-main` or `-master`; they need to rename it back to `Guestbook` before uploading, or the plugin cannot be activated. Packaging with Git inside the repository gives you the correct structure directly and leaves out files such as `.git`:

```bash
git archive --format=zip --prefix=Guestbook/ -o Guestbook-1.0.0.zip HEAD
```

Do not put development files (test scripts, front-end sources, `node_modules`, etc.) in the archive. If your plugin uses third-party PHP libraries, put the library files into the plugin directory and `require` them yourself, since Typecho does not use Composer.

For a single-file plugin (`usr/plugins/Name.php`), just release that one file.

## Releasing on GitHub

1. In the repository's README, state: the plugin name, what it does, the compatible Typecho and PHP versions, required PHP extensions, how to install it (upload to `usr/plugins/` and activate it under "Console → Plugins"), what the settings do, and the license.
2. Add the topic `typecho-plugin` to the repository. It is the most widely used topic for Typecho plugins on GitHub, and users search by it in [Finding Plugins and Themes](../../guide/extend/finding-plugins.md).
3. Create a Release for each version, attach the archive packaged as described above, and list the changes in the notes.
4. Post an introduction of the plugin on the [Typecho forum](https://forum.typecho.org/) with the repository URL.

## Upgrading versions

When users upgrade a plugin, they usually overwrite the files in the plugin directory and **do not** reactivate the plugin. So in a new version:

- Hooks newly registered in `activate()`, and newly added routes and panels, do not take effect until the user deactivates and reactivates the plugin. When you have changes like these, state "please deactivate and reactivate the plugin after upgrading" in the upgrade notes. Deactivating clears the plugin settings, so remind users of that too.
- New settings have no value until the user saves the settings once, so handle defaults when reading them. Reading new personal settings throws an exception until the plugin is reactivated; see [Plugin Settings](./settings.md).
- When you remove a setting, keep a `Hidden` element with the same name, or the plugin settings page will break; see [Plugin Settings](./settings.md#remove-setting-on-upgrade).
- When the table structure changes, check for and add the new columns in code; do not rely only on the table creation statement in `activate()`.
