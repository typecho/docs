---
title: Discussion Settings
sidebar_position: 2
translation: machine
source_commit: aa7feeba4a3e65c1981bae0a64ca5696e3af4504
---

# Discussion Settings

"Settings → Comments" (`admin/options-discussion.php`) in the admin panel; only administrators can open it. These settings apply to every post and page on the site; "Allow comments" and "Allow trackbacks" for a single item are set in the editor, and comments are moderated in "Manage → Comments"; see [Comment management and moderation](../content/comments.md).

## Comment date format {#date-format}

"Comment date format" (`commentDateFormat`) is the default format a theme uses when outputting comment dates (used when the theme calls `$comments->date()` without specifying a format of its own). It is written with PHP `date()` format characters; the "PHP date format" link in the admin description points to this function in the PHP manual.

The default is `F jS, Y \a\t h:i a`. Common formats, using 5:23 pm on 16 October 2026 as an example:

| Format | Output |
|---|---|
| `Y-m-d` | 2026-10-16 |
| `Y-m-d H:i` | 2026-10-16 17:23 |
| `D, j M Y` | Fri, 16 Oct 2026 |
| `l, F j, Y g:i a` | Friday, October 16, 2026 5:23 pm |
| `n/j/Y` | 10/16/2026 |
| `M j, Y` | Oct 16, 2026 |
| `F jS, Y \a\t h:i a` (default) | October 16th, 2026 at 05:23 pm |

Common format characters: `Y` four-digit year, `m` / `n` month (with / without a leading zero), `d` / `j` day, `H` hour in 24-hour format, `h` hour in 12-hour format, `i` minutes, `s` seconds, `a` am/pm. Every letter in the format is treated as a format character; to output a letter literally, put a backslash in front of it, for example `\a\t` in the default format outputs "at". English month and weekday names (`F`, `M`, `D`, `l`) are not translated with the interface language.

