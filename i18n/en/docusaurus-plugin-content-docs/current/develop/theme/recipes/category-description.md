---
title: Category Descriptions
sidebar_position: 4
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Category Descriptions

**Problem**: above the post list on a category page, show the "Category description" entered for that category in the admin panel, and a short notice when there is no description.

**Solution**: add the following before the post loop in `index.php` (or `category.php`):

```php
<?php if ($this->is('category')): ?>
    <header class="category-header">
        <h1><?php $this->archiveTitle('', '', ''); ?></h1>
        <?php if ($this->getArchiveDescription()): ?>
            <p><?php echo htmlspecialchars($this->getArchiveDescription()); ?></p>
        <?php else: ?>
            <p><?php _e('这个分类还没有描述'); ?></p>
        <?php endif; ?>
    </header>
<?php endif; ?>
```

**Result**: when you visit `/category/tech/`, the description of the "Tech" category appears below the heading; when the description is empty, "这个分类还没有描述" (this category has no description yet) is shown. Other pages do not output this block.

**How it works**:

- The archive description of a category page is the category's "Category description", obtained with `$this->getArchiveDescription()`. The `<meta name="description">` output by `header()` uses it too.
- `getDescription()` is the old name of the same method; it is marked deprecated as of 1.3.0, so use `getArchiveDescription()` in new code.
- Archive descriptions of other pages: on tag pages it is the tag's description (the tag form in the admin panel has no description field, so it is usually empty); on posts and pages it is the excerpt of the body; on the home page, search and similar pages it is the site description from "Settings → General".

To list all categories with their descriptions in a sidebar, use the category list widget:

```php
<?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
<dl>
    <?php while ($categories->next()): ?>
        <dt><a href="<?php $categories->permalink(); ?>"><?php $categories->name(); ?></a></dt>
        <dd><?php $categories->description(); ?></dd>
    <?php endwhile; ?>
</dl>
```
