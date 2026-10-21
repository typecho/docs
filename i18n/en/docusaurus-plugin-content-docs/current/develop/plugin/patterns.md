---
title: Common Plugin Patterns
sidebar_position: 9
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Common Plugin Patterns

This page shows how to write the five most common kinds of plugin. Each one is a complete plugin you can activate as is. You can look up the arguments and call sites of every hook used here in the [hook list](../reference/hooks.md).

## Filtering content: contentEx and excerptEx {#content-filter}

The body and excerpt of posts and pages pass through a series of filter hooks before output, all on `Widget\Base\Contents` (handle `Widget_Abstract_Contents`):

| Hook | Type | When |
|---|---|---|
| `content` | trigger + filter | **Before** Markdown parsing; receives the source text. Once registered, Typecho no longer parses Markdown, and the plugin is responsible for turning the source text into HTML |
| `markdown` / `autoP` | trigger + filter | Markdown parsing / automatic paragraphs; once registered, replaces Typecho's built-in parser |
| `contentEx` | filter | **After** HTML is generated; receives the final body HTML |
| `excerpt` | filter | Before the excerpt is cut; receives the full body HTML |
| `excerptEx` | filter | After the excerpt is generated; receives the excerpt HTML |

When you only want to make small changes to the HTML (add attributes, replace text, insert content), use `contentEx` and `excerptEx`. Comment bodies have hooks with the same names, `content`, `markdown`, `autoP` and `contentEx`, on `Widget\Base\Comments` (handle `Widget_Abstract_Comments`); comments have no `excerpt` or `excerptEx` hooks, so modify comment HTML with `contentEx`.

The plugin below adds `target="_blank"` to external links:

```php title="usr/plugins/LinkTarget/Plugin.php"
<?php

namespace TypechoPlugin\LinkTarget;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Opens external links in a new window
 *
 * @package LinkTarget
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::filter';
        \Typecho\Plugin::factory('Widget\Base\Contents')->excerptEx = __CLASS__ . '::filter';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function filter(string $html): string
    {
        $host = parse_url(Options::alloc()->siteUrl, PHP_URL_HOST);

        return preg_replace_callback('/<a\s[^>]*href="(https?:\/\/[^"]+)"[^>]*>/i', function ($m) use ($host) {
            if (parse_url($m[1], PHP_URL_HOST) === $host || stripos($m[0], 'target=') !== false) {
                return $m[0];
            }

            return substr($m[0], 0, -1) . ' target="_blank" rel="noopener noreferrer">';
        }, $html);
    }
}
```

Key points:

- The excerpt is cut from the **already filtered body**, so the HTML that `excerptEx` receives may already have been processed once by `contentEx`. Your filter function must be safe to run more than once; the example achieves this by skipping links that already have `target=`.
- The filtered body is also used for the page's `description` meta tag and for feeds. Content inserted before or after the body shows up there too. Content you only want to display on the page is better placed in the theme, or offered to the theme through a [dynamic hook](./hooks.md#dynamic-hooks).
- `contentEx` applies everywhere a body is output: posts, pages, the home page list, feeds and so on. To handle only one kind of page, check the second argument (the content object) in the callback, e.g. `$widget instanceof \Widget\Archive && $widget->is('single')`.

## Comment anti-spam: comment on Widget_Feedback {#anti-spam}

When a visitor submits a comment, after validating the required fields and before writing to the database, Typecho passes the comment data to the `comment` filter of `Widget\Feedback`:

- The first argument is the array about to be written to the `comments` table, including `cid`, `created`, `author`, `mail`, `url`, `text`, `ip`, `agent`, `ownerId`, `authorId` (logged-in user), `parent`, `type`, `status`.
- The second argument is the post being commented on (a `Widget\Archive` object).
- The callback returns the modified array. Set `status` to `waiting` to send it to moderation, or to `spam` to send it straight to spam.
- To reject the comment, throw `\Typecho\Widget\Exception`. The visitor sees the exception message, and the comment they typed is kept and is still in the input box when they go back.

```php title="usr/plugins/CommentGuard/Plugin.php"
<?php

namespace TypechoPlugin\CommentGuard;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Simple comment filter: rejects comments containing blocked words and marks comments with too many links as spam
 *
 * @package CommentGuard
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Feedback')->comment = __CLASS__ . '::check';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $words = new Textarea('words', null, '', _t('Blocked words'), _t('One per line; a comment is rejected if its content, name or URL contains any of them'));
        $form->addInput($words);

        $maxLinks = new Text('maxLinks', null, '2', _t('Maximum number of links'), _t('Comments with more links than this are marked as spam'));
        $maxLinks->addRule('isInteger', _t('Enter an integer'));
        $form->addInput($maxLinks);
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function check(array $comment, $post): array
    {
        $config = Options::alloc()->plugin('CommentGuard');
        $haystack = $comment['text'] . "\n" . $comment['author'] . "\n" . $comment['url'];

        foreach (preg_split('/\r?\n/', (string) $config->words) as $word) {
            $word = trim($word);
            if ($word !== '' && mb_stripos($haystack, $word) !== false) {
                throw new Exception(_t('The comment contains content that is not allowed'), 403);
            }
        }

        if (preg_match_all('/https?:\/\//i', $comment['text']) > (int) $config->maxLinks) {
            $comment['status'] = 'spam';
        }

        return $comment;
    }
}
```

