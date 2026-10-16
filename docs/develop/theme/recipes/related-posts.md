---
title: 相关文章
sidebar_position: 3
---

# 相关文章

**问题**：在文章底部列出几篇相关的文章。

**做法**：在 `post.php` 的正文之后加入：

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

**效果**：列出与当前文章**有相同标签**的其他已发布文章，最多 5 篇，按发布时间从新到旧排列，不包括当前文章。当前文章没有标签，或者没有其他文章使用这些标签时，整段不输出。

**原理**：`related(int $limit = 5, ?string $type = null)` 返回一个文章列表 Widget。

- 默认按标签关联；第二个参数为 `'author'` 时改为列出**同一作者**的其他文章：

  ```php
  <?php $this->related(5, 'author')->to($sameAuthor); ?>
  ```

- 返回的对象和文章列表一样用 `next()` 循环，循环中可以调用 `permalink()`、`title()`、`date()`、`excerpt()` 等。
- 相关文章只适合在文章页（`post.php`）中使用。
