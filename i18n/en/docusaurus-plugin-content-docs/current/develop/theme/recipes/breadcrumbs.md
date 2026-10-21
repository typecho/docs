---
title: Breadcrumb Navigation
sidebar_position: 2
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Breadcrumb Navigation

**Problem**: show a breadcrumb trail such as "Home » Tech » Post title" at the top of the page.

**Solution**: add the following after `</header>` in `header.php` (or in any template):

```php
<nav class="breadcrumbs">
    <a href="<?php $this->options->siteUrl(); ?>"><?php _e('Home'); ?></a>
    <?php if ($this->is('post')): ?>
        &raquo; <?php $this->category(' / '); ?>
        &raquo; <?php $this->title(); ?>
    <?php elseif ($this->is('front')): ?>
        &raquo; <?php _e('Blog'); ?>
    <?php elseif (!$this->is('index')): ?>
        &raquo; <?php $this->archiveTitle([
            'category' => _t('Category %s'),
            'search'   => _t('Search %s'),
            'tag'      => _t('Tag %s'),
            'author'   => _t('Author %s'),
        ], '', ''); ?>
    <?php endif; ?>
</nav>
```

**Result**:

| Page | Output |
|---|---|
| Home page | Home |
| Post | Home » Tech » Post title (when the post has several categories they are separated by ` / `, and each category is a link) |
| Page | Home » Page title |
| Category, tag, search, author | Home » Category Tech |
| Date archive | Home » October 2026 |
| Post list page | Home » Blog |
| 404 | Home » Page not found |

**How it works**:

- `$this->is('post')` is true only on post pages; there, `$this->category(' / ')` outputs links to the categories the post belongs to. When the second argument is `false`, only the category names are output, without links.
- The post list page (`front`, see [Page context](../context.md)) has no archive title, so a fixed label is output for it.
- Every other page that is not the home page has an archive title, which is output directly with `archiveTitle()`; the format in the array is chosen by page type.
- The home page is detected with `$this->is('index')`, and nothing is appended. When a page is used as the home page, `is('index')` is true as well.
