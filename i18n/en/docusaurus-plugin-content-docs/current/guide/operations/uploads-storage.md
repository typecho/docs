---
title: Attachment Storage Location
sidebar_position: 6
translation: machine
source_commit: 5cf852b2f6ae6c6a96b12a34d8bd77f83e99bfa7
---

# Attachment Storage Location

Attachments uploaded in the admin panel are saved by default under `usr/uploads/year/month/` in the site directory, with a random numeric file name, for example `usr/uploads/2026/10/4159619356.png`. The access URL is "site URL + this path".

Three constants change where attachments are stored and the URL they are served from. All of them go in `config.inc.php`.

## The three constants

| Constant | Default | What it controls |
|---|---|---|
| `__TYPECHO_UPLOAD_DIR__` | `/usr/uploads` | The **relative path** prefix of attachments. It is written into the attachment record (the `path` field) at upload time and never changes afterwards |
| `__TYPECHO_UPLOAD_ROOT_DIR__` | The site root directory (`__TYPECHO_ROOT_DIR__`) | The base directory that the relative path is resolved against, that is, where the files are **actually stored** |
| `__TYPECHO_UPLOAD_URL__` | Site URL | The prefix of the attachment **access URL** |

How they relate (`var/Widget/Upload.php`):

```text
file location = UPLOAD_ROOT_DIR + UPLOAD_DIR + /year/month/filename
recorded path =                   UPLOAD_DIR + /year/month/filename
access URL    = UPLOAD_URL      + recorded path
```

For example:

```php
define('__TYPECHO_UPLOAD_ROOT_DIR__', '/data/typecho');
define('__TYPECHO_UPLOAD_DIR__', '/files');
define('__TYPECHO_UPLOAD_URL__', 'https://cdn.example.com');
```

After you upload an image, the file is saved at `/data/typecho/files/2026/10/3810523630.png`, the recorded path is `/files/2026/10/3810523630.png`, and the URL is `https://cdn.example.com/files/2026/10/3810523630.png`.

## Common uses

### Storing uploads outside the site directory

Suitable for containers, read-only deployments or a separate data disk. Set `__TYPECHO_UPLOAD_ROOT_DIR__` and have the web server map the corresponding URL to that directory:

```php
define('__TYPECHO_UPLOAD_ROOT_DIR__', '/data/typecho');
```

```nginx
# Requests for /usr/uploads/... read /data/typecho/usr/uploads/...
location ^~ /usr/uploads/ {
    root /data/typecho;
}
```

The directory must be writable by the PHP process. Typecho creates the `year/month` subdirectories level by level as needed, with permissions `0755`.

### Serving attachments through a CDN

Set only `__TYPECHO_UPLOAD_URL__`. The files stay where they are, and the CDN pulls them from your site:

```php
define('__TYPECHO_UPLOAD_URL__', 'https://cdn.example.com');
```

### What about existing attachments

These three constants only affect uploads and URL generation **from then on**:

- The recorded path of existing attachments is still the original `/usr/uploads/...`. After you change `__TYPECHO_UPLOAD_ROOT_DIR__`, move the existing files to the new directory under the same relative paths, otherwise the admin panel cannot read or replace them;
- In published post content, image URLs are the full URLs at the time of insertion and do not follow `__TYPECHO_UPLOAD_URL__`. If needed, replace them in bulk in the content; see [Migrating from other blogs](./migration.md#replace-urls).

## Known issue: deleting an attachment does not delete the file with a custom root directory

In 1.3.0, when an attachment is deleted, Typecho always builds the file path from the **site root directory** (`deleteHandle()` in `var/Widget/Upload.php`) and does not use `__TYPECHO_UPLOAD_ROOT_DIR__`. So when this constant is set and you delete an attachment in the admin panel, the record is removed but the file stays on disk.

To clean up, compare the files on disk with the attachment records in the database (rows in the `typecho_contents` table with `type = 'attachment'`) and delete the leftovers manually.

## Object storage (S3, OSS, COS, etc.)

Typecho itself only writes attachments to the local file system. To store attachments in object storage you need a plugin: Typecho provides five plugin interfaces on `Widget\Upload`, namely `uploadHandle`, `modifyHandle`, `deleteHandle`, `attachmentHandle` and `attachmentDataHandle`. Once a plugin takes over, uploading, replacing, deleting and URL generation are all handled by the plugin.

- When choosing a plugin, make sure it supports 1.3 and implements the **delete** interface; otherwise files in object storage are not removed when you delete an attachment in the admin panel;
- Plugin developers can refer to the attachment storage example in [Common plugin patterns](../../develop/plugin/patterns.md#external-storage).

If you only want attachments **served through a CDN** while the files stay on the server, you don't need a plugin; use `__TYPECHO_UPLOAD_URL__` as described above.

## Upload size limits

The maximum size of a single file is determined by PHP's `upload_max_filesize` and `post_max_size`; nginx also has `client_max_body_size` (default 1M). When uploading a large file fails, check all three.
