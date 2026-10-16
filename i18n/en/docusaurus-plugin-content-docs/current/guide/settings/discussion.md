---
title: Discussion Settings
sidebar_position: 2
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Discussion Settings

"Settings → Discussion" (设置 → 评论, `admin/options-discussion.php`) in the admin panel; only administrators can open it. These settings apply to every post and page on the site; "Allow comments" (允许评论) and "Allow trackbacks" (允许被引用) for a single item are set in the editor, and comments are moderated in "Manage → Comments" (管理 → 评论); see [Comment management and moderation](/guide/content/comments).

## Comment date format {#date-format}

"Comment date format" (评论日期格式, `commentDateFormat`) is the default format a theme uses when outputting comment dates (used when the theme calls `$comments->date()` without specifying a format of its own). It is written with PHP `date()` format characters; the "PHP 日期格式写法" link in the admin description points to this function in the PHP manual.

The default is `F jS, Y \a\t h:i a`. Common formats, using 5:23 pm on 16 October 2026 as an example:

| Format | Output |
|---|---|
| `Y-m-d` | 2026-10-16 |
| `Y-m-d H:i` | 2026-10-16 17:23 |
| `Y年n月j日` | 2026年10月16日 |
| `Y年m月d日 H:i` | 2026年10月16日 17:23 |
| `n/j/Y` | 10/16/2026 |
| `M j, Y` | Oct 16, 2026 |
| `F jS, Y \a\t h:i a` (default) | October 16th, 2026 at 05:23 pm |

Common format characters: `Y` four-digit year, `m` / `n` month (with / without a leading zero), `d` / `j` day, `H` hour in 24-hour format, `h` hour in 12-hour format, `i` minutes, `s` seconds, `a` am/pm. Every letter in the format is treated as a format character; to output a letter literally, put a backslash in front of it, for example `\a\t` in the default format outputs "at". English month and weekday names (`F`, `M`, `D`, `l`) are not translated with the interface language.

