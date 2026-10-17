---
title: Comment Templates
sidebar_position: 10
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Comment Templates

Comments usually live in a separate `comments.php`, which `post.php` and `page.php` include with `$this->need('comments.php')`. It has two parts: the list of existing comments, and the form for posting a comment.

## Complete example

```php title="usr/themes/starter/comments.php"
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<section id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
        <h2><?php $this->commentsNum(_t('No comments yet'), _t('1 comment'), _t('%d comments')); ?></h2>
        <?php $comments->listComments([
            'avatarSize'    => 48,
            'defaultAvatar' => 'identicon',
            'dateFormat'    => 'Y-m-d H:i',
        ]); ?>
        <?php $comments->pageNav('&laquo; ' . _t('Previous'), _t('Next') . ' &raquo;'); ?>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div id="<?php $this->respondId(); ?>">
            <?php $comments->cancelReply(); ?>
            <h3><?php _e('Leave a comment'); ?></h3>
            <form method="post" action="<?php $this->commentUrl(); ?>">
                <?php if ($this->user->hasLogin()): ?>
                    <p><?php _e('Logged in as'); ?>: <?php $this->user->screenName(); ?></p>
                <?php else: ?>
                    <p><input type="text" name="author" placeholder="<?php _e('Name'); ?>" value="<?php $this->remember('author'); ?>" required></p>
                    <p><input type="email" name="mail" placeholder="<?php _e('Email'); ?>" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></p>
                    <p><input type="url" name="url" placeholder="<?php _e('Website'); ?>" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?>></p>
                <?php endif; ?>
                <p><textarea name="text" rows="5" required></textarea></p>
                <p><button type="submit"><?php _e('Submit comment'); ?></button></p>
            </form>
        </div>
    <?php else: ?>
        <p><?php _e('Comments are closed'); ?></p>
    <?php endif; ?>
</section>
```

## The comment list: comments() and listComments()

`$this->comments()` returns the comment list of the current post or page (`Widget\Comments\Archive`); put it into a variable with `->to($comments)`:

- `$comments->have()`: whether there are any comments;
- `$comments->listComments($options)`: outputs the whole comment list according to the threading, pagination and ordering settings under "Settings → Comments";
- `$comments->pageNav()`: comment pagination; it only has content when comment pagination is enabled;
- `$this->commentsNum('0 comments', '1 comment', '%d comments')`: outputs the comment count; the three arguments are used for 0, 1 and more comments respectively.

The list includes **ordinary comments and trackbacks/pingbacks** (Trackback, Pingback): their `type` is `trackback` or `pingback` respectively, and `comment` for ordinary comments. When the site owner has checked "Show only comments, not Pingbacks and Trackbacks" under "Settings → Comments", the list contains only ordinary comments. Only approved comments are shown; a comment a visitor has just submitted and that is still awaiting moderation is also shown to that visitor, with the notice "Your comment is awaiting moderation!".

### Options of listComments()

You can pass an array, or a query string such as `'avatarSize=48&dateFormat=Y-m-d'`:

| Key | Default | Effect |
|---|---|---|
| `before` | `<ol class="comment-list">` | HTML at the start of the list; reply lists use it too |
| `after` | `</ol>` | HTML at the end of the list |
| `beforeAuthor` / `afterAuthor` | empty | HTML before and after the commenter's name |
| `beforeDate` / `afterDate` | empty | HTML before and after the date |
| `dateFormat` | "Comment date format" in the comment settings | The date format, in PHP `date()` syntax |
| `replyWord` | "Reply" (Chinese source string, translated by the language pack) | The text of the reply link |
| `commentStatus` | "Your comment is awaiting moderation!" (Chinese source string, translated by the language pack) | The notice for comments awaiting moderation |
| `avatarSize` | `32` | Avatar size (pixels) |
| `defaultAvatar` | `null` | The default image when the email address has no Gravatar, for example `mm`, `identicon`, `retro` |
| `avatarHighRes` | `false` | When `true`, the avatar also outputs a `srcset` with 2x and 3x sizes |

In the default output each comment is an `<li id="comment-<comment ID>" class="comment-body ...">` (`comment-` followed by the comment ID); the class includes `comment-parent` / `comment-child`, `comment-odd` / `comment-even`, `comment-by-author` (comments by the post author), `comment-by-user` (comments by other logged-in users) and so on, which you can use directly for styling.

