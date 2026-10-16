---
title: Migrating from Other Blogs
sidebar_position: 8
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Migrating from Other Blogs

This page covers three kinds of migration: importing content from WordPress or other software, moving Typecho to a new server, and changing Typecho's domain. Whichever you do, make a complete backup first as described in [Backup and restore](./backup.md).

## Importing from WordPress {#wordpress}

Typecho's official WordPress import plugin, **WordpressToTypecho**, lives in the plugin repository [github.com/typecho/plugins](https://github.com/typecho/plugins/tree/master/WordpressToTypecho) and is **not shipped with the Typecho release package**. The plugin version is 1.0.3 Beta, and the last change in the repository was on 2021-11-01.

:::warning The plugin has not been updated for 1.3
This plugin has not been updated since 2021 and was written for versions before 1.3. It can be enabled on 1.3, and the "从 WordPress 导入数据" (Import data from WordPress) page opens (enabling it logs an `Undefined variable $error` warning in the PHP log, which does not affect use), but the import results have not been adjusted for 1.3. Run the import on a test site first and check the results before using it on your production site.
:::

### What the plugin does

According to the plugin source (`Action.php`), when you click import the plugin:

1. **First deletes all posts, pages, attachment records, comments, categories, tags and their relations in the current Typecho site**. Use it only on a **freshly installed** site;
2. Imports posts and pages from the WordPress `posts` table, reusing the WordPress IDs as content IDs:
   - Published items stay published; every other status (draft, pending, private, etc.) is written as status `draft`. However, 1.3 represents drafts with the content types `post_draft` / `page_draft`, while the plugin writes "type `post` + status `draft`", which differs from how 1.3 represents drafts, so check these posts one by one after the import;
   - The author ID is taken as is from WordPress's `post_author`. WordPress users are not imported; when the author ID does not exist in Typecho, the post's author displays incorrectly, and you need to change `authorId` in the `typecho_contents` table in the database;
3. Imports categories and tags from `term_taxonomy` / `terms`, and their relations from `term_relationships`;
4. Imports comments from the `comments` table: approved → approved, unapproved → pending, spam → spam.

The plugin does **not** import: attachment files, WordPress users, WordPress settings, custom fields (post meta), menus and widgets.

### Steps

