---
title: Permalinks
sidebar_position: 4
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# Permalinks

This page covers "Settings → Permalink" (`admin/options-permalink.php`) in the admin panel, which only administrators can access. It determines whether URL rewriting is used, and the URL formats of posts, pages and categories. For the rewrite rules needed on the web server side, see [Web server configuration](../operations/web-server.md).

## Whether to use URL rewriting {#rewrite}

| UI option | `rewrite` value | Generated URL |
|---|---|---|
| Disabled (default) | `0` | `https://example.com/index.php/archives/1/` |
| Enabled | `1` | `https://example.com/archives/1/` |

Before you enable it, the web server must already have rewrite rules that pass non-existent paths to `index.php`.

### Automatic check when enabling: `checkRewrite()` {#check}

When you select "Enabled" and save, Typecho first runs a check and saves only if it passes:

1. If PHP runs as an Apache module (`mod_php`) and the root directory has no `.htaccess` and is writable, it first writes an `.htaccess` automatically. **With other setups such as PHP-FPM it is not written automatically**; you need to create it yourself, with the content shown in [Web server configuration](../operations/web-server.md#htaccess).
2. The server sends a request **to itself** at `<site URL>/action/ajax` (without `index.php`); a response of `OK` counts as a pass.
3. If step 1 wrote the file and the check did not pass, Typecho rewrites the file in another compatible form and checks again; if the check still fails, Typecho deletes the file it wrote.

There are two outcomes:

- **The check fails** (for example, there are no rewrite rules on the server at all): the page shows "URL rewriting check failed, please check your server settings", an extra checkbox "If you still want to enable this feature, check here" appears below, and `rewrite` stays 0;
- **The check passes** (the rewrite rules are in effect): the setting is saved, and front-end links change to the form without `index.php`.

Under Apache `mod_php`, when the root directory is not writable, the failure message also adds "We detected that you are using an apache server, but the program cannot create an .htaccess file in the root directory… Please adjust your directory permissions, or create an .htaccess file manually.".

The check also commonly fails when the server cannot reach itself through the site URL, for example because of an internal network, a firewall or Docker port mapping, or because the site URL's domain does not resolve on the server. In these cases the check fails even if the rules are correct.

### The check fails but you are sure the rules are fine

There are two ways:

- Check "If you still want to enable this feature, check here" (`enableRewriteAnyway`) and save again to skip the check;
- Define `__TYPECHO_REWRITE__` in `config.inc.php`; see [Configuration file: permalinks](../operations/config-file.md#rewrite). Once it is defined, neither this option nor the check appears in the admin panel.

:::caution Make sure the rules really work before skipping the check
If you check "enable anyway" when there are no rewrite rules at all, the save succeeds, but every post link on the front end (such as `/archives/1/`) returns 404. The admin panel is not affected (`/admin/` is a real directory); come back here and change it back to "Disabled" to recover.
:::

### Turning off URL rewriting deletes `.htaccess` {#htaccess-delete}

When you **save this page while URL rewriting is set to "Disabled"** (no matter which field you changed), Typecho deletes the `.htaccess` in the site root directory, including one you created manually: as long as `rewrite` is 0, saving the permalink settings once deletes `.htaccess`. However, the save that switches from "Enabled" to "Disabled" does not delete it; only later saves do.

If your `.htaccess` also contains other rules (hotlink protection, HTTPS redirects and so on), back it up first, or do not save this page repeatedly before URL rewriting is enabled.

## Custom post path {#post-pattern}

"Custom post path" (`postPattern`) has four presets and one custom definition:

| UI label | Rule | Example |
|---|---|---|
| Default style | `/archives/{cid}/` | `/archives/1/` |
| WordPress style | `/archives/{slug}.html` | `/archives/hello-world.html` |
| Archive by date | `/{year}/{month}/{day}/{slug}.html` | `/2026/10/16/hello-world.html` |
| Archive by category | `/{category}/{slug}.html` | `/default/hello-world.html` |
| Custom | Entered in the text box (`customPattern`) | For example `/{year}/{cid}.html` |

Available parameters (the UI description reads: "`{cid}` post ID, `{slug}` post slug, `{category}` category, `{directory}` multi-level category, `{year}` year, `{month}` month, `{day}` day"):

| Parameter | Meaning |
|---|---|
| `{cid}` | Post ID (a number) |
| `{slug}` | The post's URL slug |
| `{category}` | The slug of the post's first category |
| `{directory}` | A multi-level path including all parent categories, for example `tech/php` |
| `{year}` / `{month}` / `{day}` | Publish date: 4-digit year, 2-digit month, 2-digit day |

For example, after you save the custom definition `/{year}/{cid}.html`, post URLs look like `/2026/1.html`.

Notes:

- The rule must contain at least one of `{cid}` or `{slug}` to tell posts apart; otherwise several posts get the same URL. The custom definition input does **not** check this.
- When you save a custom definition, Typecho checks whether it conflicts with existing rules such as those for categories and tags. On a conflict it **still saves**, and only shows the message "The custom link conflicts with existing rules! It may affect parsing efficiency; it is recommended that you assign a different rule.". For example, after you save `/tag/{slug}/`, every URL of the form `/tag/<anything>/` is parsed as a post, and tag pages can no longer be reached. When you see this message, choose a different rule.
- **Old URLs are not redirected after you change the rule**. For example, after changing from the default style to `/{year}/{cid}.html`, the old `/archives/1/` returns 404. For a site already indexed by search engines or linked from other sites, think it over before changing, or configure redirects for the old URLs on the web server yourself. The UI also warns: "Once you have chosen a link style, do not change it lightly."

## Page path and category path {#page-category}

| UI label | Field | Default | Available parameters |
|---|---|---|---|
| Page path | `pagePattern` | `/{slug}.html` | `{cid}` page ID, `{slug}` page slug, `{directory}` multi-level pages |
| Category path | `categoryPattern` | `/category/{slug}/` | `{mid}` category ID, `{slug}` category slug, `{directory}` multi-level categories |

- Both must contain at least one parameter. For example, when you change the page path to `/about.html`, the message "The page path does not contain `{cid}` or `{slug}`" appears and none of the settings on the page are saved (`{directory}` is also a valid parameter, although the message does not mention it).
- `{directory}` is for page trees and category trees and generates a multi-level path that includes the parent slugs; when the page path is changed to `/{directory}.html`, the URLs of top-level pages stay the same (such as `/start-page.html`).
- Category pagination URLs are generated automatically as "category path + `/<page number>/`".
- The rules for other URLs such as tags, authors, search and date archives cannot be changed in the admin panel.

These three items (the post, page and category paths) are stored in the `routingTable` option, in the same routing table as the post list path in "Reading settings".
