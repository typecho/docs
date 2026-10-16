---
title: Installing and Configuring Plugins
sidebar_position: 2
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Installing and Configuring Plugins

Plugins add features to Typecho, such as code highlighting, sitemaps and comment notifications. Plugins are managed in the admin panel under "Dashboard → Plugins" (控制台 → 插件, `admin/plugins.php`), which only administrators can use. The installation package includes an example plugin, `HelloWorld`.

## Installing a plugin

The admin panel has **no** feature for uploading plugins; you need to put the files on the server yourself:

1. Download the plugin and extract it;
2. Upload the plugin directory into `usr/plugins/`, for example `usr/plugins/HelloWorld/`; the directory must contain `Plugin.php`;
3. Go to "Dashboard → Plugins"; it appears in the "Disabled plugins" (禁用的插件) list.

The name, description, version and author shown in the list all come from the comment at the top of `Plugin.php` (`@package`, `@version`, `@author`, `@link` and so on). A few plugins are a single file, placed directly as `usr/plugins/<plugin name>.php`.

:::caution The directory name must match the plugin name
A plugin's class name includes its directory name (for example `TypechoPlugin\HelloWorld\Plugin`), so **the plugin cannot be enabled if the directory name is wrong**. Archives downloaded from GitHub often extract to directories with a `-main` or `-master` suffix; you must rename them to the name the plugin author requires. For example, when the `HelloWorld` directory is named `HelloWorld-master`, the list still shows "HelloWorld", but clicking "Enable" (启用) gives a 500 error page (the plugin is not enabled, and other pages are not affected).
:::

## Enabling, configuring and disabling

| Action | Where | Result |
|---|---|---|
| Enable | "Enable" in the "Disabled plugins" list | The message "Plugin has been enabled" (插件已经被启用) appears and the plugin moves to the "Enabled plugins" (启用的插件) list; the plugin's settings are saved once with their default values |
| Configure | "Settings" (设置) in the "Enabled plugins" list (shown only when the plugin provides settings) | Opens "Configure plugin &lt;plugin name&gt;" (设置插件 插件名, `options-plugin.php?config=插件名`); after saving, the message "Plugin settings have been saved" (插件设置已经保存) appears |
| Disable | "Disable" (禁用) in the "Enabled plugins" list, then confirm "Are you sure you want to disable plugin X?" (你确认要禁用插件 某某 吗?) | The message "Plugin has been disabled" (插件已经被禁用) appears |

- For plugins with neither enable/disable logic nor settings, the action column shows "Plug and play" (即插即用).
- A plugin's personal settings (each user's own settings) appear at the bottom of each user's profile page; see [Profile](../content/profile.md#plugins).
- When a plugin declares a minimum version with `@since` and the current Typecho version is lower, enabling fails with the message "X cannot work properly under this version of typecho" (某某 无法在此版本的typecho下正常工作); an enabled plugin of this kind shows a delete icon next to its name as a warning.

Taking the bundled `HelloWorld` as an example: after it is enabled, the "Hello World" it outputs appears in the top navigation bar of the admin panel; after you change "Say something" (说点什么) on its settings page and save, the new value is stored in the `options` table in a row named `plugin:HelloWorld`.

:::caution Disabling clears the plugin's settings
When you disable a plugin, its site-wide settings and all users' personal settings are deleted from the database (for example the `plugin:HelloWorld` row in the `options` table), and when you enable it again the settings revert to their defaults. If you only want to stop it temporarily without losing the settings, write the settings down first.
:::

## When a plugin causes a 500 error {#500}

A faulty plugin is one of the most common reasons for a sudden 500 in the admin panel or on the front end. To troubleshoot:

1. **Think back to which plugin you most recently enabled or updated**, and disable it in "Dashboard → Plugins";
2. If the admin panel can no longer be opened, you cannot disable it from the UI. In that case **do not simply delete the plugin directory** (see the warning below); instead disable it through the database as described in [Debugging and troubleshooting: disabling plugins and switching back to the default theme](../operations/debugging.md#isolate);
3. Turn on debug mode to see the actual error, see [Debugging and troubleshooting](../operations/debugging.md#debug), then contact the plugin author or ask for help on the forum.

:::danger Deleting the directory of an enabled plugin can make the whole admin panel return 500
Many people's first reaction to a problem is to delete the plugin directory, but doing this to an **enabled** plugin has a result that depends on whether the plugin registers hooks:

- The plugin registers hooks (the vast majority do, including the bundled `HelloWorld`): after you delete the directory of the enabled plugin, **every admin page returns 500**, including the dashboard, plugin management and post writing, so you cannot even open the plugins page to disable it;
- The plugin registers no hooks: the admin panel works normally, and the plugin's row in the list shows "This plugin file has been damaged or removed unsafely; it is strongly recommended that you disable it" (此插件文件已经损坏或者被不安全移除, 强烈建议你禁用它); you can click "Disable" directly.

If you have already deleted the directory and the admin panel returns 500: put the plugin directory back exactly as it was, disable the plugin in the admin panel, and then delete it; or reset the plugin list in the database as described in [Debugging and troubleshooting](../operations/debugging.md#isolate).
:::

The correct order for uninstalling is: **first disable the plugin in the admin panel, then delete its directory under `usr/plugins/`**.

## Choosing plugins

For where to find plugins and how to tell whether a plugin works with 1.3, see [Finding plugins and themes](./finding-plugins.md).
