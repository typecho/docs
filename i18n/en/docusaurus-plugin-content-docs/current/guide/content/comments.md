---
title: Managing and moderating comments
sidebar_position: 8
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Managing and moderating comments

Comments left by visitors, and trackbacks and pingbacks sent by other sites, are all handled in **Manage → Comments** (管理 → 评论, `admin/manage-comments.php`). Comment form requirements, whether moderation is required, anti-spam and other options are set in **Settings → Discussion** (设置 → 评论).

## Who can manage which comments

| Role | Comments they can manage |
|---|---|
| Administrator, editor | All comments on the site (above the list you can switch between "All" (所有) and "Mine" (我的)) |
| Contributor | Only comments **on their own posts** |
| Subscriber | Cannot access comment management |

## Comments, trackbacks and pingbacks

All three are stored in the `comments` table and distinguished by `type`:

| | Comment | Trackback | Pingback |
|---|---|---|---|
| Type value | `comment` | `trackback` | `pingback` |
| Where it comes from | A visitor fills in the comment form on your post page | When another blog publishes a post, it actively submits a notification to your post's trackback URL (the post URL + `/trackback`) | A post on another site links to your post, and that site's software notifies you via the XML-RPC `pingback.ping`; Typecho fetches the other page to confirm it really contains a link to you |
| Name and content | The name and text the visitor entered | The name is the other blog's name; the content is the excerpt it submitted (truncated to the first 100 characters) | The name is the title of the other page; the content is a passage extracted from that page |
| Shown in the admin panel | Normally | Marked "Trackback" (引用) next to the author name | Marked "Trackback" (引用) next to the author name |
| Can be replied to in the admin panel | Yes | No | No |
| Prerequisite | The post has "Allow comments" (允许评论) | The post has "Allow trackbacks" (允许被引用) | The post has "Allow trackbacks", and **XMLRPC interface** (XMLRPC 接口) in **Settings → General** (设置 → 基本) is "On" (打开, `allowXmlRpc = 2`), see [Feeds and offline clients](./feeds-xmlrpc.md#xmlrpc) |

Conversely, when you publish a post: the URLs entered under **Trackbacks** (引用通告) in the editor receive a trackback; other sites linked from the content receive a pingback (if they support it).

## The three statuses

| Tab | Status value | Meaning |
|---|---|---|
| Approved (已通过) | `approved` | Shown on the front end and counted in the post's comment count |
| Pending (待审核) | `waiting` | Not shown, waiting to be handled; the tab shows the count |
| Spam (垃圾) | `spam` | Not shown; the tab shows the count |

The status of a new comment depends on **Settings → Discussion**:

- With "All comments must be moderated" (所有评论必须经过审核, `commentsRequireModeration`) checked: every new comment is "Pending";
- With only "The commenter must have a previously approved comment" (评论者之前须有评论通过了审核, `commentsWhitelist`) checked: if the same name and email have an approved comment before, the comment is approved directly; otherwise it is pending;
- With neither checked (the default): comments are approved directly, and visitor comments appear immediately.

Comments posted on the front end by the post's author, and by editors and administrators, are always approved directly.

A post's comment count (`commentsNum`) **counts only approved comments**; when you mark a comment or trackback as spam or pending, the post's comment count decreases accordingly.

## List and filtering

Each comment shows the avatar, name, email, website, IP address, time, the post it belongs to ("on Post title") and the content. You can filter by keyword; clicking the comment count in the post or page list shows only the comments on that item (`manage-comments.php?cid=文章ID`).

## Actions on a single comment

Below each comment is a set of links; the one matching the current status is greyed out:

| Action | Description |
|---|---|
| Approve / Pending / Spam (通过 / 待审核 / 垃圾) | Switch the status |
| Edit (编辑) | Edit "Username" (用户名), "Email" (电子邮箱), "Homepage" (个人主页) and "Content" (内容) directly in the list, then click **Submit** (提交) to save |
| Reply (回复) | Shown only for comments. Enter text in the box below and click **Reply**. The reply is posted under your account, approved directly, and shown as a child comment of this comment (when the theme supports nested comments) |
| Delete (删除) | Deletes after you confirm "Are you sure you want to delete the comment by XX?" (你确认要删除 某某 的评论吗?) |

A reply adds a new approved comment to the `comments` table whose `parent` points to the original comment and whose author is the currently logged-in user; editing replaces the content of the original comment directly.

## Bulk actions

Check several comments (the checkbox in the table header selects all), and choose from the **Selected items** (选中项) menu: Approve, Pending, Mark as spam, Delete.

The "Spam" tab also has a **Delete all spam comments** (删除所有垃圾评论) button (confirm "Are you sure you want to delete all spam comments?" (你确认要删除所有垃圾评论吗?)). When an editor or administrator clicks it in the "All" view, the spam comments of the whole site are deleted; in the "Mine" view, or when a contributor clicks it, only the spam comments on their own posts are deleted.

## A few anti-spam rules

- Once a trackback from an IP has been marked as spam, further trackbacks from that IP get a 404 directly.
- The anti-spam protection, referer check, minimum interval between comments from the same IP and other options in **Settings → Discussion** stop most bot comments.
- To close comments or trackbacks on a particular post: uncheck "Allow comments" or "Allow trackbacks" in the editor's **Advanced options** (高级选项), see [Writing posts](./posts.md).
- For stronger filtering, use an anti-spam plugin.
