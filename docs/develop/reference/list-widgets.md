---
title: 列表类 Widget
sidebar_position: 4
---

# 列表类 Widget

主题侧边栏、导航中常用的几个列表 Widget。用法示例见[在模板中调用 Widget](../theme/widgets.md)；每一行可用的字段和方法见[内容、评论、分类与用户对象](./row-widgets.md)。

## 总览

| 类 | 每一行是 | 参数（默认值） | 取哪些数据 |
|---|---|---|---|
| `\Widget\Contents\Post\Recent` | 文章 | `pageSize`（阅读设置的“文章列表数目”） | 已发布、发布时间已到的文章，按时间倒序；不查询正文 |
| `\Widget\Comments\Recent` | 评论 | `pageSize`（评论设置的“评论列表数目”）、`parentId`（`0`）、`ignoreAuthor`（`false`） | 已通过的评论，按 ID 倒序；开启“仅显示评论”时不含引用 |
| `\Widget\Metas\Category\Rows` | 分类 | `ignore`（`0`）、`current`（`0`） | 全部分类，按分类树和排序 |
| `\Widget\Contents\Page\Rows` | 独立页面 | `ignore`（`0`）、`current`（`0`） | 已发布、发布时间已到的页面，按页面树和“页面顺序”；不含被设为首页的页面 |
| `\Widget\Contents\Post\Date` | 时间段 | `type`（`month`）、`format`（`Y-m`）、`limit`（`0`） | 已发布文章按年 / 月 / 日分组 |
| `\Widget\Metas\Tag\Cloud` | 标签 | `sort`（`count`）、`desc`（`true`）、`ignoreZeroCount`（`false`）、`limit`（`0`） | 全部标签 |
| `\Widget\Stat` | 无（单个对象） | 请求参数 `cid`、`uid`（只用于 `current…` 系列） | 各类计数 |

参数通过 `alloc()` 的第一个参数传入，查询字符串或数组均可：`\Widget\Comments\Recent::alloc('pageSize=5&ignoreAuthor=1')`。

## Contents\Post\Recent

| 方法 | 说明 |
|---|---|
| `execute()` | 执行函数 |

只查询了 `cid`、`title`、`slug`、`created`、`modified`、`type`、`status`、`commentsNum`、`allow*`、`template`、`password`、`authorId`、`parent` 这些字段，**不含正文**，所以 `content()`、`excerpt()` 输出为空。需要带摘要的最新文章时，用 `\Widget\Archive::allocWithAlias('recent', 'pageSize=5&type=index')`。密码保护的文章也会列出，标题对未解锁的访客显示为“此内容被密码保护”。

## Comments\Recent

| 方法 | 说明 |
|---|---|
| `execute()` | 执行函数 |

| 参数 | 说明 |
|---|---|
| `parentId` | 只取某篇内容（文章 ID）的评论 |
| `ignoreAuthor` | 为真时排除评论者就是内容作者的评论 |

## Metas\Category\Rows

| 方法 | 说明 |
|---|---|
| `execute()` | 执行函数 |
| `getAllChildIds(int $id): array` | 获取某个节点下的所有子节点 |
| `getAllParents(int $id): array` | 获取某个节点所有父级节点 |
| `getAllParentsSlug(int $id): array` | 获取某个节点所有父级节点缩略名 |
| `getChildIds(int $id): array` | 获取某个节点下的子节点 |
| `getRow(int $id): ?array` | 按 ID 取一行数据（数组） |
| `getRows(array $ids, int $ignore = 0): array` | 获取多个节点 |
| `hasParent($id, $parentId): bool` | 是否拥有某个父级节点 |
| `levelsAlt(...$args)` | 根据深度余数输出 |
| `listCategories($categoryOptions = null)` | 输出带层级的分类列表，选项见下文 |

| 属性 | 类型 | 说明 |
|---|---|---|
| `children` | `array` | 获取所有子节点 |
| `directory` | `array` | 包含父级缩略名的多级路径数组 |

另有 `levels`（层级，顶层为 0）。`getAllChildIds()`、`getAllParents()` 等用来在分类树中查找，参数为分类 ID。

| 参数 | 说明 |
|---|---|
| `ignore` | 排除这个分类 ID **及其所有子分类** |
| `current` | `listCategories()` 输出时，给这个分类加 `category-active`，给它的上级分类加 `category-parent-active` |

## Contents\Page\Rows

| 方法 | 说明 |
|---|---|
| `execute()` | 执行函数 |
| `getAllChildIds(int $id): array` | 获取某个节点下的所有子节点 |
| `getAllParents(int $id): array` | 获取某个节点所有父级节点 |
| `getAllParentsSlug(int $id): array` | 获取某个节点所有父级节点缩略名 |
| `getChildIds(int $id): array` | 获取某个节点下的子节点 |
| `getRow(int $id): ?array` | 按 ID 取一行数据（数组） |
| `getRows(array $ids, int $ignore = 0): array` | 获取多个节点 |
| `hasParent($id, $parentId): bool` | 是否拥有某个父级节点 |
| `levelsAlt(...$args)` | 根据深度余数输出 |
| `listPages($pageOptions = null)` | 输出带层级的页面列表，选项见下文 |

| 属性 | 类型 | 说明 |
|---|---|---|
| `children` | `array` | 获取所有子节点 |
| `directory` | `array` | 包含父级缩略名的多级路径数组 |