For example, after you change the format to `Y-m-d H:i`, comment times in the default theme are shown as `2026-10-16 17:23`. The date format for posts is set separately in [Reading settings](./reading.md#date-format).

## Number of comments in lists

"Number of comments in lists" (`commentsListSize`, default 10) is the number of items shown in comment lists such as "Recent replies" in a theme's sidebar; it has nothing to do with the comment list below a post.

## Comment display (`commentsShow[]`) {#show}

This group of checkboxes determines how comments are displayed on the front end. The UI submits a `commentsShow[]` array, which is split into the following separate options when saved:

| UI label | Option name | Default | Effect |
|---|---|---|---|
| Show only comments, not Pingbacks and Trackbacks | `commentsShowCommentOnly` | Off | Only ordinary comments are shown in a post's comment list and in "Recent replies"; trackbacks and pingbacks are not shown (the data is still stored and managed in the admin panel as usual) |
| Use Markdown syntax in comments | `commentsMarkdown` | Off | Comment content is parsed as Markdown; when off, it is only split into paragraphs automatically |
| Automatically link commenter names to their homepage | `commentsShowUrl` | On | When a commenter has entered a URL, their name links to it |
| Use the nofollow attribute on commenter homepage links | `commentsUrlNofollow` | On | The links above carry `rel="nofollow"` |
| Enable Gravatar avatars, showing avatars rated up to [drop-down] | `commentsAvatar`; the drop-down is `commentsAvatarRating` | On, `G` | Commenter avatars are fetched from Gravatar by email address; the rating can be `G` (general), `PG` (13 and over), `R` (adults 17 and over) or `X` (restricted), and avatars rated above the selected rating are not shown |
| Enable pagination, showing [number] comments per page, with [first page / last page] shown by default | `commentsPageBreak`; the number is `commentsPageSize`, the drop-down is `commentsPageDisplay` | Off, 20, last page | Comment pagination. When `commentsPageDisplay` is `last`, opening a post shows the last page by default; when it is `first`, the first page is shown. First and last follow the comment order set below: with older comments first, the last page holds the newest comments; with newer comments first, choose the first page to show the newest comments by default |
| Enable comment replies, with [number] as the maximum reply depth per comment | `commentsThreaded`; the number is `commentsMaxNestingLevels` | On, 5 | Nested replies; see below |
| Show [newer / older] comments first | `commentsOrder` | Older (`ASC`) | `DESC` newer comments first, `ASC` older comments first |

A few details:

- **The reply depth can only be between 2 and 7**. A number outside this range is saved as the nearest bound; for example, 10 is saved as 7. Replies beyond the maximum depth are no longer indented further, but are shown under the comment one level up.
- When "Enable comment replies" is turned off, all comments are shown flat in order, and the theme's "Reply" button usually disappears as well.
- Whether these options take effect also depends on whether the theme uses Typecho's comment output methods; themes that implement their own comment lists may ignore some of them.

## Comment submission (`commentsPost[]`) {#post}

This group determines what conditions a visitor's comment must meet when it is submitted. It is likewise submitted as a `commentsPost[]` array and saved as separate options:

| UI label | Option name | Default | Effect |
|---|---|---|---|
| All comments must be moderated | `commentsRequireModeration` | Off | New comments go to "Pending". Comments by the post's author, editors and administrators are not affected and are approved directly; to test this setting, log out and comment as a visitor |
| The commenter must have a previously approved comment | `commentsWhitelist` | Off | A comment is approved directly only if the same name and email have had an approved comment before; otherwise it is pending. Takes effect only when the previous option is off |
| Email is required | `commentsRequireMail` | On | Commenters who are not logged in must enter an email; otherwise the message "An email address is required" appears |
| URL is required | `commentsRequireUrl` | Off | Commenters who are not logged in must enter a homepage; otherwise the message "A homepage is required" appears |
| Check that the comment's referring page URL matches the post link | `commentsCheckReferer` | On | See [below](#referer) |
| Enable anti-spam protection | `commentsAntiSpam` | On | See [below](#anti-spam) |
| Automatically close comments [number] days after a post is published | `commentsAutoClose`; the number is `commentsPostTimeout` | Off, 30 | Once a post has been published for more than N days, comments and trackbacks are both closed automatically. The UI takes days, and the database stores seconds; for example, 14 is stored as `1209600` |
| Limit the interval between comments from the same IP to [number] minutes | `commentsPostIntervalEnable`; the number is `commentsPostInterval` | On, 1 | The minimum interval between two comments from the same IP on the same post; when too fast, the message "Sorry, you are posting too frequently, please try again later." appears. The post's author, editors and administrators are not limited. The UI takes minutes and stores seconds; for example, 2 is stored as `120` |

:::note The comment interval only accepts whole numbers
The source code keeps one decimal place for the minutes (for example 0.5 minutes = 30 seconds), but the input is a plain number field: entering `0.5` makes the browser report "Please enter a valid value" and the form cannot be submitted. So in the admin panel you can only enter whole minutes.
:::

For moderation statuses and how to handle them, see [Comment management and moderation](../content/comments.md).

### Referring page check {#referer}

When enabled, Typecho requires the comment request to carry a `Referer` whose domain matches the post URL and whose path starts with the post URL (when a page is used as the front page, the front page URL also counts); otherwise the comment is rejected with the message "Wrong comment referring page." (HTTP 403).

This blocks bots that post directly to the comment endpoint, but it also affects visitors who have turned `Referer` off: browser privacy extensions, a `Referrer-Policy: no-referrer` response header and comment forms on a different domain all cause comments to fail. When you see "Wrong comment referring page", check these first.

### Anti-spam protection {#anti-spam}

When enabled, a script is output in the theme's page head (where the theme template calls `$this->header()`): only after the visitor scrolls, moves the mouse, presses a key or touches the page does the script add a check value to the comment form. Submissions without this check value are rejected, and the page simply jumps back to the post.

So comments fail to submit in these cases: the visitor's browser has JavaScript disabled; the theme does not call `$this->header()`. When troubleshooting comments that cannot be submitted, you can temporarily turn this option off to confirm whether it is the cause.

## Allowed HTML tags and attributes {#html-tags}

"Allowed HTML tags and attributes" (`commentsHTMLTagAllowed`) is empty by default, which means only paragraphs and line breaks (`<p>`, `<br>`) are kept in comments, and all other HTML tags are removed. To allow more, fill it in following the example in the UI:

```html
<a href=""> <img src=""> <blockquote>
```

Only the attributes listed after a tag are kept. When "Use Markdown syntax in comments" is enabled, the HTML generated by Markdown also goes through this filter; for example, to make Markdown links work, you need to allow `<a href="">`.