Trackbacks have a corresponding `trackback` filter used the same way; pingbacks go through the `pingback` filter of `Widget\XmlRpc`. To do something after the comment is written (for example sending an email notification), use `finishComment` on `Widget\Feedback`. It receives the `Widget\Feedback` object, and `$feedback->coid`, `$feedback->status` and so on are the comment that was just written.

## Attachment storage: the handler hooks of Widget_Upload {#external-storage}

`Widget\Upload` has five trigger hooks. Once registered, the plugin takes over saving, replacing and deleting attachments and generating their URLs:

| Hook | Arguments | Callback returns |
|---|---|---|
| `uploadHandle` | `$file`: the uploaded file, with `name`, `tmp_name`, `size`; uploads through XML-RPC have no `tmp_name`, the content is in `bits` | File info array, or `false` on failure |
| `modifyHandle` | `$content`: the original attachment, `$content['attachment']` is its file info; `$file`: the new file | New file info array, or `false` on failure |
| `deleteHandle` | `$content`: the attachment to delete, as above | Whether the deletion succeeded |
| `attachmentHandle` | `$attachment`: file info (a `Typecho\Config` object) | The attachment's URL |
| `attachmentDataHandle` | `$content` | File content. Typecho itself does not call it in 1.3.0, so you can leave it unimplemented |

"File info" is an array. Typecho stores it as JSON in the `text` field of the attachment record and later hands it back to the plugin as `$content['attachment']` or `$attachment`:

```php
[
    'name' => 'photo.png',             // original file name
    'path' => '/2026/10/12345678.png', // path the plugin uses to locate the file
    'size' => 1024,                     // size in bytes
    'type' => 'png',                    // extension, lowercase
    'mime' => 'image/png',
]
```

The plugin below saves attachments to a configurable directory and serves them from a configurable URL, for example a mounted network drive paired with a separate static file domain. Replace the file operations in `save()`, `deleteHandle()` and `attachmentHandle()` with an object storage SDK's upload, delete and URL generation calls, rewrite `info()`, and you have an object storage plugin. In the example, `info()` reads the size and MIME type from the local destination file; when the file is only uploaded to object storage, that local file does not exist. So before uploading, get those two values from the temporary file (`$file['tmp_name']`) or the file content sent over XML-RPC, and return them together with the path in object storage:

```php title="usr/plugins/LocalBucket/Plugin.php"
<?php

namespace TypechoPlugin\LocalBucket;

use Typecho\Common;
use Typecho\Config;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;
use Widget\Upload;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Saves attachments to a given directory and serves them from a given URL
 *
 * @package LocalBucket
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Upload')->uploadHandle = __CLASS__ . '::uploadHandle';
        \Typecho\Plugin::factory('Widget\Upload')->modifyHandle = __CLASS__ . '::modifyHandle';
        \Typecho\Plugin::factory('Widget\Upload')->deleteHandle = __CLASS__ . '::deleteHandle';
        \Typecho\Plugin::factory('Widget\Upload')->attachmentHandle = __CLASS__ . '::attachmentHandle';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $dir = new Text('dir', null, __TYPECHO_ROOT_DIR__ . '/usr/bucket', _t('Storage directory'), _t('Absolute path on the server; PHP must be able to write to it'));
        $dir->addRule('required', _t('The storage directory is required'));
        $form->addInput($dir);

        $url = new Text('url', null, Common::url('usr/bucket', Options::alloc()->siteUrl), _t('Public URL'), _t('Files in the storage directory must be reachable at this URL'));
        $url->addRule('required', _t('The public URL is required'));
        $form->addInput($url);
    }

    public static function personalConfig(Form $form)
    {
    }

    /**
     * Upload: save the file and return its information; return false on failure
     */
    public static function uploadHandle(array $file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!Upload::checkFileType($ext)) {
            return false;
        }

        $path = '/' . date('Y/m') . '/' . sprintf('%u', crc32(uniqid())) . '.' . $ext;

        return self::save($file, $path) ? self::info($file['name'], $path, $ext) : false;
    }

    /**
     * Replace: overwrite the original file with the new one; the extension must be the same
     */
    public static function modifyHandle(array $content, array $file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $path = $content['attachment']->path;

        if ($ext !== $content['attachment']->type || !self::save($file, $path)) {
            return false;
        }

        return self::info($content['attachment']->name, $path, $ext);
    }

    /**
     * Delete: delete the file and return whether it succeeded
     */
    public static function deleteHandle(array $content): bool
    {
        return @unlink(self::root() . $content['attachment']->path);
    }

    /**
     * URL: return the attachment's access URL
     */
    public static function attachmentHandle(Config $attachment): string
    {
        return Common::url($attachment->path, Options::alloc()->plugin('LocalBucket')->url);
    }

    private static function root(): string
    {
        return rtrim(Options::alloc()->plugin('LocalBucket')->dir, '/');
    }

    private static function save(array $file, string $path): bool
    {
        $target = self::root() . $path;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
            return false;
        }

        if (isset($file['tmp_name'])) {
            return move_uploaded_file($file['tmp_name'], $target);
        }

        // Uploads through XML-RPC have no temporary file; the content is in bits
        $bytes = $file['bytes'] ?? $file['bits'] ?? null;
        return $bytes !== null && file_put_contents($target, $bytes) !== false;
    }

    private static function info(string $name, string $path, string $ext): array
    {
        $target = self::root() . $path;

        return [
            'name' => $name,
            'path' => $path,
            'size' => filesize($target),
            'type' => $ext,
            'mime' => Common::mimeContentType($target),
        ];
    }
}
```

