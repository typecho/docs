---
title: Writing posts
sidebar_position: 2
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Writing posts

Write a new post under "Write → Write Post" (撰写 → 撰写文章, `admin/write-post.php`); to edit an existing post, click its title under "Manage → Posts" (管理 → 文章, `write-post.php?cid=post ID`). Contributors and above can write posts.

## Editor

| Area | Description |
|---|---|
| Title (标题) | When left empty, the post is saved as "Untitled document" (未命名文档) |
| URL slug (网址缩略名) | Shown in the permalink below the title; the input only appears when the link rule contains `{slug}`. The default rule `/archives/{cid}/` uses the post ID, so you do not need to fill it in |
| Post content (文章内容) | Markdown editor with two tabs above it, "Write" (撰写) and "Preview" (预览) |
| Custom fields (自定义字段) | Can be expanded below the content; see [Custom fields](./custom-fields.md) |
| "Options" (选项) panel on the right | Publish date, categories, tags, advanced options |
| "Attachments" (附件) panel on the right | Upload and insert files; see below |
| Buttons | "Preview post" (预览文章), "Save draft" (保存草稿), "Publish post" (发布文章) |

### Markdown

The profile setting "Use Markdown syntax to edit and parse content" (使用 Markdown 语法编辑和解析内容, `markdown`) is on by default; in that case the editor has a toolbar and keyboard shortcuts:

| Button | Shortcut | Button | Shortcut |
|---|---|---|---|
| Bold | <kbd>Ctrl</kbd>+<kbd>B</kbd> | Numbered list | <kbd>Ctrl</kbd>+<kbd>O</kbd> |
| Italic | <kbd>Ctrl</kbd>+<kbd>I</kbd> | Bulleted list | <kbd>Ctrl</kbd>+<kbd>U</kbd> |
| Link | <kbd>Ctrl</kbd>+<kbd>L</kbd> | Heading | <kbd>Ctrl</kbd>+<kbd>H</kbd> |
| Quote | <kbd>Ctrl</kbd>+<kbd>Q</kbd> | Horizontal rule | <kbd>Ctrl</kbd>+<kbd>R</kbd> |
| Code | <kbd>Ctrl</kbd>+<kbd>K</kbd> | Summary break (inserts `<!--more-->`) | <kbd>Ctrl</kbd>+<kbd>M</kbd> |
| Image | <kbd>Ctrl</kbd>+<kbd>G</kbd> | Undo / redo | <kbd>Ctrl</kbd>+<kbd>Z</kbd> / <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>Z</kbd> |
| Full screen / exit full screen | <kbd>Ctrl</kbd>+<kbd>J</kbd> / <kbd>Ctrl</kbd>+<kbd>E</kbd> | Save | <kbd>Ctrl</kbd>+<kbd>S</kbd> |

On macOS, use <kbd>Cmd</kbd> instead of <kbd>Ctrl</kbd>.

- A post written in Markdown starts with a `<!--markdown-->` marker, and the front end parses it as Markdown; posts without this marker are output as HTML (blank lines are automatically turned into paragraphs).
- When you open an older post that was not written in Markdown, the editor asks "This post was not created with Markdown syntax. Continue editing it with Markdown?" (这篇文章不是由Markdown语法创建的, 继续使用Markdown编辑它吗?). If you choose "Yes" and save, the post becomes a Markdown post.
- With `markdown` turned off, the editor is a plain text box, and inserting an attachment inserts an HTML tag. Posts already written in Markdown are still displayed as Markdown.
- The part before `<!--more-->` is the summary; the theme's post lists and feeds set to "output summary only" use it.

### Publish date

