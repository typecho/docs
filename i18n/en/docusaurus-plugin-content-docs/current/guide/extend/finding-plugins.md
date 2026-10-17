---
title: Finding Plugins and Themes
sidebar_position: 4
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Finding Plugins and Themes

Typecho currently has **no maintained official or community directory of plugins / themes**. Plugins and themes are published by their authors individually, mostly on GitHub. This page lists search entry points by need and explains how to tell whether a plugin or theme can be used with 1.3. This page does not recommend or describe specific third-party plugins.

If you cannot find a suitable one, or are not sure which one to use, post a question on the [Typecho forum](https://forum.typecho.org/); see [Getting help](../help/getting-help.md).

## Entry points on GitHub

- Plugins: GitHub topic [`typecho-plugin`](https://github.com/topics/typecho-plugin)
- Themes: GitHub topic [`typecho-theme`](https://github.com/topics/typecho-theme)
- Keyword search: [search "typecho plugin", sorted by most recently updated](https://github.com/search?q=typecho+plugin&type=repositories&s=updated&o=desc), [search "typecho theme", sorted by most recently updated](https://github.com/search?q=typecho+theme&type=repositories&s=updated&o=desc)

Not every author adds topics to their repository, so a topic page shows only part of what exists; keyword search covers more, but the results are also noisier.

## Searching by need

All links in the table below are GitHub repository searches sorted by most recent update. Chinese and English keywords often give different results, so try both.

| Need | Search | Without a plugin |
|---|---|---|
| Code highlighting | [typecho highlight](https://github.com/search?q=typecho+highlight&type=repositories&s=updated&o=desc), [same search in Chinese](https://github.com/search?q=typecho+%E4%BB%A3%E7%A0%81%E9%AB%98%E4%BA%AE&type=repositories&s=updated&o=desc) | Many themes include it; you can also load a front-end library such as highlight.js or Prism in the theme templates |
| Math formulas | [typecho katex](https://github.com/search?q=typecho+katex&type=repositories&s=updated&o=desc), [typecho mathjax](https://github.com/search?q=typecho+mathjax&type=repositories&s=updated&o=desc) | Load the KaTeX or MathJax script in the theme templates |
| Sitemap | [typecho sitemap](https://github.com/search?q=typecho+sitemap&type=repositories&s=updated&o=desc) | — |
| Visitor analytics | [typecho analytics](https://github.com/search?q=typecho+analytics&type=repositories&s=updated&o=desc), [same search in Chinese](https://github.com/search?q=typecho+%E7%BB%9F%E8%AE%A1&type=repositories&s=updated&o=desc) | Paste the code provided by the analytics service into the theme's `footer.php` or `header.php` (you can use the [online editor](./themes.md#editor)) |
| Comment email notifications | [typecho comment mail](https://github.com/search?q=typecho+comment+mail&type=repositories&s=updated&o=desc), [same search in Chinese](https://github.com/search?q=typecho+%E9%82%AE%E4%BB%B6+%E9%80%9A%E7%9F%A5&type=repositories&s=updated&o=desc) | Typecho itself sends no email at all; a plugin is required |
| Storing attachments in object storage | [typecho oss](https://github.com/search?q=typecho+oss&type=repositories&s=updated&o=desc), [typecho cos](https://github.com/search?q=typecho+cos&type=repositories&s=updated&o=desc), [typecho s3](https://github.com/search?q=typecho+s3&type=repositories&s=updated&o=desc) | If you only want attachments served through a CDN, or stored outside the site directory, the constants in `config.inc.php` are enough; see [Attachment storage location](../operations/uploads-storage.md) |

## Telling whether it works with 1.3

Typecho 1.3 requires PHP 7.4 or later, and its internal code has also switched to namespaces. Plugins and themes written for versions before 1.3 (1.1, 1.2 or even earlier) do not necessarily work properly on 1.3. Check these points before installing:

1. **Last update time**: the most reliable sign is a repository with commits after the 1.3.0 release (January 2026), or a README / release notes that mention 1.3. Be prepared for projects that have not been updated for many years not to work.
2. **Declared minimum version**: if the comment at the top of a plugin's `Plugin.php` or a theme's `index.php` contains a version such as `@since 1.3.0`, the author declares that it requires that version or later. Typecho checks `@since` when enabling a plugin and refuses to enable it when the current version is lower. A plugin without `@since` is not checked, which does not mean it is compatible.
3. **Coding style**: the 1.3 style uses namespaced classes, for example a plugin class with `namespace TypechoPlugin\PluginName;` and `class Plugin implements PluginInterface`, and class names such as `\Widget\Options` and `\Typecho\Widget\Helper\Form`. Code that only uses old underscore class names such as `Typecho_Widget` and `Widget_Abstract_Contents` was written for versions before 1.3, and whether it runs can only be known by trying it.
4. **Issues**: search for "1.3" or "PHP 8" to see whether anyone has reported compatibility problems.

When trying a new plugin or theme:

- Back up first; see [Backup and restore](../operations/backup.md);
- Install plugins in the order described in [Installing and configuring plugins](./plugins.md); when something goes wrong, **disable first, then delete the directory**;
- Switching themes clears the previous theme's settings; see [Installing and configuring themes](./themes.md);
- Plugins and themes of unknown origin have the same permissions as Typecho itself and can read the database and all files. Get them only from trusted authors, and watch for code that loads remote scripts or sends data out.
