---
title: Page Context and is()
sidebar_position: 5
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Page Context and is()

The same template file (especially `header.php`, `sidebar.php` and `index.php`) is used by many kinds of pages. `$this->is()` tells you which kind of page is currently being shown.

## Usage

```text
$this->is(string $archiveType, ?string $archiveSlug = null): bool
```

- With only the first argument, it checks the page type;
- With the second argument, it also requires the slug to match; for example `$this->is('category', 'tech')` is true only on the page of the category `tech`.

```php
<?php if ($this->is('index')): ?>
    <p>Home page</p>
<?php elseif ($this->is('post')): ?>
    <p>Post page</p>
<?php elseif ($this->is('page', 'about')): ?>
    <p>The "About" page</p>
<?php elseif ($this->is('category')): ?>
    <p>A category</p>
<?php endif; ?>
```

## Types you can check

| Argument | True on | Slug it can be combined with |
|---|---|---|
| `index` | The home page (including pagination such as `/page/2/`); also true when a page is used as the home page | — |
| `post` | Post pages | post ID |
| `page` | Pages | page slug |
| `attachment` | Attachment pages | attachment ID |
| `category` | Category pages | category slug |
| `tag` | Tag pages | tag slug |
| `author` | Author pages | user ID |
| `date` | Date archives | `year`, `month`, `day` |
| `search` | Search result pages | keyword |
| `front` | The post list page (`/blog/` by default) and its pagination, when the home page is set to a page or a template file and the [post list page](../../guide/settings/reading.md#front-archive) is enabled | — |
| `single` | **Pseudo-type**: posts, pages, attachments | as above |
| `archive` | **Pseudo-type**: all list pages except the home page (category, tag, author, date, search, post list page) plus the 404 page | the slug of the 404 page is `404` |
| `feed` | **Pseudo-type**: true when called from a feed | — |

`single`, `archive` and `feed` are not page types in their own right but groupings of several page types:

- `single` and `archive` are mutually exclusive: a page is either a single item or a list; the home page is neither.
- The type of the 404 page is `archive` and its slug is `404`, so check for 404 with `$this->is('archive', '404')`.
- Feeds (`/feed/` and so on) do not go through theme templates; `is('feed')` is mainly for plugins to use when `Widget\Archive` is called by a feed, and is rarely needed in templates.

When a page is used as the home page, `is('index')`, `is('page')` and `is('single')` are all true.

## Values of the page type and slug

You can read them directly in a template:

```php
<?php echo $this->getArchiveType(); ?>
<?php echo $this->getArchiveTitle(); ?>
```

| Method | Returns |
|---|---|
| `getArchiveType()` | The page type; values as in the first column of the table above (excluding pseudo-types) |
| `getArchiveSlug()` | The slug |
| `getArchiveTitle()` | The archive title: post / page title, category name, search keyword, etc.; `null` on the home page and the post list page |
| `getArchiveDescription()` | The archive description: category description, post excerpt; the site description on the home page and search pages |
| `getArchiveUrl()` | The URL of the current page |

:::caution The home page and the post list page have no slug
In 1.3.0, the slug of the home page (`index`) and of the post list page (`front`) is not initialized. Calling `$this->getArchiveSlug()` on either of them, or calling `$this->is('index', ...)` or `$this->is('front', ...)` with a second argument, throws the error `Typed property Widget\Archive::$archiveSlug must not be accessed before initialization` and the page becomes a 500. The post list page also satisfies `is('archive')` but not `is('index')`, so excluding the home page alone is not enough: code that needs to read the slug should first exclude both with `!$this->is('index') && !$this->is('front')`.
:::

Pages of every other type have a slug, and checks such as `is('category', 'tech')` are safe on the home page too: when the type does not match, the slug is not read.

## Common patterns {#common-patterns}

Mark the current page in the navigation menu:

```php
<?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
<?php while ($pages->next()): ?>
    <a href="<?php $pages->permalink(); ?>"<?php if ($this->is('page', $pages->slug)): ?> class="active"<?php endif; ?>><?php $pages->title(); ?></a>
<?php endwhile; ?>
```

Show the archive title only on list pages:

```php
<?php if ($this->is('archive')): ?>
    <h1><?php $this->archiveTitle('', '', ''); ?></h1>
<?php endif; ?>
```

Load the comment script only on posts and pages:

```php
<?php if ($this->is('single')): ?>
    <script src="<?php $this->options->themeUrl('comments.js'); ?>"></script>
<?php endif; ?>
```
