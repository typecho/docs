---
title: Tag Cloud
sidebar_position: 5
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Tag Cloud

**Problem**: show a tag cloud in the sidebar or on a separate page, where tags with more posts get a larger font size.

**Solution**:

```php
<?php \Widget\Metas\Tag\Cloud::alloc('sort=count&ignoreZeroCount=1&desc=1&limit=30')->to($tags); ?>
<?php if ($tags->have()): ?>
    <div class="tag-cloud">
        <?php while ($tags->next()): ?>
            <a href="<?php $tags->permalink(); ?>" class="size-<?php $tags->split(2, 5, 10, 20); ?>" title="<?php $tags->count(); ?>"><?php $tags->name(); ?></a>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
```

With a few lines of CSS:

```css
.tag-cloud a { margin-right: .5em; }
.tag-cloud .size-0 { font-size: 1.6em; }
.tag-cloud .size-2 { font-size: 1em; }
.tag-cloud .size-5 { font-size: 1.15em; }
.tag-cloud .size-10 { font-size: 1.3em; }
.tag-cloud .size-20 { font-size: 1.45em; }
```

**Result**: lists at most 30 tags ordered by post count from most to fewest, skipping tags with no posts; each tag gets one of the classes `size-2`, `size-5`, `size-10`, `size-20` or `size-0`.

**How it works**:

Parameters of `\Widget\Metas\Tag\Cloud`:

| Parameter | Default | Meaning |
|---|---|---|
| `sort` | `count` | The sort field; commonly `count` (number of posts), `name` (name), `mid` (creation order) |
| `desc` | `true` | Whether to sort in descending order |
| `ignoreZeroCount` | `false` | Whether to skip tags with 0 posts |
| `limit` | `0` | The maximum number to fetch; `0` means unlimited |

`$tags->split(2, 5, 10, 20)` outputs the first number that is **greater than** the tag's post count: `5` for a count of 3, `20` for a count of 12; when the count is larger than all the numbers, it outputs `0`. The arguments must be integers.

In themes written before 1.3, the tag cloud was written as `$this->widget('Widget_Metas_Tag_Cloud', ...)` or `Typecho_Widget::widget('Widget_Metas_Tag_Cloud', ...)`. In 1.3 the old class name `Widget_Metas_Tag_Cloud` maps automatically to `\Widget\Metas\Tag\Cloud`, so `\Widget_Metas_Tag_Cloud::alloc()` still works; new themes should use the namespaced class name directly.
