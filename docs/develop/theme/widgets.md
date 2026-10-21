---
title: 在模板中调用 Widget
sidebar_position: 11
---

# 在模板中调用 Widget

模板中的 `$this` 只包含当前页面需要的内容。侧边栏的最新文章、分类列表、归档等其他数据，由其他 Widget 提供。在模板中用 `类名::alloc()` 取得一个 Widget，再循环或直接输出。

## 基本写法

```php
<?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->to($recent); ?>
<ul>
    <?php while ($recent->next()): ?>
        <li><a href="<?php $recent->permalink(); ?>"><?php $recent->title(); ?></a></li>
    <?php endwhile; ?>
</ul>
```

- `alloc()` 的第一个参数是**参数**，可以写成查询字符串 `'pageSize=5&ignoreAuthor=1'`，也可以写成数组 `['pageSize' => 5]`；
- `->to($recent)` 把 Widget 放进变量，之后用 `next()` 循环，循环中的用法与 `$this` 相同；
- 只是简单地逐条输出时，可以用 `parse()` 代替循环，`{字段名}` 会替换为对应的值：

  ```php
  <ul>
      <?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
  </ul>
  ```

:::caution 同一个类只会取一次
`alloc()` 按类名缓存：同一个页面中**第二次**调用同一个类的 `alloc()`，即使参数不同，拿到的也是第一次的结果。例如先在侧边栏调用了 `Recent::alloc('pageSize=5')`，再在页脚调用 `Recent::alloc('pageSize=3')`，页脚仍然是 5 篇。需要同一个类的多个实例时，用 `allocWithAlias()` 给每个实例起一个不同的别名：

```php
<?php \Widget\Contents\Post\Recent::allocWithAlias('footer-recent', 'pageSize=3')->to($footerRecent); ?>
```
:::

## 常用 Widget

### 最新文章：`\Widget\Contents\Post\Recent`

| 参数 | 默认值 | 说明 |
|---|---|---|
| `pageSize` | 阅读设置中的“文章列表数目” | 篇数 |

只包括已发布、发布时间已到的文章。密码保护的文章也会列出，未解锁的访客看到的标题是“此内容被密码保护”。

### 最新评论：`\Widget\Comments\Recent`

```php
<?php \Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1')->to($comments); ?>
<ul>
    <?php while ($comments->next()): ?>
        <li><a href="<?php $comments->permalink(); ?>"><?php $comments->author(false); ?></a>：<?php $comments->excerpt(30, '...'); ?></li>
    <?php endwhile; ?>
</ul>
```

| 参数 | 默认值 | 说明 |
|---|---|---|
| `pageSize` | 评论设置中的“评论列表数目” | 条数 |
| `ignoreAuthor` | `false` | 为 `true` 时排除文章作者自己的评论 |
| `parentId` | `0` | 只取某篇内容（文章 ID）的评论 |

`$comments->author(false)` 只输出名字、不带链接；`excerpt(30, '...')` 输出前 30 个字的纯文本。

### 分类列表：`\Widget\Metas\Category\Rows`

直接输出带层级的列表：

```php
<?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=widget-list&showCount=1'); ?>
```

`listCategories()` 的选项：`wrapTag`（默认 `ul`）、`wrapClass`、`itemTag`（默认 `li`）、`itemClass`、`showCount`（是否显示文章数）、`countTemplate`（默认 `(%d)`）、`showFeed`、`feedTemplate`。

自己循环时：

```php
<?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
<?php while ($categories->next()): ?>
    <a href="<?php $categories->permalink(); ?>"><?php $categories->name(); ?></a> (<?php $categories->count(); ?>)
<?php endwhile; ?>
```

`count()` 是这个分类本身的文章数，不包括子分类中的文章（而分类页的文章列表包括子分类的文章）。

### 独立页面列表：`\Widget\Contents\Page\Rows`

