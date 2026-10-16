---
title: Custom Templates
sidebar_position: 7
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Custom Templates

Besides the templates selected automatically by the [template hierarchy](./template-hierarchy.md), a theme can provide several kinds of "custom" templates that site owners choose in the admin panel.

## Page templates: `@package custom`

These suit pages that need a special layout, such as "Archives", "Links" or "Guestbook". Create a PHP file in the theme directory and write `@package custom` in its header comment; the first line of the comment is the name the template shows in the admin panel:

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

- The file name is up to you (except `index.php`); put it in the theme root directory.
- When editing a page, "友情链接" appears in the "Custom template" drop-down on the right; once selected, the page is output with `page-links.php` instead. For what the site owner does, see [Pages: custom templates](../../guide/content/pages.md#template).
- `$this` in the template is the page, so you can call `title()`, `content()`, `comments()` and so on just as in `page.php`.
- When the site switches to a theme that does not have this file, the page is automatically output by the normal rules (`page.php` and so on).

An example "Archives" page template that lists all posts by month:

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

## Home page templates: `@package index` {#home-page-template}

A file whose comment contains `@package index` appears in the "use the [file] template file directly" option under "Settings → Reading → Front page":

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

When the site owner selects it, the option is saved as `file:home.php` and the home page outputs this file directly. Note:

- In this case `$this` on the home page **contains no posts**, and `$this->next()` fetches nothing; you have to fetch the data you need yourself with widgets (like the latest posts above);
- In 1.3.0 this kind of home page skips the initialization of the feed URLs and similar values, so as soon as the template calls `$this->header()` it fails with `Typed property Widget\Archive::$archiveFeedUrl must not be accessed before initialization`. The four `setArchive…()` lines at the top above fill them in for that reason; they cannot be omitted when `header.php` calls `header()`;
- In 1.3.0 the pagination URLs of this kind of home page (`/page/2/`) return a 500 error, so do not output pagination links in the template;
- When the theme is switched, this setting automatically reverts to "show the latest posts".

For how the site owner sets this up, see [Reading settings: front page](../../guide/settings/reading.md#front-page).

## A separate template for one category or page

No declaration is needed; just place the file according to the rules of the [template hierarchy](./template-hierarchy.md):

| You want | File |
|---|---|
| A separate list style for category `tech` | `category/tech.php` |
| One template shared by all categories | `category.php` |
| The page with slug `about` | `page/about.php` |
| The post with ID 12 | `post/12.php` |

The difference: a `@package custom` template is chosen for a page by the site owner in the admin panel and suits a reusable "page style"; a file such as `page/about.php` is bound by slug, stops working once the site owner changes the page slug, and suits fixed pages that come with the theme.

## Changing the number of posts per page in themeInit {#page-size}

"Posts per page" under "Settings → Reading" applies to all list pages. To make one kind of page different, change `pageSize` in `themeInit()` in `functions.php`:

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

`themeInit()` is called before the list query runs, so the change here takes effect on the current page, and pagination is also calculated with the new count. It works for the home page, categories, tags, authors, date archives and search.
