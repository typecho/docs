---
title: Your First Theme
sidebar_position: 2
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Your First Theme

Starting from an empty directory, this page writes, file by file, a minimal theme named `starter` that you can enable in the admin panel and that displays the home page, posts, pages, categories, search and 404. Every file follows the patterns of the `classic-22` theme that ships with Typecho (`usr/themes/classic-22/`); once you are done, you can compare against it and keep extending.

All PHP code in a theme runs inside a `Widget\Archive` object, and `$this` in a template is that object; see [Theme development overview](./index.md).

## Preparation

Create a directory `starter` under `usr/themes/`. The finished directory looks like this:

```text
usr/themes/starter/
├── index.php      required: theme info + post list
├── header.php     header
├── footer.php     footer
├── post.php       post page
├── page.php       page
├── comments.php   comment list and comment form
├── 404.php        page not found
├── functions.php  callbacks such as theme settings
└── style.css      styles
```

Only `index.php` is required: when any other template is missing, Typecho falls back to `index.php` for the output (for the order, see [Template hierarchy](./template-hierarchy.md)).

## index.php

`index.php` has two jobs: the comment at the top tells the admin panel that this is a theme and what it is called; the file itself outputs the post lists for the home page, categories, tags, search and so on.

```php title="usr/themes/starter/index.php"
<?php
/**
 * A minimal example theme
 *
 * @package Starter
 * @author Your Name
 * @version 1.0.0
 * @link https://example.com
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<main>
    <?php if (!$this->is('index')): ?>
        <h1><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章'),
        ], '', ''); ?></h1>
    <?php endif; ?>

    <?php if ($this->have()): ?>
        <?php while ($this->next()): ?>
            <article>
                <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
                <p class="meta">
                    <?php $this->date(); ?> ·
                    <?php $this->category(', '); ?> ·
                    <a href="<?php $this->permalink(); ?>#comments"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a>
                </p>
                <?php $this->content(_t('阅读全文')); ?>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <p><?php _e('没有找到内容'); ?></p>
    <?php endif; ?>

    <?php $this->pageNav('&laquo; ' . _t('前一页'), _t('后一页') . ' &raquo;'); ?>
</main>
<?php $this->need('footer.php'); ?>
```

Section by section:

- In the header comment, `@package` is the name the theme shows in the admin panel, and the first line of text is the description. For each field, see [Theme structure and header information](./anatomy.md).
- `if (!defined('__TYPECHO_ROOT_DIR__')) exit;` stops anyone from requesting this PHP file directly. Every template file should start with this line.
- `$this->need('header.php')` includes another file from the same theme directory; `$this` stays the same inside the included file.
- `$this->is('index')` checks whether the current page is the home page; when it is not, `archiveTitle()` outputs a heading such as "Posts in category Tech". The array keys are page types; `%s` in the values is replaced with the category name, search keyword and so on; the last two arguments are the prefix and suffix.
- `while ($this->next())` takes out the posts of the current page one by one; inside the loop, `$this->title()`, `$this->permalink()` and so on all refer to the current post. When there are no posts, `$this->have()` is false.
- `$this->content(_t('阅读全文'))` outputs the body; when the post contains `<!--more-->`, the list shows only the part before the separator, followed by a "Read more" link.
- `$this->pageNav()` outputs pagination; the number of posts per page is set by "Posts per page" under "Settings → Reading".

## header.php

```php title="usr/themes/starter/header.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $this->archiveTitle('', '', ' - '); ?><?php $this->options->title(); ?></title>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
    <?php $this->header(); ?>
</head>
<body>
<header class="site-header">
    <a class="site-title" href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>
    <nav>
        <a href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
        <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
        <?php while ($pages->next()): ?>
            <a href="<?php $pages->permalink(); ?>"><?php $pages->title(); ?></a>
        <?php endwhile; ?>
    </nav>
    <form method="post" action="<?php $this->options->siteUrl(); ?>">
        <input type="search" name="s" placeholder="<?php _e('搜索'); ?>">
    </form>
</header>
```

- `$this->options` is the site settings. `$this->options->title()` outputs the site name, `siteUrl()` outputs the site URL, and `charset()` outputs the character encoding.
- In `<title>`, `archiveTitle('', '', ' - ')` outputs "Title - " on posts, categories and similar pages; the home page has no archive title, so nothing is output.
- `$this->options->themeUrl('style.css')` outputs the full URL of a file in the theme directory; use it to load stylesheets and scripts.
- `$this->header()` must be placed inside `<head>`: it outputs the description, keywords, feed, Pingback and other tags, as well as the scripts needed for comment replies and anti-spam. You can suppress some of these items; see [Header output: header()](./header.md).
- `\Widget\Contents\Page\Rows::alloc()->to($pages)` fetches all pages into `$pages` to build the navigation menu. For other ways of fetching data in a template, see [Calling widgets in templates](./widgets.md).
- The search form submits the keyword under the name `s` to the site URL, and Typecho redirects to `/search/关键字/`.

## footer.php

```php title="usr/themes/starter/footer.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<footer class="site-footer">
    <p>
        &copy; <?php echo date('Y'); ?> <?php $this->options->title(); ?>
        · <a href="<?php $this->options->feedUrl(); ?>">RSS</a>
        <?php if ($this->options->footerText): ?>
            · <?php $this->options->footerText(); ?>
        <?php endif; ?>
    </p>
</footer>
<?php $this->footer(); ?>
</body>
</html>
```

- Place `$this->footer()` before `</body>`; plugins output analytics code and similar content here.
- `$this->options->footerText` is an option of this theme, defined in `functions.php`; see below. It is empty when the option has not been filled in.

## post.php

Posts use `post.php`:

