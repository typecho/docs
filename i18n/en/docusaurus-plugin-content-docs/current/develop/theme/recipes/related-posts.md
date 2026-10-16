---
title: Related Posts
sidebar_position: 3
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Related Posts

**Problem**: list a few related posts at the bottom of a post.

**Solution**: add the following after the body in `post.php`:

```php
<?php $this->related(5)->to($relatedPosts); ?>
<?php if ($relatedPosts->have()): ?>
    <section class="related">
        <h3><?php _e('相关文章'); ?></h3>
        <ul>
            <?php while ($relatedPosts->next()): ?>
                <li><a href="<?php $relatedPosts->permalink(); ?>"><?php $relatedPosts->title(); ?></a></li>
            <?php endwhile; ?>
        </ul>
    </section>
<?php endif; ?>
```

**Result**: lists other published posts that **share a tag** with the current post, at most 5, ordered by publish time from newest to oldest, excluding the current post. When the current post has no tags, or no other post uses those tags, the whole block is not output.

**How it works**: `related(int $limit = 5, ?string $type = null)` returns a post list widget.

- By default posts are related by tag; when the second argument is `'author'`, it lists other posts by **the same author** instead:

  ```php
  <?php $this->related(5, 'author')->to($sameAuthor); ?>
  ```

- Loop over the returned object with `next()` just like a post list; inside the loop you can call `permalink()`, `title()`, `date()`, `excerpt()` and so on.
- Related posts are only meant for post pages (`post.php`).
