---
title: Attachments and file management
sidebar_position: 5
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Attachments and file management

Attachments are the images and other files uploaded to the site; in the admin menu they are called "Files" (文件). You upload them from the "Attachments" (附件) panel on the right of the post and page editors, and manage them centrally under "Manage → Files" (管理 → 文件, `manage-medias.php`).

## Who can upload

| Action | Minimum role |
|---|---|
| Upload, edit and delete **your own** files in the editor (`media.php?cid=`) | contributor |
| Open "Manage → Files" to manage everyone's files | editor |
| Change the allowed upload file types | administrator |

## Uploading

In the editor, switch to the "Attachments" panel on the right and drag files onto the "Drop files here" (拖放文件到这里) area, or click "Choose files to upload" (选择文件上传).

- In 1.3.0 you select one file at a time (selecting several files at once is a change merged after 1.3.0 and not yet released).
- The size limit for a single file comes from PHP's `upload_max_filesize`; when it is exceeded, "File size exceeds the limit" (文件大小超过限制) is shown. Uploads are also limited by PHP's `post_max_size` and by the web server (for example nginx's `client_max_body_size`).
- When the extension is not in the allowed list, "X upload failed / File extension is not supported" (某某 上传失败 / 文件扩展名不被支持) is shown and disappears automatically after two seconds.
- After a successful upload, click the file name to insert it into the content; see [Writing posts](./posts.md#attachments).

### Where files are stored

By default, files are stored under `usr/uploads/year/month/` in the site directory, with a file name made of random digits plus the original extension (converted to lowercase), for example:

```text
usr/uploads/2026/10/3396430629.png
```

The original file name is only stored in the database and is shown as the attachment's title. The access address is "site URL + this path". Missing directories are created level by level automatically, so `usr/uploads` must be writable by PHP. To change the storage location or serve attachments through a CDN, see [Upload storage location](/guide/operations/uploads-storage).

## Allowed file types {#types}

Set them under "Allowed upload file types" (允许上传的文件类型) in "Settings → General" (设置 → 基本); the corresponding option is `attachmentTypes`, and **by default only images are allowed**.

| Option in the UI | Saved value | Extensions actually allowed |
|---|---|---|
| Image files (图片文件) | `@image@` | gif jpg jpeg png tiff bmp webp |
| Media files (多媒体文件) | `@media@` | mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv |
| Common archive files (常用档案文件) | `@doc@` | txt doc docx xls xlsx ppt pptx zip rar pdf |
| Other formats (其他格式) | Extensions entered in the text box | Separated by ASCII commas, for example `cpp, h, mak` |

After saving, the option value is a string of these items joined with commas. For example, if you tick "Image files", "Common archive files" and "Other formats" and enter `svg, PHP, md` in the text box, the saved value is:

```text
@image@,@doc@,svg,md
```

- Custom extensions are converted to lowercase; `php`, `php4`, `php5`, `sh`, `asp`, `jsp`, `rb`, `py`, `pl`, `dll`, `exe` and `bat` are removed outright and cannot be allowed here.
- Be careful when allowing `svg`: SVG files can contain scripts, and when a visitor opens one directly, the script runs under your domain. Only allow it if you trust every user who can upload files.

:::caution avif cannot actually be uploaded
The description after "Image files" reads `(gif jpg jpeg png tiff bmp webp avif)`, but when 1.3.0 expands `@image@` it does not include `avif` (`___allowedAttachmentTypes()` in `var/Widget/Options.php`), so uploading an `.avif` file reports "File extension is not supported". If you need to upload avif files, tick "Other formats" and enter `avif`.
:::

## Editing and replacing files {#replace}

Click the edit icon next to a file in the editor's "Attachments" panel, or click the file name under "Manage → Files", to open the "Edit (file name)" (编辑 文件名) page (`media.php?cid=`):

| UI name | Field | Description |
|---|---|---|
| Title * (标题) | `name` | Defaults to the original file name at upload |
| Slug (缩略名) | `slug` | Used in the address of the attachment page |
| Description (描述) | `description` | Some themes display it |

The upload area at the top of the page is for **replacing the file**: upload a new file and the original file is overwritten, with the message "File X has been replaced" (文件 某某 已经替换).

- After replacing, **the path and the access address stay the same**, so addresses already inserted in content remain valid; the title also stays the same.
- The new file must have the same extension as the original. If the extension differs (for example replacing a `.txt` file with a `.md` file), only "Upload failed" (上传失败) is shown, without a reason.
- Browsers or CDNs may cache the old file; if you see no change after replacing, force a refresh or purge the CDN cache.

## Deleting files

- Click the delete icon in the editor's "Attachments" panel (confirm "Are you sure you want to delete file X?" (确认要删除文件 某某 吗?)), or tick files under "Manage → Files" and choose "Delete" (删除) (confirm "Are you sure you want to delete these files?" (你确认要删除这些文件吗?)).
- Deleting removes both the database record and the file on disk. Addresses that reference it in content stop working, and you need to edit the content yourself.
- If `__TYPECHO_UPLOAD_ROOT_DIR__` is defined in `config.inc.php`, 1.3.0 does not delete the file on disk when an attachment is deleted; see [Upload storage location](/guide/operations/uploads-storage).

## Unattached files {#unattached}

The "Belongs to" (所属文章) column under "Manage → Files" shows which post or page a file belongs to. Files that belong to no content are called **unattached files**. Common sources:

- a post or page was deleted: its attachments are not deleted but become unattached;
- files were uploaded in a new post that was never saved or published.

The "Clean up unattached files" (清理未归档文件) button above the list (confirm "Are you sure you want to clean up unattached files?" (您确认要清理未归档的文件吗?)) deletes all unattached files, including the files on disk. If content references these files' addresses directly (for example, you copied an image address before deleting an old post), those addresses stop working after the cleanup, so check before you run it.

## Attachment pages

Every attachment also has its own front-end page (default address `/attachment/{cid}/`). Themes can use it to show an image or a download link, and visitors can comment on the attachment page (attachments allow comments by default).
