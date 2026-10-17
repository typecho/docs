---
title: List Widgets
sidebar_position: 4
translation: machine
source_commit: c4fe96854858d8912390c7dfe6c2eab21c87ac5c
---

# List Widgets

These are the list widgets you commonly use in theme sidebars and navigation. For usage examples, see [Calling widgets in templates](../theme/widgets.md); for the fields and methods available on each row, see [Content, comment, category and user objects](./row-widgets.md).

## Overview

| Class | Each row is | Parameters (default) | What it fetches |
|---|---|---|---|
| `\Widget\Contents\Post\Recent` | post | `pageSize` (the "Post list size" in Reading settings) | Published posts whose publish time has passed, newest first; the body is not queried |
| `\Widget\Comments\Recent` | comment | `pageSize` (the "Comment list size" in Discussion settings), `parentId` (`0`), `ignoreAuthor` (`false`) | Approved comments, by ID descending; excludes trackbacks and pingbacks when "Show comments only" is on |
| `\Widget\Metas\Category\Rows` | category | `ignore` (`0`), `current` (`0`) | All categories, in category tree and sort order |
| `\Widget\Contents\Page\Rows` | page | `ignore` (`0`), `current` (`0`) | Published pages whose publish time has passed, in page tree and "page order"; excludes the page set as the front page |
| `\Widget\Contents\Post\Date` | time period | `type` (`month`), `format` (`Y-m`), `limit` (`0`) | Published posts grouped by year / month / day |
| `\Widget\Metas\Tag\Cloud` | tag | `sort` (`count`), `desc` (`true`), `ignoreZeroCount` (`false`), `limit` (`0`) | All tags |
| `\Widget\Stat` | none (a single object) | Request parameters `cid`, `uid` (used only by the `current…` series) | Various counts |

You pass parameters as the first argument of `alloc()`, either as a query string or an array: `\Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1')`.

## Contents\Post\Recent

| Method | Description |
|---|---|
| `execute()` | Execute function |

Only the fields `cid`, `title`, `slug`, `created`, `modified`, `type`, `status`, `commentsNum`, `allow*`, `template`, `password`, `authorId` and `parent` are queried. **The body is not included**, so `content()` and `excerpt()` output nothing. When you need recent posts with excerpts, use `\Widget\Archive::allocWithAlias('recent', 'pageSize=5&type=index')`. Password-protected posts are listed too; for visitors who have not unlocked them, the title shows as "This content is password protected".

## Comments\Recent

| Method | Description |
|---|---|
| `execute()` | Execute function |

| Parameter | Description |
|---|---|
| `parentId` | Only fetch comments on one piece of content (the post ID) |
| `ignoreAuthor` | When true, excludes comments whose commenter is the author of the content |

## Metas\Category\Rows

| Method | Description |
|---|---|
| `execute()` | Execute function |
| `getAllChildIds(int $id): array` | Get all descendant nodes of a node |
| `getAllParents(int $id): array` | Get all ancestor nodes of a node |
| `getAllParentsSlug(int $id): array` | Get the slugs of all ancestor nodes of a node |
| `getChildIds(int $id): array` | Get the child nodes of a node |
| `getRow(int $id): ?array` | Get one row of data (an array) by ID |
| `getRows(array $ids, int $ignore = 0): array` | Get multiple nodes |
| `hasParent($id, $parentId): bool` | Whether the node has a given ancestor node |
| `levelsAlt(...$args)` | Output based on the depth remainder |
| `listCategories($categoryOptions = null)` | Output a hierarchical category list; options below |

| Property | Type | Description |
|---|---|---|
| `children` | `array` | Get all child nodes |
| `directory` | `array` | Multi-level path array including the ancestor slugs |

There is also `levels` (the depth, `0` at the top level). `getAllChildIds()`, `getAllParents()` and similar methods look things up in the category tree; their argument is a category ID.

| Parameter | Description |
|---|---|
| `ignore` | Excludes this category ID **and all its subcategories** |
| `current` | When `listCategories()` outputs the list, adds `category-active` to this category and `category-parent-active` to its ancestor categories |

## Contents\Page\Rows

| Method | Description |
|---|---|
| `execute()` | Execute function |
| `getAllChildIds(int $id): array` | Get all descendant nodes of a node |
| `getAllParents(int $id): array` | Get all ancestor nodes of a node |
| `getAllParentsSlug(int $id): array` | Get the slugs of all ancestor nodes of a node |
| `getChildIds(int $id): array` | Get the child nodes of a node |
| `getRow(int $id): ?array` | Get one row of data (an array) by ID |
| `getRows(array $ids, int $ignore = 0): array` | Get multiple nodes |
| `hasParent($id, $parentId): bool` | Whether the node has a given ancestor node |
| `levelsAlt(...$args)` | Output based on the depth remainder |
| `listPages($pageOptions = null)` | Output a hierarchical page list; options below |

| Property | Type | Description |
|---|---|---|
| `children` | `array` | Get all child nodes |
| `directory` | `array` | Multi-level path array including the ancestor slugs |

