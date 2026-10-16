---
title: 页面上下文与 is()
sidebar_position: 5
---

# 页面上下文与 is()

同一个模板文件（尤其是 `header.php`、`sidebar.php`、`index.php`）会被很多种页面使用。`$this->is()` 用来判断当前是哪一种页面。

## 用法

```text
$this->is(string $archiveType, ?string $archiveSlug = null): bool
```

- 只给第一个参数时，判断页面类型；
- 给第二个参数时，同时要求缩略名相等，例如 `$this->is('category', 'tech')` 只在分类 `tech` 的页面为真。

```php
<?php if ($this->is('index')): ?>
    <p>首页</p>
<?php elseif ($this->is('post')): ?>
    <p>文章页</p>
<?php elseif ($this->is('page', 'about')): ?>
    <p>“关于”页面</p>
<?php elseif ($this->is('category')): ?>
    <p>某个分类</p>
<?php endif; ?>
```

## 可以判断的类型

| 参数 | 为真的页面 | 可以配合的缩略名 |
|---|---|---|
| `index` | 首页（含分页 `/page/2/`）；以独立页面作为首页时也为真 | — |
| `post` | 文章页 | 文章 ID |
| `page` | 独立页面 | 页面缩略名 |
| `attachment` | 附件页面 | 附件 ID |
| `category` | 分类页 | 分类缩略名 |
| `tag` | 标签页 | 标签缩略名 |
| `author` | 作者页 | 用户 ID |
| `date` | 日期归档 | `year`、`month`、`day` |
| `search` | 搜索结果页 | 关键字 |
| `single` | **伪类型**：文章、独立页面、附件 | 同上 |
| `archive` | **伪类型**：除首页外的所有列表页（分类、标签、作者、日期、搜索）以及 404 页 | 404 页的缩略名为 `404` |
| `feed` | **伪类型**：由订阅源调用时为真 | — |

`single`、`archive`、`feed` 不是页面本身的类型，而是按情况归类：

- `single` 与 `archive` 互斥：一个页面要么是单篇内容，要么是列表；首页两者都不是。
- 404 页面的类型就是 `archive`，缩略名是 `404`，所以判断 404 用 `$this->is('archive', '404')`。
- 订阅源（`/feed/` 等）不经过主题模板，`is('feed')` 主要供插件在 `Widget\Archive` 被订阅源调用时使用，在模板中通常用不到。

以独立页面作为首页时，同时满足 `is('index')`、`is('page')` 和 `is('single')`。

## 页面类型和缩略名的取值

在模板中可以直接读取：

```php
<?php echo $this->getArchiveType(); ?>
<?php echo $this->getArchiveTitle(); ?>
```

| 方法 | 返回 |
|---|---|
| `getArchiveType()` | 页面类型，取值同上表第一列（不含伪类型） |
| `getArchiveSlug()` | 缩略名 |
| `getArchiveTitle()` | 归档标题：文章 / 页面标题、分类名、搜索关键字等，首页为 `null` |
| `getArchiveDescription()` | 归档描述：分类描述、文章摘要，首页和搜索页为站点描述 |
| `getArchiveUrl()` | 当前页面的地址 |

:::caution 首页没有缩略名
1.3.0 中，首页（`index`）的缩略名没有初始化。在首页上调用 `$this->getArchiveSlug()`，或者调用带第二个参数的 `$this->is('index', ...)`，会抛出 `Typed property Widget\Archive::$archiveSlug must not be accessed before initialization` 错误，页面变成 500。判断首页只用 `$this->is('index')`；需要读缩略名的代码，先用 `!$this->is('index')` 排除首页。
:::

其他类型的页面都有缩略名，`is('category', 'tech')` 这类判断在首页上也安全：类型不符时不会读取缩略名。

## 常见写法

在导航菜单中标出当前页面：

```php
<?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
<?php while ($pages->next()): ?>
    <a href="<?php $pages->permalink(); ?>"<?php if ($this->is('page', $pages->slug)): ?> class="active"<?php endif; ?>><?php $pages->title(); ?></a>
<?php endwhile; ?>
```

只在列表页显示归档标题：

```php
<?php if ($this->is('archive')): ?>
    <h1><?php $this->archiveTitle('', '', ''); ?></h1>
<?php endif; ?>
```

只在文章和页面加载评论脚本：

```php
<?php if ($this->is('single')): ?>
    <script src="<?php $this->options->themeUrl('comments.js'); ?>"></script>
<?php endif; ?>
```