## Customizing each comment: the threadedComments callback {#callback}

When you need full control over the comment HTML, define `threadedComments()` in `functions.php`, and `listComments()` calls it for each comment:

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

- `$comments` is the current comment and `$options` is the options of `listComments()`; read a value with `$options->avatarSize` and output one with `$options->commentStatus()`.
- The `id` of the outer element must be `$comments->theId()` (of the form `comment-12`); the reply feature relies on it to locate the comment.
- When there are replies, call `$comments->threadedComments()`; it wraps them with `before` / `after` and calls your callback again for each reply.
- Common methods: `author($autoLink, $noFollow)` outputs the commenter (by default whether it links to their website follows the comment settings), `gravatar($size, $default)` outputs the avatar (only for ordinary comments), `date($format)`, `content()`, `excerpt($length)`, `permalink()`; `$comments->type`, `$comments->status` and `$comments->levels` (nesting depth, starting at 0) can be used in conditions.

## Reply and cancel reply

- `$comments->reply('Reply')`: outputs the "Reply" link. It is output only when "Enable comment replies" is on, the comment has not reached the maximum reply depth, and the post allows comments.
- `$comments->cancelReply('Cancel reply')`: outputs the "Cancel reply" link, which is normally hidden and appears after "Reply" is clicked.

When "Reply" is clicked, the script output by `$this->header()` moves the element whose `id` is `$this->respondId()` (the wrapper of the comment form) below the comment being replied to, and adds a hidden field named `parent` to the form. Therefore:

- `header.php` must call `$this->header()`, and must not suppress `commentReply` (see [Header output: header()](./header.md));
- The `id` of the form's wrapper element must be `$this->respondId()`;
- When JavaScript is disabled in the browser, the "Reply" link opens the page with `?replyTo=<comment ID>`, and `$this->commentUrl()` then carries the parent comment parameter, so the reply target is not lost. However, when the site owner has turned on "Enable anti-spam protection" (on by default after installation), the form lacks the verification field that only the script can add and the submission is sent back, so without JavaScript comments still cannot be submitted; see "The comment form" below.

## The comment form

- Use `$this->commentUrl()` for `action`, with the method `post`;
- The field names are fixed: `author` (name), `mail` (email), `url` (website), `text` (content); logged-in users only need `text`;
- Whether a field is required follows the site owner's comment settings: `$this->options->commentsRequireMail`, `$this->options->commentsRequireUrl`;
- `$this->remember('author')`, `remember('mail')` and `remember('url')` output the values the visitor entered for their last comment (stored in a cookie). Only these three fields are supported; `remember('text')` outputs nothing;
- When the site owner has turned on "Enable anti-spam protection", the form also needs the script output by `header()` to add a verification field once the visitor has interacted with the page, so it depends on `$this->header()` as well;
- Do not output the form when `$this->allow('comment')` is false (comments are closed on the post, the automatic closing period has passed, or it is a password-protected post that has not been unlocked).

## Listing trackbacks separately {#pings}

`Widget\Archive` has a `pings()` method that is meant to return a list containing only trackbacks and pingbacks (Trackback, Pingback). But **in 1.3.0, calling `$this->pings()` causes a fatal error**: the return type of `___parentContent()` in the `Widget\Comments\Ping` class is incompatible with the parent class, so PHP fails as soon as it loads the class, and the page stops at the point of the call. So do not use `pings()` or `listPings()`; the `singlePing` callback in `functions.php` has no effect either.

To show trackbacks separately from comments, filter `comments()` by `type`: skip trackbacks at the start of the `threadedComments` callback, then loop once more on its own to output the trackbacks.

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

<h3><?php _e('Trackbacks'); ?></h3>
<ul class="ping-list">
    <?php while ($comments->next()): ?>
        <?php if ('comment' !== $comments->type): ?>
            <li><?php $comments->author(); ?>: <?php $comments->excerpt(60); ?></li>
        <?php endif; ?>
    <?php endwhile; ?>
</ul>
```

When the `$comments->next()` loop reaches the end, it automatically goes back to the start, so you can loop over it again both before and after `listComments()`. Note that both `commentsNum()` and the comment pagination count include trackbacks.