```php title="usr/themes/starter/post.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <article>
        <h1><?php $this->title(); ?></h1>
        <p class="meta">
            <?php $this->author(); ?> · <?php $this->date(); ?> · <?php $this->category(', '); ?>
        </p>
        <?php $this->content(); ?>
        <p class="tags"><?php _e('标签'); ?>：<?php $this->tags(', ', true, _t('无')); ?></p>
    </article>

    <nav class="post-nav">
        <span><?php $this->thePrev('&laquo; %s', _t('没有了')); ?></span>
        <span><?php $this->theNext('%s &raquo;', _t('没有了')); ?></span>
    </nav>

    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
```

- On a post page `$this` is the post itself, so no `while` loop is needed.
- `$this->tags(', ', true, _t('无'))` outputs the tags as links separated by commas, and outputs "None" when there are no tags.
- `thePrev()` / `theNext()` output links to the previous and next post; `%s` is the title, and the second argument is the text shown when there is none.

## page.php

Pages use `page.php`. Its structure is the same as a post page, except that it needs no categories, tags or previous/next links:

```php title="usr/themes/starter/page.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <article>
        <h1><?php $this->title(); ?></h1>
        <?php $this->content(); ?>
    </article>

    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
```

## comments.php

```php title="usr/themes/starter/comments.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<section id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>
        <?php $comments->listComments(); ?>
        <?php $comments->pageNav('&laquo; ' . _t('前一页'), _t('后一页') . ' &raquo;'); ?>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>">
            <?php $comments->cancelReply(); ?>
            <h3><?php _e('发表评论'); ?></h3>
            <form method="post" action="<?php $this->commentUrl(); ?>">
                <?php if ($this->user->hasLogin()): ?>
                    <p><?php _e('登录身份'); ?>：<?php $this->user->screenName(); ?></p>
                <?php else: ?>
                    <p><input type="text" name="author" placeholder="<?php _e('称呼'); ?>" value="<?php $this->remember('author'); ?>" required></p>
                    <p><input type="email" name="mail" placeholder="<?php _e('邮箱'); ?>" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></p>
                    <p><input type="url" name="url" placeholder="<?php _e('网站'); ?>" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?>></p>
                <?php endif; ?>
                <p><textarea name="text" rows="5" required></textarea></p>
                <p><button type="submit"><?php _e('提交评论'); ?></button></p>
            </form>
        </div>
    <?php else: ?>
        <p><?php _e('评论已关闭'); ?></p>
    <?php endif; ?>
</section>
```

- `$this->comments()->to($comments)` fetches the approved comments of this item, and `$comments->listComments()` outputs the list according to the threading, pagination and ordering settings under "Settings → Comments".
- The `id` of the element wrapping the form must be `$this->respondId()`, and the form field names must be `author`, `mail`, `url` and `text`. When a visitor clicks "Reply" under a comment, the script output by `header()` moves this element below the comment being replied to.
- `$this->remember('author')` and its siblings fill in the name, email and website the visitor used for their last comment.

For the complete way of writing a comment template, see [Comment templates](./comments.md).

## 404.php

`404.php` is used when a URL that does not exist is requested, with HTTP status code 404:

```php title="usr/themes/starter/404.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <h1>404</h1>
    <p><?php _e('页面没找到'); ?>。<a href="<?php $this->options->siteUrl(); ?>"><?php _e('返回首页'); ?></a></p>
</main>
<?php $this->need('footer.php'); ?>
```

## functions.php

`functions.php` is not a template but a function file loaded before the page is output; theme options, custom fields, comment callbacks and so on all go here. This example defines only one theme option, "Footer text":

```php title="usr/themes/starter/functions.php"
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $footerText = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerText',
        null,
        null,
        _t('页脚文字'),
        _t('显示在每个页面底部，可以留空')
    );
    $form->addInput($footerText);
}
```

With `themeConfig()` in place, a "Theme settings" tab appears at the top of "Dashboard → Appearance" in the admin panel, and the saved value is read in templates with `$this->options->footerText`. For details, see [Theme options](./theme-options.md) and [functions.php callbacks](./functions-php.md).

## style.css

Style it however you like; here are just a few lines:

```css title="usr/themes/starter/style.css"
body { max-width: 720px; margin: 0 auto; padding: 0 16px; font-family: sans-serif; line-height: 1.7; }
.site-header, .site-footer { display: flex; gap: 1em; align-items: center; flex-wrap: wrap; padding: 1em 0; }
.site-title { font-weight: bold; }
.meta, .tags { color: #888; font-size: .9em; }
.post-nav { display: flex; justify-content: space-between; margin: 2em 0; }
```

## Enable and check

1. Go to "Dashboard → Appearance" in the admin panel; "Starter" appears in the list. Click "Activate";
2. Open the following pages one by one and confirm that they all display correctly:

| Page | Example URL | Template used |
|---|---|---|
| Home page | `/` | `index.php` |
| Post | `/archives/1/` | `post.php` |
| Page | `/start-page.html` | `page.php` |
| Category | `/category/default/` | `index.php` |
| Search | Type a keyword into the search box in the header | `index.php` |
| Nonexistent URL | `/no-such-page/` | `404.php` |

3. Go to "Theme settings", fill in "Footer text" and save; refresh the front end and the text appears in the footer.

When a template fails, the page may be blank or return 500; turn on debug mode to see the actual error. See [Debugging and troubleshooting](../../guide/operations/debugging.md#debug).

## Next steps

- To build different list pages for categories, tags and search, see [Template hierarchy](./template-hierarchy.md);
- To determine which kind of page you are on, see [Page context and is()](./context.md);
- To build a dedicated template for a page, see [Custom templates](./custom-templates.md);
- For the latest posts, category list and so on in a sidebar, see [Calling widgets in templates](./widgets.md).