参数 `ignore` 同分类。`current` 在 1.3.0 中不起作用，见下文。

## listCategories() 与 listPages() 的选项

```php
<?php \Widget\Metas\Category\Rows::alloc('current=3')->listCategories('wrapClass=widget-list&showCount=1'); ?>
```

| 选项 | 默认值 | 说明 |
|---|---|---|
| `wrapTag` | `ul` | 列表外层标签，子列表也使用它 |
| `wrapClass` | 空 | 外层标签的 class，子列表也使用它 |
| `itemTag` | `li` | 每一项的标签 |
| `itemClass` | 空 | 每一项额外的 class |
| `showCount` | `false` | 是否在链接后显示文章数 |
| `countTemplate` | `(%d)` | 文章数的格式 |
| `showFeed` | `false` | 是否显示订阅链接 |
| `feedTemplate` | `<a href="%s">RSS</a>` | 订阅链接的格式 |

`listCategories()` 输出的每一项带有这些 class：`category-level-层级`，顶层为 `category-parent`，子分类为 `category-child` 加 `category-level-odd` / `category-level-even`，以及 `current` 参数产生的 `category-active`、`category-parent-active`。

### 自定义每一项的输出

在主题的 `functions.php` 中定义 `treeViewCategoriesCallback($categories, $options)`，`listCategories()` 就改为对每个顶层分类调用它：

```php
function treeViewCategoriesCallback($categories, $options)
{
    echo '<li><a href="' . $categories->permalink . '">' . $categories->name . '</a></li>';
}
```

定义了回调后，子分类不会再自动输出；需要时在回调中读取 `$categories->children`（子分类数组）自己输出。

:::caution 1.3.0 中 listPages() 的已知问题
`Contents\Page\Rows::listPages()` 调用内部方法时参数错位，导致：每一项的 class 变成 `treeViewPagesCallback-level-0`、`treeViewPagesCallback-parent` 这样的名字（而不是 `page-level-0`），`current` 参数不会产生高亮，`functions.php` 中定义的 `treeViewPagesCallback()` 也不会被调用。需要控制页面列表的输出时，用 `->to($pages)` 自己循环，见[页面上下文与 is()](../theme/context.md#常见写法)。
:::

## Contents\Post\Date

| 方法 | 说明 |
|---|---|
| `execute()` | 初始化函数 |

每一行的字段：`year`、`month`、`day`、`date`（按 `format` 格式化的文字）、`count`（这一时间段的文章数）、`permalink`（归档地址）。

## Metas\Tag\Cloud

| 方法 | 说明 |
|---|---|
| `execute()` | 入口函数 |
| `split(...$args)` | 按分割数输出字符串 |

`split(...$args)` 的参数是整数阈值，输出第一个大于该标签文章数的数字，都不大于时输出 `0`，用法见[标签云](../theme/recipes/tag-cloud.md)。`sort` 可用 `count`、`name`、`mid` 等字段名。

## Stat

`\Widget\Stat` 没有循环，只有魔术属性；带括号调用为输出，不带括号为取值，例如 `$stat->publishedPostsNum()`。

| 属性 | 类型 | 说明 |
|---|---|---|
| `categoriesNum` | `int` | 获取分类数目 |
| `currentCommentsNum` | `int` | 获取当前文章的评论数目 |
| `currentDraftPostsNum` | `int` | 获取当前用户草稿文章数目 |
| `currentPublishedCommentsNum` | `int` | 获取当前文章显示的评论数目 |
| `currentPublishedPostsNum` | `int` | 获取当前用户已发布的文章数目 |
| `currentSpamCommentsNum` | `int` | 获取当前文章垃圾评论数目 |
| `currentWaitingCommentsNum` | `int` | 获取当前文章待审核的评论数目 |
| `currentWaitingPostsNum` | `int` | 获取当前用户待审核文章数目 |
| `draftPagesNum` | `int` | 获取草稿页面数目 |
| `draftPostsNum` | `int` | 获取草稿文章数目 |
| `myDraftPostsNum` | `int` | 获取当前用户草稿文章数目 |
| `myPublishedCommentsNum` | `int` | 获取当前用户显示的评论数目 |
| `myPublishedPostsNum` | `int` | 获取当前用户已发布的文章数目 |
| `mySpamCommentsNum` | `int` | 获取当前用户垃圾评论数目 |
| `myWaitingCommentsNum` | `int` | 获取当前用户待审核的评论数目 |
| `myWaitingPostsNum` | `int` | 获取当前用户待审核文章数目 |
| `publishedCommentsNum` | `int` | 获取当前显示的评论数目 |
| `publishedPagesNum` | `int` | 获取已发布页面数目 |
| `publishedPostsNum` | `int` | 获取已发布的文章数目 |
| `spamCommentsNum` | `int` | 获取当前垃圾评论数目 |
| `tagsNum` | `int` | 获取标签数目 |
| `waitingCommentsNum` | `int` | 获取当前待审核的评论数目 |
| `waitingPostsNum` | `int` | 获取待审核的文章数目 |

- `my…` 系列统计当前登录用户；
- `current…Posts` 系列统计请求参数 `uid` 指定的用户，`current…Comments` 系列统计请求参数 `cid` 指定的内容，参数通过 `alloc()` 的第二个参数传入：`\Widget\Stat::alloc(null, 'cid=3')`。
