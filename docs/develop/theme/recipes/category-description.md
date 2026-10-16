---
title: 分类描述
sidebar_position: 4
---

# 分类描述

**问题**：在分类页的文章列表上方显示这个分类在后台填写的“分类描述”，没有描述时显示一句提示。

**做法**：在 `index.php`（或 `category.php`）的文章循环之前加入：

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

**效果**：访问 `/category/tech/` 时，标题下方显示“技术”分类的描述；描述为空时显示“这个分类还没有描述”。其他页面不输出这一段。

**原理**：

- 分类页的归档描述就是分类的“分类描述”，用 `$this->getArchiveDescription()` 取得。`header()` 输出的 `<meta name="description">` 用的也是它。
- `getDescription()` 是同一个方法的旧名字，1.3.0 起已标记为弃用，新代码请用 `getArchiveDescription()`。
- 其他页面的归档描述：标签页是标签的描述（后台标签表单中没有描述字段，通常为空），文章和独立页面是正文摘要，首页、搜索等页面是“设置 → 基本”中的站点描述。

在侧边栏列出所有分类及其描述，可以用分类列表 Widget：

```php
<?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
<dl>
    <?php while ($categories->next()): ?>
        <dt><a href="<?php $categories->permalink(); ?>"><?php $categories->name(); ?></a></dt>
        <dd><?php $categories->description(); ?></dd>
    <?php endwhile; ?>
</dl>
```