The "Publish date" (发布日期) format is `year-month-day hour:minute`, for example `2026-12-01 09:00`, interpreted in the time zone set under "Settings → General" (设置 → 基本). For a new post, leaving it empty uses the moment of publishing; when you edit an existing post, the original date is kept. Setting the date in the future schedules the post; see [below](#scheduled).

## Drafts, autosave and revisions {#drafts}

### Saving a draft

When you click "Save draft" (or press <kbd>Ctrl</kbd>/<kbd>Cmd</kbd>+<kbd>S</kbd>), what gets saved depends on the current state of the post:

| Situation | Saved as | Notice at the top of the editor | In the post list |
|---|---|---|---|
| New post, never published | Draft, content type `post_draft` | "You are editing an unpublished draft" (当前正在编辑的是未发布的草稿) | Appears under the "Drafts" (草稿) tab |
| Existing post (published, pending, hidden, etc.) | Revision, content type `revision`, attached to the original post | "You are editing a revision saved at (time); you can also delete it" (你正在编辑的是保存于 某时间 的修订版, 你也可以 删除它) | "Has revision" (有修订版) after the title |

Revisions are new in 1.3 (see [What's new in 1.3](../getting-started/whats-new-1-3.md)). Key points:

- **The original post is unaffected**: after a revision is saved, the front end still shows the last published content, and the post counts of categories and tags do not change.
- **Each post has only one revision**; saving again overwrites it, so no list of historical versions builds up.
- When you click "Publish post", the content of the revision is written back to the original post and the revision is deleted.
- If you do not want the change, click "delete it" (删除它) in the notice to go back to the published content.
- When a post is deleted, its revision is deleted with it.

### Autosave

After you turn on "Autosave" (自动保存, `autoSave`, off by default) in [Profile](./profile.md#writing), the editor runs "Save draft" automatically **3 seconds** after you stop typing, and the area to the left of the buttons shows "Not saved yet" (尚未保存), "Saving" (正在保存) and "Saved (time)" (已保存 (时间)) in turn; on failure it shows "Save failed, please try again" (保存失败, 请重试). Autosave goes through the same save process, so for a published post it also only creates a revision and does not change the front end.

With autosave off, if you leave the page with unsaved changes, the browser asks "The content has changed and has not been saved. Are you sure you want to leave this page?" (内容已经改变尚未保存, 您确认要离开此页面吗?).

:::note
The time after "Saved" is shown in a form like `16:50:08 PM`: a 24-hour time followed by AM/PM. This is a display issue (`format('H:i:s A')` in `var/Widget/Contents/Post/Edit.php`) and does not affect saving.
:::

### Preview

"Preview post" opens a preview in the front-end style inside the page. If the content has changed, it first asks "The changed content must be saved before it can be previewed. Save it?" (修改后的内容需要保存后才能预览, 是否保存?), saves (as a draft or a revision) and then previews, so previewing never changes the front end.

## Categories and tags

- **Categories**: tick one or more. A new post has the default category ticked in advance; if you untick everything, the post is still filed under the default category when saved. For adding and removing categories and setting the default category, see [Categories and tags](./categories-tags.md).
- **Tags**: type a tag name and press the ASCII comma `,` or Enter to turn it into a tag chip. Existing tags are suggested as you type; for a tag that does not exist, the hint "This tag does not exist, press Enter to create it" (此标签不存在, 按回车创建) appears, and the tag is created automatically on save. Typing a full-width comma "，" in the input does not split the tag, but the server treats "，" as a separator when saving.

## Advanced options

Click "Advanced options" (高级选项) on the right to expand them:

| UI name | Field | Description |
|---|---|---|
| Visibility (公开度) | `visibility` | Public, hidden, password protected, private, pending. **Only editors and above see this option**; for the meanings, see [Visibility and password protection](./visibility.md) |
| Permissions → Allow comments (权限控制 → 允许评论) | `allowComment` | When off, the post no longer accepts comments |
| Permissions → Allow pings (允许被引用) | `allowPing` | When off, the post no longer accepts trackbacks and pingbacks |
| Permissions → Allow in feeds (允许在聚合中出现) | `allowFeed` | When off, the post does not appear in RSS / Atom feeds |
| Trackback (引用通告) | `trackback` | Trackback URLs of the other posts to notify, one per line; sent on publish |

The default state of the three "Allow" switches for new posts comes from "Allow by default" (默认允许) in [Profile](./profile.md#writing). In addition, if "Automatically close comments N days after a post is published" (在文章发布 N 天以后自动关闭评论) is on under "Settings → Comments" (设置 → 评论), comments and pings are closed on posts past that limit even if "Allow comments" and "Allow pings" are ticked.

## Inserting attachments {#attachments}

Switch to the "Attachments" panel on the right and drag files onto the "Drop files here" (拖放文件到这里) area, or click "Choose files to upload" (选择文件上传).

- In 1.3.0 you can only select one file at a time (selecting several files at once is a change merged after 1.3.0 and not yet released). Pasting an image directly into the Markdown editor also uploads it.
- Uploaded files appear in the list. **Clicking the file name** inserts it into the editor: images are inserted as Markdown images, other files as links, with the address written as a reference at the end of the content, for example:

  ```markdown
  ![pic.png][1]

    [1]: https://example.com/usr/uploads/2026/10/3396430629.png
  ```

- The edit icon after the file name opens the file's edit page; the delete icon deletes the file.
- If the post has already been saved (it has a post ID), uploaded files belong to the post immediately; for a new post they are attached on the first save or publish.
- For which file types are allowed, file size limits, replacing files and so on, see [Attachments and file management](./attachments.md).

## Publishing

Click "Publish post".

- Editors and above publish with the chosen "Visibility", which defaults to "Public" (公开); afterwards you are taken to the post list with the message "Post "title" has been published" (文章 "标题" 已经发布).
- **Contributors** have no "Visibility" option; their posts always go to the "pending" (待审核) state, with the message "Post "title" is awaiting review" (文章 "标题" 等待审核). An editor or administrator reviews it under "Manage → Posts → Pending" (管理 → 文章 → 待审核): tick it, then choose "Mark as public" (标记为公开) in the "Selected items" (选中项) menu.
- If a contributor edits and publishes again a post that has **already been approved**, it becomes "pending" again and disappears from the front end until it is approved again (`checkStatus()` in `var/Widget/Contents/EditTrait.php`: content published by roles below editor is always `waiting`).

### Scheduled publishing {#scheduled}

Set the "Publish date" to a future time and click "Publish post" (with visibility "Public"). At that time, the post appears on the front end automatically; no cron job is needed. Until then:

- the post's address returns 404, and it does not appear on the home page, in categories, tags, archives or feeds; even the author does not see it while logged in;
- in the admin post list it is shown like any other post, with the future date in the date column (for example "Dec 1"), and **without any "scheduled" marker**;
- it is already counted in the post counts of categories and tags, so front-end lists show fewer posts than the admin panel; see [Categories and tags](./categories-tags.md#counts).

:::caution Scheduled publishing is not "pending"
A scheduled post still has the status `publish` (public); only its creation time is in the future. "Pending" (`waiting`) in Visibility is something else: a pending post never appears on the front end until it is approved, regardless of its date.
:::

## Post list

"Manage → Posts" (`manage-posts.php`):

- Tabs at the top: "Available" (可用, all posts, including unpublished drafts), "Pending" (待审核, with a count) and "Drafts" (草稿). Editors and above also have an "All" (所有) / "Mine" (我的) toggle, which defaults to "Mine" and remembers your choice; contributors only see their own posts.
- The status column shows markers such as "Pending" (待审核), "Password protected" (密码保护), "Private" (私密), "Hidden" (隐藏) and "Has revision" (有修订版); public posts show no marker.
- You can filter by keyword and by category.
- After ticking posts, the "Selected items" menu offers: Delete, Mark as public, Mark as pending, Mark as hidden, Mark as private.

When a post is deleted, its comments, custom fields and revision are deleted with it; its attachments are not deleted but become "unattached" (未归档) files; see [Attachments and file management](./attachments.md#unattached).
