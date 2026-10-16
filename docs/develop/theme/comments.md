---
title: 评论模板
sidebar_position: 10
---

# 评论模板

评论通常放在单独的 `comments.php` 中，由 `post.php` 和 `page.php` 用 `$this->need('comments.php')` 引入。它包括两部分：已有评论的列表，以及发表评论的表单。

## 完整示例

```php title="usr/themes/starter/comments.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<section id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>
        <?php $comments->listComments([
            'avatarSize'    => 48,
            'defaultAvatar' => 'identicon',
            'dateFormat'    => 'Y-m-d H:i',
        ]); ?>
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

## 评论列表：comments() 与 listComments()

`$this->comments()` 返回当前文章或页面的评论列表（`Widget\Comments\Archive`），用 `->to($comments)` 放进变量：

- `$comments->have()`：有没有评论；
- `$comments->listComments($options)`：按“设置 → 评论”中的嵌套、分页、排序设置，输出整个评论列表；
- `$comments->pageNav()`：评论分页导航，只在开启了评论分页时有内容；
- `$this->commentsNum('0 条', '1 条', '%d 条')`：输出评论数，三个参数分别用于 0 条、1 条和多条。

列表中包括**普通评论和引用**（Trackback、Pingback）：引用的 `type` 分别为 `trackback`、`pingback`，普通评论为 `comment`。站长在“设置 → 评论”中勾选了“仅显示评论, 不显示 Pingback 和 Trackback”时，列表中只有普通评论。只显示已通过的评论；访客刚提交、还在等待审核的那一条也会显示给他本人，带有“您的评论正等待审核!”的提示。

### listComments() 的选项

可以传数组，也可以传 `'avatarSize=48&dateFormat=Y-m-d'` 这样的查询字符串：

| 键 | 默认值 | 作用 |
|---|---|---|
| `before` | `<ol class="comment-list">` | 列表开始的 HTML；回复列表也使用它 |
| `after` | `</ol>` | 列表结束的 HTML |
| `beforeAuthor` / `afterAuthor` | 空 | 评论者名字前后的 HTML |
| `beforeDate` / `afterDate` | 空 | 日期前后的 HTML |
| `dateFormat` | 评论设置中的“评论日期格式” | 日期格式，PHP `date()` 写法 |
| `replyWord` | `回复` | 回复链接的文字 |
| `commentStatus` | `您的评论正等待审核!` | 待审核评论的提示 |
| `avatarSize` | `32` | 头像尺寸（像素） |
| `defaultAvatar` | `null` | 邮箱没有 Gravatar 头像时的默认图，例如 `mm`、`identicon`、`retro` |
| `avatarHighRes` | `false` | 为 `true` 时头像额外输出 2 倍、3 倍尺寸的 `srcset` |

默认输出中每条评论是一个 `<li id="comment-评论ID" class="comment-body ...">`，class 中包含 `comment-parent` / `comment-child`、`comment-odd` / `comment-even`、`comment-by-author`（文章作者的评论）、`comment-by-user`（其他登录用户的评论）等，可以直接用来写样式。

## 自定义每条评论：threadedComments 回调 {#callback}

需要完全控制评论的 HTML 时，在 `functions.php` 中定义 `threadedComments()`，`listComments()` 就会对每条评论调用它：

```php title="usr/themes/starter/functions.php"
function threadedComments($comments, $options)
{
    ?>
    <li id="<?php $comments->theId(); ?>" class="comment <?php $comments->levelsAlt('odd', 'even'); ?>">
        <?php $comments->gravatar($options->avatarSize, $options->defaultAvatar); ?>
        <cite><?php $comments->author(); ?></cite>
        <time><?php $comments->date($options->dateFormat); ?></time>
        <?php if ('approved' !== $comments->status): ?>
            <em><?php $options->commentStatus(); ?></em>
        <?php endif; ?>
        <?php $comments->content(); ?>
        <?php $comments->reply($options->replyWord); ?>
        <?php if ($comments->children): ?>
            <?php $comments->threadedComments(); ?>
        <?php endif; ?>
    </li>
    <?php
}
```

- `$comments` 是当前这条评论，`$options` 是 `listComments()` 的选项，用 `$options->avatarSize` 取值、`$options->commentStatus()` 输出。
- 外层元素的 `id` 必须是 `$comments->theId()`（形如 `comment-12`），回复功能靠它定位。
- 有回复时调用 `$comments->threadedComments()`，它会用 `before` / `after` 包裹，并对每条回复再次调用你的回调。
- 常用方法：`author($autoLink, $noFollow)` 输出评论者（默认按评论设置决定是否链接到其网址）、`gravatar($size, $default)` 输出头像（只对普通评论输出）、`date($format)`、`content()`、`excerpt($length)`、`permalink()`；`$comments->type`、`$comments->status`、`$comments->levels`（嵌套深度，从 0 开始）可以用来判断。

## 回复与取消回复

- `$comments->reply('回复')`：输出“回复”链接。只在开启了“启用评论回复”、评论未达到最大回复层数、文章允许评论时输出。
- `$comments->cancelReply('取消回复')`：输出“取消回复”链接，平时隐藏，点击“回复”后出现。

点击“回复”时，`$this->header()` 输出的脚本会把 `id` 为 `$this->respondId()` 的元素（评论表单的外层）移动到被回复的评论下面，并在表单中加入名为 `parent` 的隐藏字段。所以：

- `header.php` 中必须调用 `$this->header()`，并且不能屏蔽 `commentReply`（见[头部输出 header()](./header.md)）；
- 表单外层元素的 `id` 必须是 `$this->respondId()`；
- 浏览器禁用 JavaScript 时，“回复”链接会带上 `?replyTo=评论ID` 打开页面，`$this->commentUrl()` 随之带上父评论参数，同样能回复。

## 评论表单

- `action` 用 `$this->commentUrl()`，方法为 `post`；
- 字段名固定为 `author`（称呼）、`mail`（邮箱）、`url`（网站）、`text`（内容）；已登录用户只需要 `text`；
- 是否必填按站长的评论设置：`$this->options->commentsRequireMail`、`$this->options->commentsRequireUrl`；
- `$this->remember('author')`、`remember('mail')`、`remember('url')` 输出访客上次评论时填写的值（保存在 Cookie 中）。只支持这三个字段，`remember('text')` 不输出任何内容；
- 站长开启了“开启反垃圾保护”时，表单还需要 `header()` 输出的脚本在访客与页面交互后加入一个校验字段，所以同样依赖 `$this->header()`；
- `$this->allow('comment')` 为假（文章关闭了评论、超过了自动关闭期限、或者是未解锁的密码保护文章）时不要输出表单。

## 单独列出引用 {#pings}

`Widget\Archive` 有一个 `pings()` 方法，本应返回只含引用（Trackback、Pingback）的列表。但 **1.3.0 中调用 `$this->pings()` 会导致致命错误**：`Widget\Comments\Ping` 类的 `___parentContent()` 返回类型与父类不兼容，PHP 加载这个类时就会报错，页面在调用处中断。所以不要使用 `pings()` 和 `listPings()`，`functions.php` 中的 `singlePing` 回调也不会生效。

需要把引用和评论分开显示时，从 `comments()` 中按 `type` 筛选：在 `threadedComments` 回调的开头跳过引用，再单独循环一遍输出引用。

```php title="usr/themes/starter/functions.php"
function threadedComments($comments, $options)
{
    if ('comment' !== $comments->type) {
        return;
    }
    ?>
    <li id="<?php $comments->theId(); ?>">
        <?php $comments->gravatar($options->avatarSize, $options->defaultAvatar); ?>
        <cite><?php $comments->author(); ?></cite>
        <?php $comments->content(); ?>
        <?php $comments->reply($options->replyWord); ?>
        <?php if ($comments->children): ?>
            <?php $comments->threadedComments(); ?>
        <?php endif; ?>
    </li>
    <?php
}
```

```php
<?php $this->comments()->to($comments); ?>
<?php $comments->listComments(); ?>

<h3><?php _e('引用'); ?></h3>
<ul class="ping-list">
    <?php while ($comments->next()): ?>
        <?php if ('comment' !== $comments->type): ?>
            <li><?php $comments->author(); ?>：<?php $comments->excerpt(60); ?></li>
        <?php endif; ?>
    <?php endwhile; ?>
</ul>
```

`$comments->next()` 循环到末尾后会自动回到开头，所以 `listComments()` 前后都可以再循环一遍。注意 `commentsNum()` 和评论分页的计数中都包括引用。