```php
<?php \Widget\Contents\Page\Rows::alloc()->listPages(); ?>
```

`listPages()` 的选项与 `listCategories()` 相同。自己循环（例如做导航菜单）的写法见[第一个主题](./quick-start.md#headerphp)。

### 按月归档：`\Widget\Contents\Post\Date`

```php
<ul>
    <?php \Widget\Contents\Post\Date::alloc('type=month&format=Y年m月')->parse('<li><a href="{permalink}">{date}</a> ({count})</li>'); ?>
</ul>
```

| 参数 | 默认值 | 说明 |
|---|---|---|
| `type` | `month` | 链接指向的归档粒度：`year`、`month`、`day` |
| `format` | `Y-m` | 显示格式，PHP `date()` 写法；同时决定文章按什么分组计数 |
| `limit` | `0` | 最多显示几个时间段，`0` 为不限 |

`parse()` 中可用的字段：`{permalink}`、`{date}`、`{count}`（文章数）、`{year}`、`{month}`、`{day}`。

文章按 `format` 格式化后的日期分组，`type` 只决定链接地址，所以两者要按同一粒度配对：年归档用 `type=year&format=Y`，月归档用 `type=month&format=Y-m`，日归档用 `type=day&format=Y-m-d`（可以换成 `Y年m月d日` 等写法）。如果只改 `type` 而不改 `format`，文章仍按月分组：年归档的链接会每个月出现一次，日归档显示的篇数是整个月的篇数。`format` 中不含年份时，不同年份的同一月份会合并成一项。

### 统计：`\Widget\Stat`

```php
<?php \Widget\Stat::alloc()->to($stat); ?>
<p>
    <?php _e('文章'); ?> <?php $stat->publishedPostsNum(); ?> ·
    <?php _e('评论'); ?> <?php $stat->publishedCommentsNum(); ?> ·
    <?php _e('分类'); ?> <?php $stat->categoriesNum(); ?> ·
    <?php _e('标签'); ?> <?php $stat->tagsNum(); ?>
</p>
```

常用的统计项：`publishedPostsNum`（已发布文章）、`publishedPagesNum`（已发布页面）、`publishedCommentsNum`（已通过的评论）、`categoriesNum`、`tagsNum`。带括号调用为输出，不带括号为取值。

### 标签云：`\Widget\Metas\Tag\Cloud`

见[标签云](./recipes/tag-cloud.md)。

## 取某个分类的文章

直接使用 `\Widget\Archive`，并指定页面类型和分类，即可取得这个分类下的文章：

```php
<?php \Widget\Archive::allocWithAlias('category-tech', 'pageSize=5&type=category', 'slug=tech')->to($tech); ?>
<ul>
    <?php while ($tech->next()): ?>
        <li><a href="<?php $tech->permalink(); ?>"><?php $tech->title(); ?></a></li>
    <?php endwhile; ?>
</ul>
```

- 第一个参数是别名，必须用 `allocWithAlias()` 并起一个独有的名字，否则会和当前页面的 `$this`（同为 `Widget\Archive`）混在一起；
- 第二个参数中，`type=category` 表示按分类取，`pageSize` 为篇数；
- 第三个参数指定是哪个分类：`slug=tech`（缩略名）或 `mid=2`（分类 ID）；
- 结果包括子分类中的文章，按发布时间从新到旧排列。

同样的方式可以取某个标签的文章：`'pageSize=5&type=tag'`、`'slug=php'`。

## 参数的写法

`alloc()` 和各个输出方法（`listCategories()`、`listComments()` 等）的参数都可以写成查询字符串或数组，两种写法等价：

```php
<?php \Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1'); ?>
<?php \Widget\Comments\Recent::alloc(['pageSize' => 5, 'ignoreAuthor' => true]); ?>
```

查询字符串中的值都是字符串，`1` / `0` 表示真假。值本身含有 `&`、`=` 时请用数组写法。
