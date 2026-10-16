---
title: 标签云
sidebar_position: 5
---

# 标签云

**问题**：在侧边栏或单独的页面中显示标签云，文章越多的标签字号越大。

**做法**：

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

配合几行 CSS：

```css
.tag-cloud a { margin-right: .5em; }
.tag-cloud .size-0 { font-size: 1.6em; }
.tag-cloud .size-2 { font-size: 1em; }
.tag-cloud .size-5 { font-size: 1.15em; }
.tag-cloud .size-10 { font-size: 1.3em; }
.tag-cloud .size-20 { font-size: 1.45em; }
```

**效果**：按文章数从多到少列出最多 30 个标签，跳过没有文章的标签；每个标签带上 `size-2`、`size-5`、`size-10`、`size-20` 或 `size-0` 中的一个 class。

**原理**：

`\Widget\Metas\Tag\Cloud` 的参数：

| 参数 | 默认值 | 含义 |
|---|---|---|
| `sort` | `count` | 排序字段，常用 `count`（文章数）、`name`（名称）、`mid`（创建顺序） |
| `desc` | `true` | 是否倒序 |
| `ignoreZeroCount` | `false` | 是否跳过文章数为 0 的标签 |
| `limit` | `0` | 最多取多少个，`0` 为不限 |

`$tags->split(2, 5, 10, 20)` 输出第一个**大于**该标签文章数的数字：文章数为 3 时输出 `5`，为 12 时输出 `20`；比所有数字都大时输出 `0`。参数必须是整数。

在 1.3 之前的主题中，标签云写作 `$this->widget('Widget_Metas_Tag_Cloud', ...)` 或 `Typecho_Widget::widget('Widget_Metas_Tag_Cloud', ...)`。旧类名 `Widget_Metas_Tag_Cloud` 在 1.3 中会自动对应到 `\Widget\Metas\Tag\Cloud`，所以 `\Widget_Metas_Tag_Cloud::alloc()` 仍然可用；新主题请直接使用带命名空间的类名。
