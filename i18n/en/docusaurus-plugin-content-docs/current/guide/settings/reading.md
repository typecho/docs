---
title: Reading Settings
sidebar_position: 3
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# Reading Settings

This page covers "Settings → Read" (`admin/options-reading.php`) in the admin panel, which only administrators can access. It determines what the front page shows, how many posts appear per page, how dates are displayed, and whether feeds output full text or summaries.

## Fields at a glance

| UI label | Option name | Default | Description |
|---|---|---|---|
| Post date format | `postDateFormat` | `Y-m-d` | See [below](#date-format) |
| Site front page | `frontPage` | `recent` | See [below](#front-page) |
| Also change the post list path to | `frontArchive`, `archivePattern` | Unchecked, `/blog/` | Appears only when the front page is not "Show the latest posts" |
| Number of posts in lists | `postsListSize` | 10 | The number of posts shown in lists such as "Recent posts" in the sidebar |
| Posts per page | `pageSize` | 5 | The number of posts per page in post lists such as the front page, categories, tags, search and archives |
| Full-text feed output | `feedFullText` | Full text (`1`) | `0` outputs summaries only, `1` outputs full text; see [Feeds and offline clients](../content/feeds-xmlrpc.md#feeds) |

## Post date format {#date-format}

"Post date format" (`postDateFormat`) is the default format a theme uses when outputting post dates. It is written with PHP `date()` format characters; the "PHP date format" link in the admin description points to this function in the PHP manual. The description also notes: "This format may not take effect in some themes, because theme authors can customize the date format."

Common formats, using 5:23 pm on 16 October 2026 as an example:

| Format | Output |
|---|---|
| `Y-m-d` (default) | 2026-10-16 |
| `D, j M Y` | Fri, 16 Oct 2026 |
| `l, F j, Y g:i a` | Friday, October 16, 2026 5:23 pm |
| `M j, Y` | Oct 16, 2026 |
| `D, d M Y` | Fri, 16 Oct 2026 |

`Y` four-digit year, `m` / `n` month (with / without a leading zero), `d` / `j` day, `H` hour in 24-hour format, `i` minutes; to output a letter literally, put a backslash in front of it. For the full description and more examples, see [Discussion settings: comment date format](./discussion.md#date-format). For example, after you change it to `F j, Y`, dates in the default theme's post list are shown as "October 16, 2026".

The format cannot contain special characters such as `<` and `>`; otherwise the message "Please do not use special characters in the date format" appears.

## Site front page {#front-page}

"Site front page" (`frontPage`) has three possible values; the last two appear in the UI only when their conditions are met:

| UI option | Saved value | Appears when | Front page shows |
|---|---|---|---|
| Show the latest posts | `recent` | Always | Default; lists posts in reverse chronological order |
| Use the [page] page as the front page | `page:<page ID>`, for example `page:2` | At least one published page exists | The content of the selected page |
| Call the [file] template file directly | `file:<file name>`, for example `file:home.php` | The current theme has a file declared as a front page template | The output of the selected template file |

### Using a page as the front page

For example, after you select the "About" page, `frontPage` is saved as `page:2` and the front page shows that page's content; the page's original URL (such as `/start-page.html`) can still be visited. When comments are posted on this page from the front page, "Check the comment referring page" treats the front page URL as a valid source.

### Calling a template file directly

Any PHP file in the theme directory other than `index.php` appears in the "Call the [file] template file directly" drop-down if its opening comment contains `@package index`. Neither bundled theme has such a file, so this option is not visible by default. For example, create `home.php` in the theme:

```php
<?php
/**
 * Custom home page
 *
 * @package index
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<!-- Home page content -->
```

After you select it, `frontPage` is saved as `file:home.php` and the front page outputs this file directly. When you change themes, if the front page is set to a template file, it is automatically reset to "Show the latest posts".

:::caution `/page/2/` fails with a template file front page
In 1.3.0, once the front page is set to a template file, visiting the front page's pagination URLs (such as `/page/2/`) returns a 500 error (`Typed property Widget\Archive::$currentPage must not be accessed before initialization`). Do not output pagination links to `/page/N/` in the template file.
:::

:::caution Calling header() in the template file fails
In 1.3.0, when the front page is set to a template file, Typecho does not initialize the feed URLs. As soon as the template (or the `header.php` it includes) calls `$this->header()`, the front page fails with `Typed property Widget\Archive::$archiveFeedUrl must not be accessed before initialization`. This is fixed only in code after 1.3.0. You can avoid it by setting the feed URLs first with methods such as `setArchiveFeedUrl()` at the top of the template; for how, see [Front page template](../../develop/theme/custom-templates.md#home-page-template) in theme development.
:::

### Post list page {#front-archive}

After the front page is changed to a page or a template file, the post list that used to be on the front page has nowhere to be shown. If you check "Also change the post list path to [path]" (`frontArchive`; the path is stored in the `archive` rule of the routing table, default `/blog/`), the post list appears under that path, with pagination URLs `<path>/page/N/`.

For example, after you change the path to `/posts/` and save, `/posts/` shows the post list and the old `/blog/` returns 404. When it is unchecked, this path also returns 404, and visitors can only see post lists through category, tag, archive and similar pages. When the front page is changed back to "Show the latest posts", `frontArchive` automatically becomes 0.

## Number of posts in lists and posts per page

- "Posts per page" (`pageSize`) affects the pagination of all post list pages; after you make it smaller, there are more pages, and the content behind existing `/page/N/` URLs changes.
- "Number of posts in lists" (`postsListSize`) only affects lists of recent posts such as those in a theme's sidebar.

Both numbers must be integers; otherwise the message "Please enter a number" appears.