The `ignore` parameter works as it does for categories. `current` has no effect in 1.3.0; see below.

## Options for listCategories() and listPages()

```php
<?php \Widget\Metas\Category\Rows::alloc('current=3')->listCategories('wrapClass=widget-list&showCount=1'); ?>
```

| Option | Default | Description |
|---|---|---|
| `wrapTag` | `ul` | Outer tag of the list; sub-lists use it too |
| `wrapClass` | empty | Class of the outer tag; sub-lists use it too |
| `itemTag` | `li` | Tag of each item |
| `itemClass` | empty | Extra class on each item |
| `showCount` | `false` | Whether to show the post count after the link |
| `countTemplate` | `(%d)` | Format of the post count |
| `showFeed` | `false` | Whether to show a feed link |
| `feedTemplate` | `<a href="%s">RSS</a>` | Format of the feed link |

Each item output by `listCategories()` has these classes: `category-level-<depth>`; `category-parent` at the top level, and `category-child` plus `category-level-odd` / `category-level-even` for subcategories; and `category-active` and `category-parent-active` from the `current` parameter.

### Customizing the output of each item

If you define `treeViewCategoriesCallback($categories, $options)` in your theme's `functions.php`, `listCategories()` calls it for each top-level category instead:

```php
function treeViewCategoriesCallback($categories, $options)
{
    echo '<li><a href="' . $categories->permalink . '">' . $categories->name . '</a></li>';
}
```

Once you define the callback, subcategories are no longer output automatically; if you need them, read `$categories->children` (the array of subcategories) in the callback and output them yourself.

:::caution Known issue with listPages() in 1.3.0
`Contents\Page\Rows::listPages()` passes its arguments in the wrong order when it calls an internal method. As a result, each item's classes get names like `treeViewPagesCallback-level-0` and `treeViewPagesCallback-parent` (instead of `page-level-0`), the `current` parameter produces no highlight, and a `treeViewPagesCallback()` defined in `functions.php` is never called. When you need to control the output of the page list, loop over it yourself with `->to($pages)`; see [Page context and is()](../theme/context.md#common-patterns).
:::

## Contents\Post\Date

| Method | Description |
|---|---|
| `execute()` | Initialization function |

Fields of each row: `year`, `month`, `day`, `date` (the text formatted with `format`), `count` (the number of posts in this period), `permalink` (the archive URL).

## Metas\Tag\Cloud

| Method | Description |
|---|---|
| `execute()` | Entry function |
| `split(...$args)` | Output a string according to the split count |

The arguments of `split(...$args)` are integer thresholds. It outputs the first number greater than the tag's post count, or `0` if none is greater; for usage see [Tag cloud](../theme/recipes/tag-cloud.md). `sort` accepts field names such as `count`, `name` and `mid`.

## Stat

`\Widget\Stat` has no loop, only magic properties. Calling one with parentheses outputs it; without parentheses you get the value, for example `$stat->publishedPostsNum()`.

| Property | Type | Description |
|---|---|---|
| `categoriesNum` | `int` | Get the number of categories |
| `currentCommentsNum` | `int` | Get the number of comments on the current post |
| `currentDraftPostsNum` | `int` | Get the number of draft posts of the current user |
| `currentPublishedCommentsNum` | `int` | Get the number of displayed comments on the current post |
| `currentPublishedPostsNum` | `int` | Get the number of published posts of the current user |
| `currentSpamCommentsNum` | `int` | Get the number of spam comments on the current post |
| `currentWaitingCommentsNum` | `int` | Get the number of comments awaiting moderation on the current post |
| `currentWaitingPostsNum` | `int` | Get the number of posts awaiting review of the current user |
| `draftPagesNum` | `int` | Get the number of draft pages |
| `draftPostsNum` | `int` | Get the number of draft posts |
| `myDraftPostsNum` | `int` | Get the number of draft posts of the current user |
| `myPublishedCommentsNum` | `int` | Get the number of displayed comments of the current user |
| `myPublishedPostsNum` | `int` | Get the number of published posts of the current user |
| `mySpamCommentsNum` | `int` | Get the number of spam comments of the current user |
| `myWaitingCommentsNum` | `int` | Get the number of comments awaiting moderation of the current user |
| `myWaitingPostsNum` | `int` | Get the number of posts awaiting review of the current user |
| `publishedCommentsNum` | `int` | Get the number of currently displayed comments |
| `publishedPagesNum` | `int` | Get the number of published pages |
| `publishedPostsNum` | `int` | Get the number of published posts |
| `spamCommentsNum` | `int` | Get the current number of spam comments |
| `tagsNum` | `int` | Get the number of tags |
| `waitingCommentsNum` | `int` | Get the current number of comments awaiting moderation |
| `waitingPostsNum` | `int` | Get the number of posts awaiting review |

- The `my…` series counts for the currently logged-in user;
- The `current…Posts` series counts for the user given by the request parameter `uid`, and the `current…Comments` series counts for the content given by the request parameter `cid`. You pass these parameters as the second argument of `alloc()`: `\Widget\Stat::alloc(null, 'cid=3')`.
