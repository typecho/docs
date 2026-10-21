---
title: Installing and Configuring Plugins
sidebar_position: 2
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# Installing and Configuring Plugins

Plugins add features to Typecho, such as code highlighting, sitemaps and comment notifications. Plugins are managed in the admin panel under "Console → Plugins" (`admin/plugins.php`), which only administrators can use. The installation package includes an example plugin, `HelloWorld`.

## Installing a plugin

The admin panel has **no** feature for uploading plugins; you need to put the files on the server yourself:

1. Download the plugin and extract it;
2. Upload the plugin directory into `usr/plugins/`, for example `usr/plugins/HelloWorld/`; the directory must contain `Plugin.php`;
3. Go to "Console → Plugins"; it appears in the "Disabled plugins" list.

The name, description, version and author shown in the list all come from the comment at the top of `Plugin.php` (`@package`, `@version`, `@author`, `@link` and so on). A few plugins are a single file, placed directly as `usr/plugins/<plugin name>.php`.

:::caution The directory name must match the plugin name
A plugin's class name includes its directory name (for example `TypechoPlugin\HelloWorld\Plugin`), so **the plugin cannot be enabled if the directory name is wrong**. Archives downloaded from GitHub often extract to directories with a `-main` or `-master` suffix; you must rename them to the name the plugin author requires. For example, when the `HelloWorld` directory is named `HelloWorld-master`, the list still shows "HelloWorld", but clicking "Enable" gives a 500 error page (the plugin is not enabled, and other pages are not affected).
:::

## Enabling, configuring and disabling

| Action | Where | Result |
|---|---|---|
| Enable | "Enable" in the "Disabled plugins" list | The message "Plugin has been enabled" appears and the plugin moves to the "Enabled plugins" list; the plugin's settings are saved once with their default values |
| Configure | "Settings" in the "Enabled plugins" list (shown only when the plugin provides settings) | Opens "Configure plugin &lt;plugin name&gt;" (`options-plugin.php?config=<plugin name>`); after saving, the message "Plugin settings have been saved" appears |
| Disable | "Disable" in the "Enabled plugins" list, then confirm "Are you sure you want to disable plugin X?" | The message "Plugin has been disabled" appears |

- For plugins with neither enable/disable logic nor settings, the action column shows "Plug and play".
- A plugin's personal settings (each user's own settings) appear at the bottom of each user's profile page; see [Profile](../content/profile.md#plugins).
- When a plugin declares a minimum version with `@since` and the current Typecho version is lower, enabling fails with the message "X cannot work properly under this version of typecho"; an enabled plugin of this kind shows a delete icon next to its name as a warning.

Taking the bundled `HelloWorld` as an example: after it is enabled, the "Hello World" it outputs appears in the top navigation bar of the admin panel; after you change "Say something" on its settings page and save, the new value is stored in the `options` table in a row named `plugin:HelloWorld`.

:::caution Disabling clears the plugin's settings
When you disable a plugin, its site-wide settings and all users' personal settings are deleted from the database (for example the `plugin:HelloWorld` row in the `options` table), and when you enable it again the settings revert to their defaults. If you only want to stop it temporarily without losing the settings, write the settings down first.
:::

## When a plugin causes a 500 error {#500}

A faulty plugin is one of the most common reasons for a sudden 500 in the admin panel or on the front end. To troubleshoot:

1. **Think back to which plugin you most recently enabled or updated**, and disable it in "Console → Plugins";
2. If the admin panel can no longer be opened, you cannot disable it from the UI. In that case **do not simply delete the plugin directory** (see the warning below); instead disable it through the database as described in [Debugging and troubleshooting: disabling plugins and switching back to the default theme](../operations/debugging.md#isolate). Disabling it this way does not delete its settings, but when you enable the plugin again later, its default values overwrite saved settings with the same names. So before you enable it again, back up the `plugin:<plugin name>` and `_plugin:<plugin name>` rows in the `options` table, and restore the settings from the backup after enabling it;
3. Turn on debug mode to see the actual error (see [Debugging and troubleshooting](../operations/debugging.md#debug)), then contact the plugin author or ask for help on the forum.

:::danger Deleting the directory of an enabled plugin can make the whole admin panel return 500
When something goes wrong, many people's first reaction is to delete the plugin directory. For an **enabled** plugin, however, what happens next depends on which hooks the plugin has attached its callbacks to:

- When a page reaches a hook registered by the plugin, the plugin's code cannot be found and the page returns 500. If the plugin is attached to a hook that runs on every admin page (for example, the bundled `HelloWorld` is attached to the admin navigation bar), **every admin page returns 500**, including the Console, plugin management and post writing, so you cannot even open the plugins page to disable it;
- A plugin attached only to front-end or other hooks breaks only the pages that reach those hooks after its directory is deleted. If the plugin management page opens, the plugin's row in the list shows "This plugin file has been damaged or removed unsafely; it is strongly recommended that you disable it"; you can click "Disable" directly.

If you have already deleted the directory and the admin panel returns 500: put the plugin directory back exactly as it was, disable the plugin in the admin panel, and then delete it; if the plugin management page cannot be used and the directory cannot be recovered, reset the plugin list in the database as described in [Debugging and troubleshooting](../operations/debugging.md#isolate).
:::

The correct order for uninstalling is: **first disable the plugin in the admin panel, then delete its directory under `usr/plugins/`**.

## Choosing plugins

For where to find plugins and how to tell whether a plugin works with 1.3, see [Finding plugins and themes](./finding-plugins.md).
