---
title: Custom Page Titles
sidebar_position: 1
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Custom Page Titles

**Problem**: you want the title in browser tabs and search results to change with the page type, for example "Posts in category Tech - Site name" on a category page and "Site name - Site description" on the home page.

**Solution**: replace the `<title>` in `header.php` with the following:

```php
<title><?php $this->archiveTitle([
    'category' => _t('Posts in category %s'),
    'search'   => _t('Posts containing %s'),
    'tag'      => _t('Posts tagged %s'),
    'author'   => _t('Posts by %s'),
], '', ' - '); ?><?php $this->options->title(); ?><?php if ($this->is('index')): ?> - <?php $this->options->description(); ?><?php endif; ?></title>
```

**Result**:

| Page | Title |
|---|---|
| Home page | Site name - Site description |
| Posts, pages | Post title - Site name |
| Category | Posts in category Tech - Site name |
| Tag | Posts tagged PHP - Site name |
| Search | Posts containing the keyword Typecho - Site name |
| Author | Posts by admin - Site name |
| Date archive | October 2026 - Site name |
| 404 | Page not found - Site name |

**How it works**: `archiveTitle($defines, $before, $end)` outputs "`$before` + title + `$end`" only when the current page has an archive title; the home page has no archive title, so nothing is output.

- `$defines` gives a format per page type (`category`, `tag`, `search`, `author`, `date`, `post`, `page`, etc.); `%s` is replaced with the category name, keyword and so on; for types not listed, the title itself is output.
- `$before` defaults to ` &raquo; ` and `$end` defaults to empty. Above, `$before` is set to empty and `$end` to ` - `, so the title comes first and the site name after it.
- When you need the title string rather than direct output, use `$this->getArchiveTitle()`.
