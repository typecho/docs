---
title: 自定义模板
sidebar_position: 7
---

# 自定义模板

除了按[模板层级](./template-hierarchy.md)自动选择的模板，主题还可以提供几种“自定义”模板，由站长在后台选用。

## 独立页面模板：`@package custom`

适合“归档”“友情链接”“留言板”这类需要特殊排版的页面。在主题目录中新建一个 PHP 文件，开头的注释写 `@package custom`，注释的第一行是模板在后台显示的名称：

```php title="usr/themes/starter/page-links.php"
<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<main>
    <article>
        <h1><?php $this->title(); ?></h1>
        <?php $this->content(); ?>
    </article>
</main>
<?php $this->need('footer.php'); ?>
```

- 文件名可以随意（`index.php` 除外），放在主题根目录。
- 编辑独立页面时，右侧“自定义模板”下拉框中会出现“友情链接”；选中后这个页面改用 `page-links.php` 输出。站长的操作见[独立页面：自定义模板](../../guide/content/pages.md#template)。
- 模板中的 `$this` 就是这个页面，可以和 `page.php` 一样调用 `title()`、`content()`、`comments()` 等。
- 换成没有这个文件的主题时，页面自动按 `page.php` 等普通规则输出。

一个“文章归档”页面模板的例子，按月列出所有文章：

```php title="usr/themes/starter/page-archives.php"
<?php
/**
 * 文章归档
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<main>
    <h1><?php $this->title(); ?></h1>
    <?php \Widget\Contents\Post\Recent::allocWithAlias('archives', 'pageSize=10000')->to($archives); ?>
    <?php $month = null; ?>
    <?php while ($archives->next()): ?>
        <?php if ($month !== $archives->date->format('Y-m')): ?>
            <?php if (null !== $month): ?></ul><?php endif; ?>
            <?php $month = $archives->date->format('Y-m'); ?>
            <h2><?php echo $archives->date->format('Y 年 n 月'); ?></h2>
            <ul>
        <?php endif; ?>
        <li><a href="<?php $archives->permalink(); ?>"><?php $archives->title(); ?></a></li>
    <?php endwhile; ?>
    <?php if (null !== $month): ?></ul><?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>
```

## 首页模板：`@package index`

注释中写 `@package index` 的文件，会出现在“设置 → 阅读 → 站点首页”的“直接调用 [文件] 模板文件”选项中：

```php title="usr/themes/starter/home.php"
<?php
/**
 * 自定义首页
 *
 * @package index
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
// 1.3.0 中这类首页没有初始化订阅地址，先设置好，否则 header() 会报错
$this->setArchiveFeedUrl($this->options->feedUrl);
$this->setArchiveFeedRssUrl($this->options->feedRssUrl);
$this->setArchiveFeedAtomUrl($this->options->feedAtomUrl);
$this->setArchiveUrl($this->options->siteUrl);
$this->need('header.php');
?>
<main>
    <h1><?php $this->options->title(); ?></h1>
    <p><?php $this->options->description(); ?></p>
    <h2><?php _e('最新文章'); ?></h2>
    <ul>
        <?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
    </ul>
</main>
<?php $this->need('footer.php'); ?>
```

站长选中后，选项保存为 `file:home.php`，首页直接输出这个文件。注意：

- 这时首页的 `$this` 中**没有文章**，`$this->next()` 不会取到任何内容，需要的数据要自己用 Widget 取（如上面的最新文章）；
- 1.3.0 中这种首页跳过了订阅地址等的初始化，模板一旦调用 `$this->header()` 就会报错 `Typed property Widget\Archive::$archiveFeedUrl must not be accessed before initialization`。示例开头的四行 `setArchive…()` 用来补上这些初始化；只要 `header.php` 中调用了 `header()`，就不能省略它们；
- 这种首页不加载 `functions.php`，也不调用 `themeInit()`。模板或它引入的 `header.php` 等文件用到 `functions.php` 中的函数时，在模板开头自己加载：`if (is_file(__DIR__ . '/functions.php')) require_once __DIR__ . '/functions.php';`。这样只加载函数定义；如果需要 `themeInit()` 中的初始化，还要自己调用 `themeInit($this)`；
- 1.3.0 中这种首页的分页地址（`/page/2/`）会报 500 错误，模板中不要输出分页链接；
- 切换主题时，这个设置会自动恢复为“显示最新发布的文章”。

站长的设置方法见[阅读设置：站点首页](../../guide/settings/reading.md#front-page)。

## 为某个分类或页面单独做模板

不需要任何声明，按[模板层级](./template-hierarchy.md)的规则放文件即可：

| 想要 | 文件 |
|---|---|
| 分类 `tech` 用单独的列表样式 | `category/tech.php` |
| 所有分类共用一个模板 | `category.php` |
| 缩略名为 `about` 的页面 | `page/about.php` |
| ID 为 12 的文章 | `post/12.php` |

区别在于：`@package custom` 模板由站长在后台为页面挑选，适合做成可复用的“页面样式”；`page/about.php` 这种按缩略名绑定，站长改了页面缩略名就会失效，适合主题自带的固定页面。

## 在 themeInit 中修改每页篇数 {#page-size}

“设置 → 阅读”中的“每页文章数目”对所有列表页生效。想让某类页面不同，在 `functions.php` 的 `themeInit()` 中修改 `pageSize`：

```php
function themeInit($archive)
{
    if ($archive->is('category', 'tech')) {
        $archive->parameter->pageSize = 2;
    } elseif ($archive->is('search')) {
        $archive->parameter->pageSize = 20;
    }
}
```

`themeInit()` 在列表查询执行之前调用，所以这里的修改对当前页生效，分页导航也会按新的篇数计算。它对首页、分类、标签、作者、日期归档、搜索都可以使用。
