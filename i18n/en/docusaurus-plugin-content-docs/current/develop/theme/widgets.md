---
title: Calling Widgets in Templates
sidebar_position: 11
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Calling Widgets in Templates

`$this` in a template holds only the content the current page needs. Other data, such as the latest posts, the category list or archives for a sidebar, is provided by other widgets. In a template, get a widget with `ClassName::alloc()`, then loop over it or output it directly.

## Basic usage

```php
<?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->to($recent); ?>
<ul>
    <?php while ($recent->next()): ?>
        <li><a href="<?php $recent->permalink(); ?>"><?php $recent->title(); ?></a></li>
    <?php endwhile; ?>
</ul>
```

- The first argument of `alloc()` is the **parameters**, written either as a query string `'pageSize=5&ignoreAuthor=1'` or as an array `['pageSize' => 5]`;
- `->to($recent)` puts the widget into a variable; then loop with `next()`, and inside the loop use it the same way as `$this`;
- When you only need to output the items one by one, you can use `parse()` instead of a loop; `{field}` (a field name in braces) is replaced with the corresponding value:

  ```php
  <ul>
      <?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
  </ul>
  ```

:::caution Each class is fetched only once
`alloc()` caches by class name: the **second** call to `alloc()` of the same class on the same page returns the result of the first call, even with different parameters. For example, if the sidebar first calls `Recent::alloc('pageSize=5')` and the footer then calls `Recent::alloc('pageSize=3')`, the footer still gets 5 posts. When you need several instances of the same class, use `allocWithAlias()` to give each instance a different alias:

```php
<?php \Widget\Contents\Post\Recent::allocWithAlias('footer-recent', 'pageSize=3')->to($footerRecent); ?>
```
:::

## Common widgets

### Latest posts: `\Widget\Contents\Post\Recent`

| Parameter | Default | Description |
|---|---|---|
| `pageSize` | "Number of posts in lists" in the reading settings | Number of posts |

Only published posts whose publish time has arrived are included. Password-protected posts are listed too; visitors who have not unlocked them see the title "This content is password protected".

### Latest comments: `\Widget\Comments\Recent`

```php
<?php \Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1')->to($comments); ?>
<ul>
    <?php while ($comments->next()): ?>
        <li><a href="<?php $comments->permalink(); ?>"><?php $comments->author(false); ?></a>: <?php $comments->excerpt(30, '...'); ?></li>
    <?php endwhile; ?>
</ul>
```

| Parameter | Default | Description |
|---|---|---|
| `pageSize` | "Number of comments in lists" in the comment settings | Number of comments |
| `ignoreAuthor` | `false` | When `true`, excludes the post author's own comments |
| `parentId` | `0` | Fetch only the comments of one item (post ID) |

`$comments->author(false)` outputs only the name, without a link; `excerpt(30, '...')` outputs the first 30 characters as plain text.

### Category list: `\Widget\Metas\Category\Rows`

Output a hierarchical list directly:

```php
<?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=widget-list&showCount=1'); ?>
```

Options of `listCategories()`: `wrapTag` (default `ul`), `wrapClass`, `itemTag` (default `li`), `itemClass`, `showCount` (whether to show the post count), `countTemplate` (default `(%d)`), `showFeed`, `feedTemplate`.

Looping yourself:

```php
<?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
<?php while ($categories->next()): ?>
    <a href="<?php $categories->permalink(); ?>"><?php $categories->name(); ?></a> (<?php $categories->count(); ?>)
<?php endwhile; ?>
```

`count()` is the number of posts in the category itself and does not include posts in subcategories (whereas the post list on a category page does include posts from subcategories).

### Page list: `\Widget\Contents\Page\Rows`

```php
<?php \Widget\Contents\Page\Rows::alloc()->listPages(); ?>
```

The options of `listPages()` are the same as those of `listCategories()`. For looping yourself (for example to build a navigation menu), see [Your first theme](./quick-start.md#headerphp).

### Monthly archives: `\Widget\Contents\Post\Date`

```php
<ul>
    <?php \Widget\Contents\Post\Date::alloc('type=month&format=F Y')->parse('<li><a href="{permalink}">{date}</a> ({count})</li>'); ?>
</ul>
```

| Parameter | Default | Description |
|---|---|---|
| `type` | `month` | The archive granularity the links point to: `year`, `month`, `day` |
| `format` | `Y-m` | Display format, in PHP `date()` syntax; also decides how posts are grouped and counted |
| `limit` | `0` | The maximum number of periods to show; `0` means unlimited |

Fields available in `parse()`: `{permalink}`, `{date}`, `{count}` (number of posts), `{year}`, `{month}`, `{day}`.

Posts are grouped by their date formatted with `format`; `type` only decides the link URL, so the two must be paired at the same granularity: `type=year&format=Y` for yearly archives, `type=month&format=Y-m` for monthly archives and `type=day&format=Y-m-d` for daily archives (other notations such as `F j, Y` also work). Changing only `type` still groups by month: a yearly archive then appears once per month, and a daily archive shows the count for the whole month. When `format` does not include the year, the same month of different years is merged into one item.

### Statistics: `\Widget\Stat`

```php
<?php \Widget\Stat::alloc()->to($stat); ?>
<p>
    <?php _e('Posts'); ?> <?php $stat->publishedPostsNum(); ?> ·
    <?php _e('Comments'); ?> <?php $stat->publishedCommentsNum(); ?> ·
    <?php _e('Categories'); ?> <?php $stat->categoriesNum(); ?> ·
    <?php _e('Tags'); ?> <?php $stat->tagsNum(); ?>
</p>
```

Common statistics: `publishedPostsNum` (published posts), `publishedPagesNum` (published pages), `publishedCommentsNum` (approved comments), `categoriesNum`, `tagsNum`. Calling with parentheses outputs the value; without parentheses returns it.

### Tag cloud: `\Widget\Metas\Tag\Cloud`

See [Tag cloud](./recipes/tag-cloud.md).

## Fetching the posts of a category

Use `\Widget\Archive` itself, specifying the page type and the category; what you get is the posts in that category:

```php
<?php \Widget\Archive::allocWithAlias('category-tech', 'pageSize=5&type=category', 'slug=tech')->to($tech); ?>
<ul>
    <?php while ($tech->next()): ?>
        <li><a href="<?php $tech->permalink(); ?>"><?php $tech->title(); ?></a></li>
    <?php endwhile; ?>
</ul>
```

- The first argument is the alias. You must use `allocWithAlias()` with a unique name, otherwise it gets mixed up with `$this` of the current page (which is also a `Widget\Archive`);
- In the second argument, `type=category` means fetch by category, and `pageSize` is the number of posts;
- The third argument specifies which category: `slug=tech` (slug) or `mid=2` (category ID);
- The result includes posts in subcategories, ordered by publish time from newest to oldest.

You can fetch the posts of a tag the same way: `'pageSize=5&type=tag'`, `'slug=php'`.

## Writing parameters

The parameters of `alloc()` and of the various output methods (`listCategories()`, `listComments()` and so on) can be written as a query string or as an array; the two forms are equivalent:

```php
<?php \Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1'); ?>
<?php \Widget\Comments\Recent::alloc(['pageSize' => 5, 'ignoreAuthor' => true]); ?>
```

Values in a query string are all strings; `1` / `0` mean true / false. When a value itself contains `&` or `=`, use the array form.