1. Install a new Typecho 1.3 site. The WordPress MySQL database must be reachable from this server; the plugin only supports MySQL (when enabled it checks whether `Mysqli` or `Pdo_Mysql` is available).
2. Download the `WordpressToTypecho` directory from the plugin repository and put it in `usr/plugins/WordpressToTypecho/`.
3. Enable it under "Dashboard → Plugins" in the admin panel, then open the plugin settings and fill in the WordPress database host, port, user name, password, database name and table prefix (default `wp_`).
4. Open the new "从 WordPress 导入数据" (Import data from WordPress) item in the "Dashboard" menu and start the import. With a large amount of data it may time out, so raise PHP's `max_execution_time` first.
5. After the import:
   - Copy WordPress's `wp-content/uploads/` to **the same path** on the new server and make it reachable by the web server, so image URLs in old posts keep working; or replace the URLs in the content in bulk as described [below](#replace-urls);
   - Under "Settings → Permalinks", set the same post URL format as the original site (a custom format is allowed), so search engines and external links keep working;
   - Refresh the counts under "Manage → Categories" and "Manage → Tags"; see [below](#counts);
   - Once the import is complete, you can disable and delete the plugin.

## Importing from Emlog and other software {#others}

Neither the Typecho release package nor the official plugin repository contains an import tool for Emlog (the official repository also has a `MagikeToTypecho`, which is only for Magike). Community members have written various conversion scripts and plugins; search GitHub for keywords such as "emlog typecho", or ask on the [support forum](https://forum.typecho.org). Before using a third-party tool:

- Check when it was last updated and whether it states support for Typecho 1.3;
- Always run it on a test site first, and use it on your production site only after posts, categories and comments are confirmed correct;
- After conversion you also need to handle attachments, permalinks and category counts, the same way as above.

## Moving to a new server (same domain) {#new-server}

1. On the old server, back up three parts: the database export, `usr/` and `config.inc.php` (see [Backup and restore](./backup.md)).
2. On the new server, prepare the program files of the same Typecho version (`admin/`, `var/`, `install/`, `index.php`, `install.php`), using the version number shown in the old site's admin footer.
3. Put `usr/` and `config.inc.php` back and import the database. For MySQL / PostgreSQL, create the database first.
4. Adjust `config.inc.php` for the new environment:
   - If the database host, user name or password changed, update the values in `addServer()`;
   - **With SQLite**, `'file'` is an absolute path, so update it if the site directory moved;
   - Also check absolute-path constants such as `__TYPECHO_UPLOAD_ROOT_DIR__`, `__TYPECHO_BACKUP_DIR__` and `__TYPECHO_LANG_DIR__`.
5. Make sure `usr/uploads/` is writable by the PHP process, and set up the permalink rules as described in [Web server configuration](./web-server.md).
6. Edit your local `hosts` file to point the domain at the new server, check that the home page, posts, admin login and attachment uploads all work, and only then change DNS.

When the database and `config.inc.php` are migrated unchanged, you don't need to run the installer again.

## Changing the domain {#new-domain}

Most of Typecho's links are generated from **the domain currently being accessed**, so after a domain change the links of posts, categories and the admin panel switch to the new domain automatically. What you need to handle manually:

| Location | How to change it |
|---|---|
| Site URL (option `siteUrl`) | Log in to the admin panel with the new domain and change it under "Settings → General". If you cannot get into the admin panel, change the database directly; see below |
| Constants in `config.inc.php` | Any of `__TYPECHO_SITE_URL__`, `__TYPECHO_ROOT_URL__`, `__TYPECHO_URL_PREFIX__`, `__TYPECHO_UPLOAD_URL__`, `__TYPECHO_THEME_URL__`, `__TYPECHO_PLUGIN_URL__` that contain the old domain; the `domain` of `__TYPECHO_COOKIE_OPTIONS__`. See [config.inc.php and constants](./config-file.md) |
| Images and internal links in post content | The content stores the full URL at the time of insertion, so replace them in bulk; see below |
| Users' homepage URLs | At installation, the administrator's homepage URL is the site URL. Change it under "Manage → Users" or in the profile settings, or change the `url` field of the `typecho_users` table |
| Theme and plugin settings | If you entered full URLs in settings (such as a logo image), check them one by one |

Afterwards, all users need to log in again: the name of the login cookie depends on the site URL, so old cookies stop working once the domain changes.

Finally, set up a 301 redirect from the old domain to the new one on the web server, for example in nginx:

```nginx
server {
    listen 80;
    server_name old.example.com;
    return 301 https://new.example.com$request_uri;
}
```

### Changing the site URL directly in the database

The SQL below assumes the default table prefix `typecho_`; change it to the actual prefix in `config.inc.php`.

```sql
UPDATE typecho_options SET value = 'https://new.example.com'
WHERE name = 'siteUrl' AND user = 0;
```

### Replacing URLs in content in bulk {#replace-urls}

This applies to changing the domain, switching from HTTP to HTTPS, changing the attachment URL prefix and similar cases. **Back up the database first**, then run:

```sql
UPDATE typecho_contents
SET text = REPLACE(text, 'http://old.example.com/', 'https://new.example.com/');

UPDATE typecho_fields
SET str_value = REPLACE(str_value, 'http://old.example.com/', 'https://new.example.com/');
```

- `REPLACE()` is available in MySQL, PostgreSQL and SQLite.
- The first statement handles the content of posts, pages and revisions; the second handles string-type custom fields. Attachment records store relative paths and are not affected.
- Include the trailing `/` in the replaced strings to avoid accidentally changing similar domains such as `old.example.com.cn`.

## Wrong post counts for categories and tags {#counts}

After importing data, restoring a backup or editing the database by hand, the post counts shown next to categories and tags may be wrong. This number is stored in the `count` field of the `metas` table and is not recalculated automatically.

**How to refresh**: in the admin panel, go to "Manage → Categories" (or "Manage → Tags"), check the items to refresh (you can click the checkbox in the table header to select all), and click "刷新" (Refresh) in the "选中项" (Selected items) drop-down menu. With many categories this may take a while.

According to the source (`refreshCountByTypeAndStatus()` in `var/Widget/Metas/EditTrait.php`), the refreshed number is the count of **published posts**: pages, drafts, and private or pending posts are not counted. So a refreshed number lower than you expected may be correct.

Refreshing tags does **not** delete empty tags with a count of 0. The source (`clearTags()` in `var/Widget/Metas/Tag/Edit.php`) is meant to clean up tags that no longer relate to any content after a refresh, but in 1.3.0 the query condition spells the type as `tags` (it should be `tag`), so nothing is actually deleted and empty tags remain after the refresh. Delete empty tags manually by selecting them under "Manage → Tags"; see [Categories and tags](../content/categories-tags.md#counts).
