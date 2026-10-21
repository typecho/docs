---
title: Categories and tags
sidebar_position: 4
translation: machine
source_commit: 5cf852b2f6ae6c6a96b12a34d8bd77f83e99bfa7
---

# Categories and tags

Categories and tags are only used for posts; pages do not have them. Both are under the "Manage" menu, and only **editors and above** can manage them. Contributors can tick existing categories and type tags when writing a post (new tags are created automatically), but cannot open the management pages.

| | Category | Tag |
|---|---|---|
| Hierarchy | Can be multi-level (category tree) | No hierarchy |
| Creating | Only under "Manage → Categories" | Created by typing it when writing a post, or under "Manage → Tags" |
| Per post | At least one (if none is selected, the post goes into the default category) | Can have none |
| Front-end URL | `/category/{slug}/` | `/tag/{slug}/` |

## Managing categories

"Manage → Categories" (`manage-categories.php`) lists the top-level categories, with the columns: Name, Subcategories, Slug, Default, Posts.

### Adding and editing

Click "Add" next to the title to open `category.php`:

| UI name | Field | Description |
|---|---|---|
| Category name * | `name` | Required; must not duplicate the name of an existing category with the same parent |
| Category slug | `slug` | Used in the URL; letters, digits, underscores and hyphens are recommended. When left empty, it is generated from the name; it must not duplicate an existing category (categories with the same name under different parents still need different slugs) |
| Parent category | `parent` | "None" makes it a top-level category; when selected, "this category will be filed under the parent category you selected" |
| Category description | `description` | Some themes display it |

Click a category name to edit it; click the external-link icon next to the name to open its front-end page.

### Category tree {#tree}

- "Add" in the "Subcategories" column creates a subcategory for that category (`category.php?parent=category ID`, with the parent category preselected); when it has subcategories, the column shows their count; clicking it opens the subcategory list (`manage-categories.php?parent=category ID`), titled "Manage subcategories of Tech".
- When writing a post, subcategories are indented in the category list.
- The rule for category URLs is changed under "Category path" in "Settings → Permalinks"; the available parameters are `{mid}`, `{slug}` and `{directory}`, which stands for a multi-level category path.

### Sorting

**Drag rows** in the category list to change the order; it is saved as soon as you release (within the same level). Themes list categories in this order.

### Default category {#default}

After installation there is one category named "Default category" (slug `default`). The default category:

- is ticked in advance when you write a new post;
- receives any post saved without a category selected.

The default category is marked in the "Default" column of the list. To change it, click "Default" in another category's row (the hover hint is "Set as default"); the message "X has been set as the default category" appears.

:::caution Do not delete the default category
1.3.0 allows you to delete the current default category. After it is deleted, the setting still points to the category that no longer exists, with the result that no category is ticked in advance when you write a new post, and posts published without selecting a category have **no category at all**. If you have already deleted it, set another category as the default in the category list to recover.
:::

### Deleting and merging

After ticking categories, use the "Selected items" menu:

| Action | Result |
|---|---|
| Delete | Deletes the category itself and its associations with posts. Subcategories move up one level |
| Refresh | Recalculates post counts; see [below](#counts) |
| Merge into (choose the target category on the right) | Posts in the selected categories are moved into the target category, the selected categories are deleted, and their subcategories are attached to the target category |

On delete, the prompt "All content under this category will be deleted. Are you sure you want to delete these categories?" appears. **This message is inaccurate**: after a category is deleted, its posts remain and simply no longer belong to it. If a post belonged only to that category, it becomes a post without any category; it is not moved into the default category automatically, and you have to assign one manually.

The "Merge into" drop-down only lists categories at the level of the current list. To merge a subcategory into a category at another level, first change its parent category on its edit page so that both are at the same level.

## Managing tags

"Manage → Tags" (`manage-tags.php`) lists all tags as a tag cloud on the left. Click tags to select them (multiple selection is possible), then use the "Selected items" menu; on the right is the add / edit form:

| UI name | Field | Description |
|---|---|---|
| Tag name * | `name` | The tag's display name; non-English names are supported |
| Tag slug | `slug` | When left empty, the tag name is used |

| Action | Result |
|---|---|
| Delete | Deletes the tags and their associations with posts |
| Refresh | Recalculates post counts |
| Merge into (enter the target tag name in the text box on the right) | The selected tags are merged into the target tag; if the target tag does not exist, it is created |

New tags typed while writing a post are created automatically; in the tag input, existing tags appear as suggestions.

## What to do when post counts are wrong {#counts}

This is a frequently asked question. The "Posts" count next to categories and tags is stored in the `count` field of the `metas` table and is increased or decreased whenever a post is published, changes status or is deleted; it is not counted live each time. The following situations make it look "wrong":

| Symptom | Cause | What to do |
|---|---|---|
| The admin number is higher than the front-end list | **Scheduled** posts (publish date in the future) are already counted but are not shown on the front end until their time | Normal; the numbers match once the time arrives |
| The front-end list contains "This content is password protected" entries | Password-protected posts are counted and also appear in lists | Normal |
| Hidden, private and pending posts, drafts, revisions and pages are not counted | Only posts **with the status public** are counted | Normal |
| The number is too high after merging tags or categories | Merging counts all associated records directly, including revisions and hidden or pending posts, so the count after merging can exceed the number of public posts | Run "Refresh" on the target after merging |
| Counts are inaccurate after importing data, restoring a backup or editing the database directly | These operations do not recount | Select all and "Refresh" |
| Empty tags with a count of 0 remain | When posts are deleted, tags no longer associated with any post are left behind | Delete them manually on the tag page |

**Refresh**: in the category or tag list, tick the items to refresh (you can select all with the checkbox in the table header), click "Refresh" in the "Selected items" menu, and confirm "Refreshing categories may take a long time…" to run it. After refreshing, the count equals the number of associated public posts (including scheduled and password-protected posts).

:::note Refreshing tags does not delete empty tags
Refreshing tags is meant to also delete tags with a count of 0 that are no longer associated with any content. In 1.3.0, however, the type name queried by this code is written as `tags` (it should be `tag`), so nothing is actually deleted, and tags with a count of 0 still exist after refreshing. Select and delete empty tags manually on the tag page.
:::

For bulk processing after importing or migrating, see also [Migrating from other blogs](../operations/migration.md#counts).
