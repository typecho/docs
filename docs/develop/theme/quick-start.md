---
title: 第一个主题
sidebar_position: 2
---

# 第一个主题

本页从一个空目录开始，逐个文件编写一个名为 `starter` 的最小主题。它可以在后台启用，能显示首页、文章、页面、分类、搜索和 404。每个文件的写法都参照随 Typecho 发布的 `classic-22` 主题（`usr/themes/classic-22/`），写完以后可以对照它继续扩展。

主题中的 PHP 代码都运行在 `Widget\Archive` 对象里，模板中的 `$this` 就是它，见[主题开发概览](./index.md)。

## 准备

在 `usr/themes/` 下新建目录 `starter`。完成后的目录是：

```text
usr/themes/starter/
├── index.php      必需：主题信息 + 文章列表
├── header.php     页头
├── footer.php     页脚
├── post.php       文章页
├── page.php       独立页面
├── comments.php   评论列表与评论表单
├── 404.php        找不到页面
├── functions.php  主题设置等回调
└── style.css      样式
```

只有 `index.php` 是必需的：其他模板缺少时，Typecho 会退回用 `index.php` 输出（顺序见[模板层级](./template-hierarchy.md)）。

## index.php

`index.php` 有两个作用：开头的注释告诉后台这是一个主题、叫什么名字；文件本身负责输出首页、分类、标签、搜索等文章列表。

```php title="usr/themes/starter/index.php"
<?php
/**
 * 一个最小的示例主题
 *
 * @package Starter
 * @author 你的名字
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

逐段说明：

- 头注释中的 `@package` 是主题在后台显示的名称，第一行文字是描述。各字段见[主题结构与头部信息](./anatomy.md)。
- `if (!defined('__TYPECHO_ROOT_DIR__')) exit;` 防止有人直接访问这个 PHP 文件。每个模板文件开头都应该有这一行。
- `$this->need('header.php')` 引入同一主题目录下的另一个文件，`$this` 在被引入的文件里不变。
- `$this->is('index')` 判断当前是不是首页；不是首页时，用 `archiveTitle()` 输出“分类 技术 下的文章”这样的标题。数组的键是页面类型，值中的 `%s` 换成分类名、关键字等，后两个参数是前后缀。
- `while ($this->next())` 逐篇取出当前页的文章，循环里的 `$this->title()`、`$this->permalink()` 等都指向当前这一篇。没有文章时 `$this->have()` 为假。
- `$this->content(_t('阅读全文'))` 输出正文；文章中有 `<!--more-->` 时，列表中只输出分割线之前的部分，后面加一个“阅读全文”链接。
- `$this->pageNav()` 输出分页导航，每页篇数由“设置 → 阅读”中的“每页文章数目”决定。

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

- `$this->options` 是站点设置。`$this->options->title()` 输出站点名称，`siteUrl()` 输出站点地址，`charset()` 输出编码。
- `<title>` 中的 `archiveTitle('', '', ' - ')` 在文章、分类等页面输出“标题 - ”，首页没有归档标题，什么都不输出。
- `$this->options->themeUrl('style.css')` 输出主题目录中文件的完整地址，用来加载样式和脚本。
- `$this->header()` 必须放在 `<head>` 里：它输出描述、关键词、订阅源、Pingback 等标签，以及评论回复和反垃圾所需的脚本。可以屏蔽其中某些项，见[头部输出 header()](./header.md)。
- `\Widget\Contents\Page\Rows::alloc()->to($pages)` 取出所有独立页面放进 `$pages`，用来生成导航菜单。在模板中取其他数据的方法见[在模板中调用 Widget](./widgets.md)。
- 搜索表单把关键字以 `s` 为名提交到站点地址，Typecho 会跳转到 `/search/关键字/`。

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

- `$this->footer()` 放在 `</body>` 之前，插件会在这里输出统计代码等内容。
- `$this->options->footerText` 是本主题自己的设置项，在 `functions.php` 中定义，见下文。设置项没有填写时为空。

## post.php

文章页使用 `post.php`：

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

- 文章页的 `$this` 就是这一篇文章，不需要 `while` 循环。
- `$this->tags(', ', true, _t('无'))` 输出带链接的标签，用逗号分隔，没有标签时输出“无”。
- `thePrev()` / `theNext()` 输出上一篇、下一篇的链接，`%s` 是标题，第二个参数是没有时的文字。

## page.php

独立页面使用 `page.php`，结构与文章页相同，只是不需要分类、标签和上下篇：

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

- `$this->comments()->to($comments)` 取出这篇内容已通过的评论，`$comments->listComments()` 按“设置 → 评论”中的嵌套、分页、排序设置输出列表。
- 表单外层元素的 `id` 必须是 `$this->respondId()`，表单中的字段名必须是 `author`、`mail`、`url`、`text`。点击评论下方的“回复”时，`header()` 输出的脚本会把这个元素移动到被回复的评论下面。
- `$this->remember('author')` 等填入访客上次评论时使用的称呼、邮箱和网址。

评论模板的完整写法见[评论模板](./comments.md)。

## 404.php

访问不存在的地址时使用 `404.php`，HTTP 状态码为 404：

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

`functions.php` 不是模板，而是在输出页面之前加载的函数文件，主题设置、自定义字段、评论回调等都写在这里。这里只定义一个主题设置项“页脚文字”：

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

有了 `themeConfig()`，后台“控制台 → 外观”上方就会出现“设置外观”标签，保存的值在模板中用 `$this->options->footerText` 读取。详见[主题设置](./theme-options.md)和 [functions.php 回调](./functions-php.md)。

## style.css

样式随意，这里只放几行：

```css title="usr/themes/starter/style.css"
body { max-width: 720px; margin: 0 auto; padding: 0 16px; font-family: sans-serif; line-height: 1.7; }
.site-header, .site-footer { display: flex; gap: 1em; align-items: center; flex-wrap: wrap; padding: 1em 0; }
.site-title { font-weight: bold; }
.meta, .tags { color: #888; font-size: .9em; }
.post-nav { display: flex; justify-content: space-between; margin: 2em 0; }
```

## 启用并检查

1. 到后台“控制台 → 外观”，列表中出现“Starter”，点击“启用”；
2. 依次打开下面的页面，确认都能正常显示：

| 页面 | 地址示例 | 使用的模板 |
|---|---|---|
| 首页 | `/` | `index.php` |
| 文章 | `/archives/1/` | `post.php` |
| 独立页面 | `/start-page.html` | `page.php` |
| 分类 | `/category/default/` | `index.php` |
| 搜索 | 在页头搜索框中输入关键字 | `index.php` |
| 不存在的地址 | `/no-such-page/` | `404.php` |

3. 到“设置外观”填写“页脚文字”并保存，刷新前台，页脚出现这段文字。

模板出错时页面可能是空白或 500，打开调试模式可以看到具体错误，见[调试与排错](../../guide/operations/debugging.md#debug)。

## 下一步

- 想给分类、标签、搜索分别做不同的列表页，见[模板层级](./template-hierarchy.md)；
- 判断当前在哪种页面，见[页面上下文与 is()](./context.md)；
- 给独立页面做专用模板，见[自定义模板](./custom-templates.md)；
- 侧边栏的最新文章、分类列表等，见[在模板中调用 Widget](./widgets.md)。