For example, after you change the format to `Y-m-d H:i`, comment times in the default theme are shown as `2026-10-16 17:23`. The date format for posts is set separately in [Reading settings](./reading.md#date-format).

## Number of comments in lists

"Number of comments in lists" (评论列表数目, `commentsListSize`, default 10) is the number of items shown in comment lists such as "Recent replies" (最近回复) in a theme's sidebar; it has nothing to do with the comment list below a post.

## Comment display (`commentsShow[]`) {#show}

This group of checkboxes determines how comments are displayed on the front end. The UI submits a `commentsShow[]` array, which is split into the following separate options when saved:

| UI label | Option name | Default | Effect |
|---|---|---|---|
| Show only comments, not Pingbacks and Trackbacks (仅显示评论, 不显示 Pingback 和 Trackback) | `commentsShowCommentOnly` | Off | Only ordinary comments are shown in a post's comment list and in "Recent replies"; trackbacks and pingbacks are not shown (the data is still stored and managed in the admin panel as usual) |
| Use Markdown syntax in comments (在评论中使用 Markdown 语法) | `commentsMarkdown` | Off | Comment content is parsed as Markdown; when off, it is only split into paragraphs automatically |
| Automatically link commenter names to their homepage (评论者名称显示时自动加上其个人主页链接) | `commentsShowUrl` | On | When a commenter has entered a URL, their name links to it |
| Use the nofollow attribute on commenter homepage links (对评论者个人主页链接使用 nofollow 属性) | `commentsUrlNofollow` | On | The links above carry `rel="nofollow"` |
| Enable Gravatar avatars, showing avatars rated up to [drop-down] (启用 Gravatar 头像服务, 最高显示评级为 [下拉框] 的头像) | `commentsAvatar`; the drop-down is `commentsAvatarRating` | On, `G` | Commenter avatars are fetched from Gravatar by email address; the rating can be `G` (general), `PG` (13 and over), `R` (adults 17 and over) or `X` (restricted), and avatars rated above the selected rating are not shown |
| Enable pagination, showing [number] comments per page, with [first page / last page] shown by default (启用分页, 并且每页显示 [数字] 篇评论, 在列出时将 [第一页 / 最后一页] 作为默认显示) | `commentsPageBreak`; the number is `commentsPageSize`, the drop-down is `commentsPageDisplay` | Off, 20, last page | Comment pagination. When `commentsPageDisplay` is `last`, opening a post shows the last page (the newest comments) by default; when it is `first`, the first page is shown |
| Enable comment replies, with [number] as the maximum reply depth per comment (启用评论回复, 以 [数字] 层作为每个评论最多的回复层数) | `commentsThreaded`; the number is `commentsMaxNestingLevels` | On, 5 | Nested replies; see below |
| Show [newer / older] comments first (将 [较新的 / 较旧的] 的评论显示在前面) | `commentsOrder` | Older (`ASC`) | `DESC` newer comments first, `ASC` older comments first |

A few details:

- **The reply depth can only be between 2 and 7**. A number outside this range is saved as the nearest bound; for example, 10 is saved as 7. Replies beyond the maximum depth are no longer indented further, but are shown under the comment one level up.
- When "Enable comment replies" is turned off, all comments are shown flat in order, and the theme's "Reply" button usually disappears as well.
- Whether these options take effect also depends on whether the theme uses Typecho's comment output methods; themes that implement their own comment lists may ignore some of them.

## Comment submission (`commentsPost[]`) {#post}

This group determines what conditions a visitor's comment must meet when it is submitted. It is likewise submitted as a `commentsPost[]` array and saved as separate options:

| UI label | Option name | Default | Effect |
|---|---|---|---|
| All comments must be moderated (所有评论必须经过审核) | `commentsRequireModeration` | Off | New comments go to "Pending" (待审核) |
| The commenter must have a previously approved comment (评论者之前须有评论通过了审核) | `commentsWhitelist` | Off | A comment is approved directly only if the same name and email have had an approved comment before; otherwise it is pending. Takes effect only when the previous option is off |
| Email is required (必须填写邮箱) | `commentsRequireMail` | On | Commenters who are not logged in must enter an email; otherwise the message "An email address is required" (必须填写电子邮箱地址) appears |
| URL is required (必须填写网址) | `commentsRequireUrl` | Off | Commenters who are not logged in must enter a homepage; otherwise the message "A homepage is required" (必须填写个人主页) appears |
| Check that the comment's referring page URL matches the post link (检查评论来源页 URL 是否与文章链接一致) | `commentsCheckReferer` | On | See [below](#referer) |
| Enable anti-spam protection (开启反垃圾保护) | `commentsAntiSpam` | On | See [below](#anti-spam) |
| Automatically close comments [number] days after a post is published (在文章发布 [数字] 天以后自动关闭评论) | `commentsAutoClose`; the number is `commentsPostTimeout` | Off, 30 | Once a post has been published for more than N days, comments and trackbacks are both closed automatically. The UI takes days, and the database stores seconds; for example, 14 is stored as `1209600` |
| Limit the interval between comments from the same IP to [number] minutes (同一 IP 发布评论的时间间隔限制为 [数字] 分钟) | `commentsPostIntervalEnable`; the number is `commentsPostInterval` | On, 1 | The minimum interval between two comments from the same IP on the same post; when too fast, the message "Sorry, you are posting too frequently, please try again later." (对不起, 您的发言过于频繁, 请稍候再次发布.) appears. The post's author, editors and administrators are not limited. The UI takes minutes and stores seconds; for example, 2 is stored as `120` |

:::note The comment interval only accepts whole numbers
The source code keeps one decimal place for the minutes (for example 0.5 minutes = 30 seconds), but the input is a plain number field: entering `0.5` makes the browser report "Please enter a valid value" and the form cannot be submitted. So in the admin panel you can only enter whole minutes.
:::

For moderation statuses and how to handle them, see [Comment management and moderation](/guide/content/comments).

### Referring page check {#referer}

When enabled, Typecho requires the comment request to carry a `Referer` whose domain matches the post URL and whose path starts with the post URL (when a page is used as the front page, the front page URL also counts); otherwise the comment is rejected with the message "Wrong comment referring page." (评论来源页错误., HTTP 403).

This blocks bots that post directly to the comment endpoint, but it also affects visitors who have turned `Referer` off: browser privacy extensions, a `Referrer-Policy: no-referrer` response header and comment forms on a different domain all cause comments to fail. When you see "Wrong comment referring page", check these first.

### Anti-spam protection {#anti-spam}

When enabled, a script is output in the theme's page head (where the theme template calls `$this->header()`): only after the visitor scrolls, moves the mouse, presses a key or touches the page does the script add a check value to the comment form. Submissions without this check value are rejected, and the page simply jumps back to the post.

So comments fail to submit in these cases: the visitor's browser has JavaScript disabled; the theme does not call `$this->header()`. When troubleshooting comments that cannot be submitted, you can temporarily turn this option off to confirm whether it is the cause.

## Allowed HTML tags and attributes {#html-tags}

"Allowed HTML tags and attributes" (允许使用的HTML标签和属性, `commentsHTMLTagAllowed`) is empty by default, which means only paragraphs and line breaks (`<p>`, `<br>`) are kept in comments, and all other HTML tags are removed. To allow more, fill it in following the example in the UI:

```html
<a href=""> <img src=""> <blockquote>
```

Only the attributes listed after a tag are kept. When "Use Markdown syntax in comments" is enabled, the HTML generated by Markdown also goes through this filter; for example, to make Markdown links work, you need to allow `<a href="">`.
