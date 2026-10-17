---
title: Pages
sidebar_position: 3
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Pages

Pages suit content that is not ordered by time, such as "About", "Links" or a "Guestbook". Create them under "Write → Create Page" (`admin/write-page.php`) and manage them under "Manage → Pages" (`manage-pages.php`). Only **editors and above** can create and manage pages; contributors do not see these two menus.

## Pages compared with posts

| | Post | Page |
|---|---|---|
| Who can write | Contributor and above | Editor and above |
| Appears on the home page, in archives, search and feeds | Yes | No; themes usually list pages in the navigation |
| Categories, tags | Yes | No |
| Hierarchy | None | Can have a parent page, forming a page tree |
| Sort order | By publish time | By "Page order" |
| Custom template | None | Each page can use a template from the theme |
| Visibility | Public, hidden, password protected, private, pending | Only public and hidden |
| Default address | `/archives/{cid}/` | `/{slug}.html` |
| Comments, custom fields, attachments, drafts and revisions, autosave | Yes | Yes, used the same way as in [Writing posts](./posts.md) |

## Editing a page

The editing area is the same as for posts: title, URL slug, content, [custom fields](./custom-fields.md), and the buttons "Preview page", "Save draft" and "Publish page". The "Options" panel on the right:

| UI name | Field | Description |
|---|---|---|
| Publish date | `date` | As for posts; if set to a future time, the page cannot be visited until then |
| Page order | `order` | A number. "Give your custom pages a sequence value so that they are sorted by it in ascending order" |
| Custom template | `template` | Lists the files in the current theme declared as custom templates; "None" uses the theme's `page.php` |
| Parent page | `parent` | When selected, "this page will be shown as a child page" |
| Visibility | `visibility` | Public, hidden. Hidden pages do not appear in the theme's page list but can still be visited by anyone who knows the address |
| Permissions | `allowComment`, `allowPing`, `allowFeed` | As for posts |

The **URL slug** determines the page address. The default rule is `/{slug}.html`; for example, the slug `links` gives `/links.html`. If you leave it empty, it is generated automatically: on creation it takes the ASCII letters and digits from the title (joined with `-`), and if the title has none of these characters (for example a title written only in Chinese), the page ID is used (`applySlug()` in `var/Widget/Base/Contents.php`). The rule for page addresses is changed under "Page path" in "Settings → Permalinks"; the available parameters are `{cid}`, `{slug}` and `{directory}`, where `{directory}` is a multi-level path containing the slugs of all parent pages, which suits page trees.

## Page tree {#tree}

Since 1.3, pages can have parent pages, forming a multi-level structure:

- Select a page under "Parent page" and the current page becomes its child page;
- The "Child pages" column under "Manage → Pages": when a page has no child pages it shows "Add", which creates a child page for it directly (`write-page.php?parent=page ID`, with the parent page preselected); when it has child pages it shows "one page" or "N pages", which opens the child page list (`manage-pages.php?parent=page ID`), titled "Manage child pages of About";
- When you delete a page that has child pages, its child pages move up one level and are attached to the deleted page's parent (child pages of a top-level page become top-level pages).

## Sorting

Themes usually list pages by "Page order" in ascending order. There are two ways to set it:

1. Fill in "Page order" when editing a page;
2. **Drag rows** directly under "Manage → Pages". When you release, the pages in the current list are renumbered 1, 2, 3… in their displayed order, overwriting any numbers entered by hand. Dragging only works within a list of the same level; to reorder child pages, open the child page list first.

## Custom templates {#template}

A theme can provide several page templates. A template is a PHP file in the theme directory whose leading comment contains `@package custom`; the first line of the comment is the name shown in the drop-down, for example `usr/themes/default/page-links.php`:

```php
<?php
/**
 * Links template
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<!-- Page content -->
<?php $this->need('footer.php'); ?>
```

Once the theme has such a file, "Links template" appears in the "Custom template" drop-down (`index.php` excluded). When it is selected, the page is rendered with that file on the front end. After you switch themes, if the new theme has no file with the same name, the page is displayed with the new theme's default page template.

## Managing pages

Under "Manage → Pages" you can filter by keyword; after ticking pages, use the "Selected items" menu to delete them or mark them as public or hidden.

When a page is deleted, its comments, custom fields and revision are deleted with it, and its attachments become "unattached" files; if the page was set as the site home page ("Site home page" under "Settings → Reading"), the home page goes back to showing the latest posts.