Key points:

- **Once you take over uploads, Typecho no longer checks the file type.** The example calls `\Widget\Upload::checkFileType($ext)`, which checks against the attachment types allowed in Basic Settings.
- You must implement `deleteHandle`; otherwise deleting an attachment in the admin only deletes the record, and the file stays in storage.
- When replacing an attachment, Typecho's built-in logic requires the old and new files to have the same extension; the example keeps this rule.
- `attachmentHandle` is called every time an attachment URL is read, so do not make network requests in it.
- Once activated, the URL, replacement and deletion of **every** attachment are handled as "access URL / storage directory + `path`", and this includes attachments uploaded before activation (whose `path` is `/usr/uploads/...`). Before activating on a site that already has attachments, copy the site's `usr/uploads` directory as is into the storage directory (so it becomes `<storage directory>/usr/uploads/...`). Otherwise those attachments cannot be accessed, and deleting an attachment only deletes the record while the original file stays. Likewise, when you later change the storage directory or the access URL, move the existing files to the new location yourself;
- The `path` in the file info is decided by the plugin and only that plugin understands it. **After the plugin is deactivated**, attachments it saved still get URLs the default Typecho way (site URL + `path`), which usually do not work. Warn users about this in the plugin description.

`Widget\Upload` also has a few notification hooks: `beforeUpload` / `beforeModify` receive the file info array before it is written to the database, and `upload` / `modify` receive the `Widget\Upload` object after it is written.

If a site owner only wants attachments served through a CDN without changing where they are stored, no plugin is needed; see [Attachment Storage Location](../../guide/operations/uploads-storage.md).

## Replacing the editor: richEditor {#editor}

The post and page writing screens trigger `richEditor` before outputting the editor scripts:

```php
\Typecho\Plugin::factory('admin/write-post.php')->trigger($plugged)->call('richEditor', $post);
if (!$plugged) {
    include 'editor-js.php';
}
```

Once it is registered, Typecho's built-in Markdown editor (toolbar, preview, shortcuts) is no longer loaded. The page only has a plain textarea with `id="text"`, and the plugin outputs scripts to turn it into a different editor. Posts and pages are two separate handles; register both.

When you replace the editor, you must provide two functions that other Typecho scripts depend on:

- `Typecho.insertFileToEditor(file, url, isImage)`: called when an attachment name is clicked in the attachment panel; inserts the attachment into the editor.
- `Typecho.uploadComplete(attachment)`: called after an attachment upload finishes.

Saving, autosave and preview all read the value of the `#text` textarea; if your editor keeps the content elsewhere, write it back to `#text` continuously. In addition, the built-in editor is responsible for triggering the `write` event on the form when the content changes (`$('#text').parents('form').trigger('write')`). Autosave and the "content has changed but not been saved" leave-page warning both depend on it, so a replacement editor has to trigger it itself.

The plugin below replaces the built-in editor with a plain textarea without toolbar or preview, and implements the two functions above and the `write` event:

```php title="usr/plugins/PlainEditor/Plugin.php"
<?php

namespace TypechoPlugin\PlainEditor;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Replaces the built-in Markdown editor with a plain text box without toolbar or preview
 *
 * @package PlainEditor
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/write-post.php')->richEditor = __CLASS__ . '::render';
        \Typecho\Plugin::factory('admin/write-page.php')->richEditor = __CLASS__ . '::render';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function render($post)
    {
        ?>
<script>
$(document).ready(function () {
    const textarea = $('#text');
    // When content is saved as Markdown, the form has a hidden field named "markdown"; without it, attachments are inserted as HTML
    const isMarkdown = textarea.parents('form').find('input[name="markdown"]').length > 0;

    // Notify the form when the content changes; autosave and the leave-page prompt depend on this event
    textarea.on('input', function () {
        textarea.parents('form').trigger('write');
    });

    // Called when "Insert" is clicked in the attachment panel; the editor must provide it
    Typecho.insertFileToEditor = function (file, url, isImage) {
        const el = textarea.get(0);
        const html = isMarkdown
            ? (isImage ? '![' + file + '](' + url + ')' : '[' + file + '](' + url + ')')
            : (isImage ? '<img src="' + url + '" alt="' + file + '" />' : '<a href="' + url + '">' + file + '</a>');
        const start = el.selectionStart, end = el.selectionEnd;

        el.value = el.value.substring(0, start) + html + el.value.substring(end);
        el.selectionStart = el.selectionEnd = start + html.length;
        textarea.trigger('input').focus();
    };

    // Insert automatically when the upload finishes
    Typecho.uploadComplete = function (attachment) {
        Typecho.insertFileToEditor(attachment.title, attachment.url, attachment.isImage);
    };
});
</script>
        <?php
    }
}
```

If you only want to **extend** the built-in editor (for example add a toolbar button), do not use `richEditor`. Use the `markdownEditor` hook of `admin/editor-js.php` instead: it is called in the middle of the built-in editor's initialization script and receives the current post, and the JavaScript the plugin outputs there can use the variables in that script. Once `richEditor` is registered, `markdownEditor` is no longer triggered.

## Login events {#login}

Login-related hooks live on two classes:

| Handle | Hook | When |
|---|---|---|
| `Widget_User` | `login` | Trigger; takes over the whole login verification and returns whether it succeeded |
| `Widget_User` | `hashValidate` | Trigger; takes over password verification, receives the plaintext password and the hash from the database, and returns whether they match |
| `Widget_User` | `loginSucceed` / `loginFail` | After an account is found and its password checked, on success / failure; routes such as the login form and XML-RPC all go through them; not triggered when the username does not exist or when `login` is taken over by a plugin |
| `Widget_Login` | `loginSuccess` / `loginFailure` | Only after a successful / failed login through the admin login form |
| `Widget_User` | `logout` | Trigger; takes over logout: once registered, Typecho no longer clears the login cookie |
| `Widget_Logout` | `logout` | After the user clicks "Logout" and the login state has been cleared |

To record logins made through the login form, XML-RPC and other channels, use `loginSucceed` / `loginFail` on `Widget_User`. They receive the `Widget\User` object, the login name, the **plaintext password**, whether the login is temporary (every XML-RPC request is a temporary login) and the expiry. Do not write the password to logs or send it anywhere.

These two hooks fire only after an account has been found and its password checked: when the username (or email) does not exist, the login fails immediately without triggering `loginFail`; when a plugin has registered the `login` hook to take over authentication, neither fires. To record every failure on the admin login form (including nonexistent usernames), also register `loginFailure` on `Widget_Login`; it receives four arguments: the `Widget\User` object, the login name, the plaintext password and whether "Remember me" was checked. On a wrong password, both it and `loginFail` fire.

```php title="usr/plugins/LoginLog/Plugin.php"
<?php

namespace TypechoPlugin\LoginLog;

use Typecho\Plugin\PluginInterface;
use Typecho\Request;
use Typecho\Widget\Helper\Form;
use Widget\User;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Writes successful logins and wrong-password attempts to the PHP error log
 *
 * @package LoginLog
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\User')->loginSucceed = __CLASS__ . '::succeed';
        \Typecho\Plugin::factory('Widget\User')->loginFail = __CLASS__ . '::fail';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function succeed(User $user, string $name, string $password, bool $temporarily, int $expire)
    {
        error_log(sprintf(
            '[LoginLog] success user=%s uid=%d ip=%s temporarily=%d',
            $user->name,
            $user->uid,
            Request::getInstance()->getIp(),
            $temporarily
        ));
    }

    public static function fail(User $user, string $name, string $password, bool $temporarily, int $expire)
    {
        error_log(sprintf(
            '[LoginLog] failure name=%s ip=%s',
            $name,
            Request::getInstance()->getIp()
        ));
    }
}
```

After activation, successful logins and wrong-password attempts through the login form and XML-RPC clients each leave a line in the PHP error log (attempts with a nonexistent username are not recorded), for example:

```text
[LoginLog] success user=admin uid=1 ip=203.0.113.5 temporarily=0
[LoginLog] failure name=admin ip=203.0.113.5
```

For the log location, see [Debugging and Troubleshooting](../../guide/operations/debugging.md#logs).
