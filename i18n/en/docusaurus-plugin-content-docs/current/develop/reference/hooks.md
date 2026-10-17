---
title: Hook Reference
sidebar_position: 1
translation: machine
source_commit: 1d967a0c62b5a260a8146b63efb3c9323aa91cc3
---

# Hook Reference

All plugin hooks in Typecho 1.3.0. For how to register callbacks and what the three trigger types mean, see [Using Hooks](../plugin/hooks.md).

## How to read the tables

- **Handle**: the name you pass to `Typecho\Plugin::factory()` when registering. For class handles you can use either the 1.3 class name (such as `Widget\Base\Contents`) or the name in the table (such as `Widget_Abstract_Contents`); Typecho converts it to the name in the table. See [Handle names](../plugin/hooks.md#handle-names).
- **Hook**: the property name you assign to, for example `Typecho\Plugin::factory('Widget\Archive')->beforeRender = ...`.
- **Type**:
  - `call`: the callback receives the arguments in the order of the "Arguments" column. `call` returns the return value of the last callback; most `call` hooks do not use it, and those that do are listed under [Return values](#return-values) below;
  - `filter`: a filter. The first argument the callback receives is the value being filtered (the first item in the "Arguments" column), and the callback **must return** the processed value;
  - `trigger + call` / `trigger + filter`: same as above; in addition the calling code learns whether any callback ran and decides what happens next. In most cases, when a callback has run, Typecho **skips its own default handling** and uses the callback's result instead; for the exceptions see [Return values](#return-values);
  - `deprecated magic`: triggered through a deprecated magic method; behaves the same as `call`.
- **Arguments**: the expressions passed to the hook at the call site, copied verbatim from the source. `$this` is the object that triggers the hook, whose type is the class under the section heading; in file handles, `$post` and `$page` are the post or page being edited.
- **Location**: `file:line`, with line numbers matching Typecho 1.3.0. When the same hook is triggered in several places, all of them are listed.

For hooks triggered by `self::pluginHandle()`, the handle is the class the object actually belongs to. The tables list them under the class where they appear in the source; `getDefaultFieldItems` and `isFieldReadOnly`, which `Widget\Contents\EditTrait` triggers with `static::pluginHandle()`, are listed under `Widget\Contents\Post\Edit` and `Widget\Contents\Page\Edit`, which use the trait.

## Return values {#return-values}

Callbacks for the following hooks must return the stated value; otherwise login fails, a type error is raised or the query breaks:

| Handle | Hook | The callback should return |
|---|---|---|
| `Widget_User` | `login` | `bool`, used as the login result; Typecho no longer checks the user name and password itself |
| `Widget_User` | `hashValidate` | `bool`, whether the password is correct |
| `Widget_Archive` | `select` | `Typecho\Db\Query`; `Widget\Archive` adds its conditions, ordering and pagination to it |
| `Widget_Upload` | `attachmentHandle` | `string`, the URL of the attachment |
| `Widget_Upload` | `attachmentDataHandle` | `string`, the contents of the attachment file |
| `Widget_Upload` | `deleteHandle` | `bool`, whether the deletion succeeded |
| `Widget_Upload` | `uploadHandle` / `modifyHandle` | An array with the same structure as the default implementation's (`name`, `path`, `size`, `type`, `mime`), or `false` on failure |
| `Widget_Feed` | `feedItem` / `commentFeedItem` | `string` or `null`. Typecho still generates the item as usual; the returned string is inserted as raw XML at the end of that item |
| `Widget_Abstract_Contents`, `Widget_Contents_Post_Edit`, `Widget_Contents_Page_Edit` | `isFieldReadOnly` | `bool`. Returning `true` skips this custom field, returning `false` handles it as usual; registering a callback that does not return `true` does not make the field read-only |

Every `filter` hook must return the processed value; they are not listed one by one.

## Overview

| Handle | File or class | Hooks |
|---|---|---|
| [`admin/common.php`](#admin-common-php) | `admin/common.php` | 1 |
| [`admin/editor-js.php`](#admin-editor-js-php) | `admin/editor-js.php` | 1 |
| [`admin/footer.php`](#admin-footer-php) | `admin/footer.php` | 2 |
| [`admin/header.php`](#admin-header-php) | `admin/header.php` | 1 |
| [`admin/menu.php`](#admin-menu-php) | `admin/menu.php` | 1 |
| [`admin/profile.php`](#admin-profile-php) | `admin/profile.php` | 1 |
| [`admin/theme-editor.php`](#admin-theme-editor-php) | `admin/theme-editor.php` | 1 |
| [`admin/write-js.php`](#admin-write-js-php) | `admin/write-js.php` | 1 |
| [`admin/write-page.php`](#admin-write-page-php) | `admin/write-page.php` | 5 |
| [`admin/write-post.php`](#admin-write-post-php) | `admin/write-post.php` | 5 |
| [`index.php`](#index-php) | `index.php` | 2 |
| [`Widget_Abstract_Comments`](#widget-abstract-comments) | `Widget\Base\Comments` | 6 |
| [`Widget_Abstract_Contents`](#widget-abstract-contents) | `Widget\Base\Contents` | 10 |
| [`Widget_Abstract_Metas`](#widget-abstract-metas) | `Widget\Base\Metas` | 1 |
| [`Widget_Abstract_Users`](#widget-abstract-users) | `Widget\Base\Users` | 1 |
| [`Widget_Archive`](#widget-archive) | `Widget\Archive` | 19 |
| [`Widget_Backup`](#widget-backup) | `Widget\Backup` | 2 |
| [`Widget_Comments_Archive`](#widget-comments-archive) | `Widget\Comments\Archive` | 4 |
| [`Widget_Comments_Edit`](#widget-comments-edit) | `Widget\Comments\Edit` | 7 |
| [`Widget_Contents_Attachment_Edit`](#widget-contents-attachment-edit) | `Widget\Contents\Attachment\Edit` | 2 |
| [`Widget_Contents_Page_Edit`](#widget-contents-page-edit) | `Widget\Contents\Page\Edit` | 9 |
| [`Widget_Contents_Page_List`](#widget-contents-page-list) | `Widget\Contents\Page\Rows` | 1 |
| [`Widget_Contents_Post_Edit`](#widget-contents-post-edit) | `Widget\Contents\Post\Edit` | 9 |
| [`Widget_Feed`](#widget-feed) | `Widget\Feed` | 2 |
| [`Widget_Feedback`](#widget-feedback) | `Widget\Feedback` | 4 |
| [`Widget_Login`](#widget-login) | `Widget\Login` | 2 |
| [`Widget_Logout`](#widget-logout) | `Widget\Logout` | 1 |
| [`Widget_Metas_Category_List`](#widget-metas-category-list) | `Widget\Metas\Category\Rows` | 1 |
| [`Widget_Register`](#widget-register) | `Widget\Register` | 2 |
| [`Widget_Upload`](#widget-upload) | `Widget\Upload` | 9 |
| [`Widget_User`](#widget-user) | `Widget\User` | 7 |
| [`Widget_XmlRpc`](#widget-xmlrpc) | `Widget\XmlRpc` | 4 |

### `admin/common.php` {#admin-common-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `begin` | call | (none) | `admin/common.php:18` |

### `admin/editor-js.php` {#admin-editor-js-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `markdownEditor` | call | `$content` | `admin/editor-js.php:140` |

### `admin/footer.php` {#admin-footer-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `begin` | call | (none) | `admin/footer.php:2` |
| `end` | call | (none) | `admin/footer.php:7` |

### `admin/header.php` {#admin-header-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `header` | filter | `$header` | `admin/header.php:11` |

### `admin/menu.php` {#admin-menu-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `navBar` | call | (none) | `admin/menu.php:10` |

### `admin/profile.php` {#admin-profile-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `bottom` | call | (none) | `admin/profile.php:60` |

### `admin/theme-editor.php` {#admin-theme-editor-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `bottom` | call | `$files` | `admin/theme-editor.php:46` |

### `admin/write-js.php` {#admin-write-js-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `write` | call | (none) | `admin/write-js.php:2` |

### `admin/write-page.php` {#admin-write-page-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `advanceOption` | call | `$page` | `admin/write-page.php:177` |
| `bottom` | deprecated magic | `$page` | `admin/write-page.php:214` |
| `content` | call | `$page` | `admin/write-page.php:94` |
| `option` | call | `$page` | `admin/write-page.php:145` |
| `richEditor` | trigger + call | `$page` | `admin/write-page.php:207` |

### `admin/write-post.php` {#admin-write-post-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `advanceOption` | call | `$post` | `admin/write-post.php:173` |
| `bottom` | call | `$post` | `admin/write-post.php:211` |
| `content` | call | `$post` | `admin/write-post.php:78` |
| `option` | call | `$post` | `admin/write-post.php:119` |
| `richEditor` | trigger + call | `$post` | `admin/write-post.php:204` |

### `index.php` {#index-php}

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `begin` | call | (none) | `index.php:20` |
| `end` | call | (none) | `index.php:26` |

### `Widget_Abstract_Comments` {#widget-abstract-comments}

Class: `Widget\Base\Comments`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `autoP` | trigger + filter | `$text` | `var/Widget/Base/Comments.php:366` |
| `content` | trigger + filter | `$text, $this` | `var/Widget/Base/Comments.php:486` |
| `contentEx` | filter | `$text, $this` | `var/Widget/Base/Comments.php:492` |
| `filter` | filter | `$row, $this` | `var/Widget/Base/Comments.php:247` |
| `gravatar` | trigger + call | `$size, $rating, $default, $this` | `var/Widget/Base/Comments.php:290` |
| `markdown` | trigger + filter | `$text` | `var/Widget/Base/Comments.php:349` |

### `Widget_Abstract_Contents` {#widget-abstract-contents}

Class: `Widget\Base\Contents`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `autoP` | trigger + filter | `$text` | `var/Widget/Base/Contents.php:826` |
| `content` | trigger + filter | `$this->text, $this` | `var/Widget/Base/Contents.php:852` |
| `contentEx` | filter | `$content, $this` | `var/Widget/Base/Contents.php:859` |
| `excerpt` | filter | `$this->content, $this` | `var/Widget/Base/Contents.php:783` |
| `excerptEx` | filter | `$excerpt, $this` | `var/Widget/Base/Contents.php:786` |
| `filter` | filter | `$row, $this` | `var/Widget/Base/Contents.php:362` |
| `getDefaultFieldItems` | call | `$layout` | `var/Widget/Contents/EditTrait.php:230` |
| `isFieldReadOnly` | trigger + call | `$name`<br/>`$row['name']` | `var/Widget/Contents/EditTrait.php:59`<br/>`var/Widget/Contents/EditTrait.php:193`<br/>`var/Widget/Contents/EditTrait.php:251` |
| `markdown` | trigger + filter | `$text` | `var/Widget/Base/Contents.php:809` |
| `title` | trigger + filter | `$this->title, $this` | `var/Widget/Base/Contents.php:407` |

### `Widget_Abstract_Metas` {#widget-abstract-metas}

Class: `Widget\Base\Metas`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `filter` | filter | `$row, $this` | `var/Widget/Base/Metas.php:96` |

### `Widget_Abstract_Users` {#widget-abstract-users}

Class: `Widget\Base\Users`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `filter` | filter | `$row, $this` | `var/Widget/Base/Users.php:67` |

### `Widget_Archive` {#widget-archive}

Class: `Widget\Archive`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `afterRender` | call | `$this` | `var/Widget/Archive.php:1393` |
| `authorHandle` | call | `$this, $select` | `var/Widget/Archive.php:1952` |
| `beforeRender` | call | `$this` | `var/Widget/Archive.php:1387` |
| `categoryHandle` | call | `$this, $select` | `var/Widget/Archive.php:1822` |
| `dateHandle` | call | `$this, $select` | `var/Widget/Archive.php:2055` |
| `error404Handle` | call | `$this, $select` | `var/Widget/Archive.php:1573` |
| `footer` | call | `$this` | `var/Widget/Archive.php:1246` |
| `handle` | call | `$this->parameter->type, $this, $select` | `var/Widget/Archive.php:665` |
| `handleInit` | call | `$this, $select` | `var/Widget/Archive.php:651` |
| `header` | call | `$header, $this` | `var/Widget/Archive.php:1238` |
| `headerOptions` | filter | `$allows, $this` | `var/Widget/Archive.php:1015` |
| `indexHandle` | call | `$this, $select` | `var/Widget/Archive.php:1529` |
| `pageNav` | trigger + call | `$this->currentPage, $total, $this->parameter->pageSize, $prev, $next, $splitPage, $splitWord, $template, $query` | `var/Widget/Archive.php:765` |
| `query` | trigger + call | `$this, $select` | `var/Widget/Archive.php:1421` |
| `search` | trigger + call | `$keywords, $this` | `var/Widget/Archive.php:2071` |
| `searchHandle` | call | `$this, $select` | `var/Widget/Archive.php:2137` |
| `select` | trigger + call | `$this` | `var/Widget/Archive.php:610` |
| `singleHandle` | call | `$this, $select` | `var/Widget/Archive.php:1731` |
| `tagHandle` | call | `$this, $select` | `var/Widget/Archive.php:1896` |

### `Widget_Backup` {#widget-backup}

Class: `Widget\Backup`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `export` | call | `$fp` | `var/Widget/Backup.php:129` |
| `import` | deprecated magic | `$type, $header, $body` | `var/Widget/Backup.php:330` |

### `Widget_Comments_Archive` {#widget-comments-archive}

Class: `Widget\Comments\Archive`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `cancelReply` | trigger + call | `$word, $this` | `var/Widget/Comments/Archive.php:457` |
| `listComments` | trigger + call | `$this->singleCommentOptions, $this` | `var/Widget/Comments/Archive.php:295` |
| `pageNav` | trigger + call | `$this->currentPage, $this->total, $this->options->commentsPageSize, $prev, $next, $splitPage, $splitWord, $template, $query` | `var/Widget/Comments/Archive.php:245` |
| `reply` | trigger + call | `$word, $this` | `var/Widget/Comments/Archive.php:411` |

### `Widget_Comments_Edit` {#widget-comments-edit}

Class: `Widget\Comments\Edit`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `comment` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:356` |
| `delete` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:186` |
| `edit` | filter | `$comment, $this` | `var/Widget/Comments/Edit.php:303` |
| `finishComment` | call | `$this` | `var/Widget/Comments/Edit.php:366` |
| `finishDelete` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:197` |
| `finishEdit` | call | `$this` | `var/Widget/Comments/Edit.php:313` |
| `mark` | call | `$comment, $this, $status` | `var/Widget/Comments/Edit.php:90` |

### `Widget_Contents_Attachment_Edit` {#widget-contents-attachment-edit}

Class: `Widget\Contents\Attachment\Edit`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `delete` | call | `$post, $this` | `var/Widget/Contents/Attachment/Edit.php:306` |
| `finishDelete` | call | `$post, $this` | `var/Widget/Contents/Attachment/Edit.php:323` |

### `Widget_Contents_Page_Edit` {#widget-contents-page-edit}

Class: `Widget\Contents\Page\Edit`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `delete` | call | `$page, $this` | `var/Widget/Contents/Page/Edit.php:192` |
| `finishDelete` | call | `$page, $this` | `var/Widget/Contents/Page/Edit.php:232` |
| `finishMark` | call | `$status, $page, $this` | `var/Widget/Contents/Page/Edit.php:161` |
| `finishPublish` | call | `$contents, $this` | `var/Widget/Contents/Page/Edit.php:77` |
| `finishSave` | call | `$contents, $this` | `var/Widget/Contents/Page/Edit.php:100` |
| `getDefaultFieldItems` | call | `$layout` | `var/Widget/Contents/EditTrait.php:231` |
| `isFieldReadOnly` | trigger + call | `$row['name']` | `var/Widget/Contents/EditTrait.php:200` |
| `mark` | call | `$status, $page, $this` | `var/Widget/Contents/Page/Edit.php:145` |
| `write` | filter | `$contents, $this` | `var/Widget/Contents/Page/Edit.php:69` |

### `Widget_Contents_Page_List` {#widget-contents-page-list}

Class: `Widget\Contents\Page\Rows`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `listPages` | trigger + call | `$pageOptions, $this` | `var/Widget/Contents/Page/Rows.php:95` |

### `Widget_Contents_Post_Edit` {#widget-contents-post-edit}

Class: `Widget\Contents\Post\Edit`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `delete` | call | `$post, $this` | `var/Widget/Contents/Post/Edit.php:243` |
| `finishDelete` | call | `$post, $this` | `var/Widget/Contents/Post/Edit.php:281` |
| `finishMark` | call | `$status, $post, $this` | `var/Widget/Contents/Post/Edit.php:212` |
| `finishPublish` | call | `$contents, $this` | `var/Widget/Contents/Post/Edit.php:74` |
| `finishSave` | call | `$contents, $this` | `var/Widget/Contents/Post/Edit.php:101` |
| `getDefaultFieldItems` | call | `$layout` | `var/Widget/Contents/EditTrait.php:231` |
| `isFieldReadOnly` | trigger + call | `$row['name']` | `var/Widget/Contents/EditTrait.php:200` |
| `mark` | call | `$status, $post, $this` | `var/Widget/Contents/Post/Edit.php:167` |
| `write` | filter | `$contents, $this` | `var/Widget/Contents/Post/Edit.php:66` |

### `Widget_Feed` {#widget-feed}

Class: `Widget\Feed`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `commentFeedItem` | trigger + call | `$feed->getType(), $comments` | `var/Widget/Feed.php:139` |
| `feedItem` | trigger + call | `$feed->getType(), $archive` | `var/Widget/Feed.php:168` |

### `Widget_Feedback` {#widget-feedback}

Class: `Widget\Feedback`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `comment` | filter | `$comment, $this->content` | `var/Widget/Feedback.php:258` |
| `finishComment` | call | `$this` | `var/Widget/Feedback.php:271` |
| `finishTrackback` | call | `$this` | `var/Widget/Feedback.php:350` |
| `trackback` | filter | `$trackback, $this->content` | `var/Widget/Feedback.php:344` |

### `Widget_Login` {#widget-login}

Class: `Widget\Login`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `loginFailure` | call | `$this->user, $this->request->get('name'), $this->request->get('password'), $this->request->is('remember=1')` | `var/Widget/Login.php:75` |
| `loginSuccess` | call | `$this->user, $this->request->get('name'), $this->request->get('password'), $this->request->is('remember=1')` | `var/Widget/Login.php:88` |

### `Widget_Logout` {#widget-logout}

Class: `Widget\Logout`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `logout` | call | (none) | `var/Widget/Logout.php:33` |

### `Widget_Metas_Category_List` {#widget-metas-category-list}

Class: `Widget\Metas\Category\Rows`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `listCategories` | trigger + call | `$categoryOptions, $this` | `var/Widget/Metas/Category/Rows.php:59` |

### `Widget_Register` {#widget-register}

Class: `Widget\Register`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `finishRegister` | call | `$this` | `var/Widget/Register.php:92` |
| `register` | filter | `$dataStruct` | `var/Widget/Register.php:86` |

### `Widget_Upload` {#widget-upload}

Class: `Widget\Upload`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `attachmentDataHandle` | trigger + call | `$content` | `var/Widget/Upload.php:72` |
| `attachmentHandle` | trigger + call | `$attachment` | `var/Widget/Upload.php:52` |
| `beforeModify` | call | `$result` | `var/Widget/Upload.php:139` |
| `beforeUpload` | call | `$result` | `var/Widget/Upload.php:306` |
| `deleteHandle` | trigger + call | `$content` | `var/Widget/Upload.php:36` |
| `modify` | call | `$this` | `var/Widget/Upload.php:149` |
| `modifyHandle` | trigger + call | `$content, $file` | `var/Widget/Upload.php:181` |
| `upload` | call | `$this` | `var/Widget/Upload.php:333` |
| `uploadHandle` | trigger + call | `$file` | `var/Widget/Upload.php:364` |

### `Widget_User` {#widget-user}

Class: `Widget\User`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `hashValidate` | trigger + call | `$password, $user['password']` | `var/Widget/User.php:170` |
| `login` | trigger + call | `$name, $password, $temporarily, $expire` | `var/Widget/User.php:148` |
| `loginFail` | call | `$this, $name, $password, $temporarily, $expire` | `var/Widget/User.php:194` |
| `loginSucceed` | call | `$this, $name, $password, $temporarily, $expire` | `var/Widget/User.php:189` |
| `logout` | trigger + call | (none) | `var/Widget/User.php:125` |
| `simpleLoginFail` | call | `$this` | `var/Widget/User.php:241` |
| `simpleLoginSucceed` | call | `$this, $user` | `var/Widget/User.php:253` |

### `Widget_XmlRpc` {#widget-xmlrpc}

Class: `Widget\XmlRpc`

| Hook | Type | Arguments | Location |
|---|---|---|---|
| `finishPingback` | call | `$this` | `var/Widget/XmlRpc.php:1728` |
| `pingback` | filter | `$pingback, $post` | `var/Widget/XmlRpc.php:1722` |
| `textFilter` | filter | `$input['text'], $this` | `var/Widget/XmlRpc.php:342` |
| `upload` | call | `$this` | `var/Widget/XmlRpc.php:1389` |

## Dynamic hooks

Besides the hooks named above, there are three kinds of hooks whose names are built at runtime:

| Hook name | Handle | When it fires | Callback receives | Location |
|---|---|---|---|---|
| `call` + method name (first letter uppercase), such as `callWordCount` | The class the object actually belongs to, such as `Widget_Archive` in a template | When you call a method that does not exist on a widget object, `$widget->wordCount()`. Type trigger + call: without a callback, the value of `$widget->wordCount` is output | The widget object, the array of arguments | `var/Typecho/Widget.php:431` (`Typecho\Widget::__call()`) |
| `___` + property name, such as `___readingTime` | Same as above | When you read a property that does not exist on a widget object, `$widget->readingTime` (it is not a database field and there is no `___readingTime()` method). Type trigger + call: with a callback, its return value is the property value; otherwise the value is `null` | The widget object | `var/Typecho/Widget.php:467` (`Typecho\Widget::__get()`) |
| The task name passed to `Helper::requestService()` | `Widget_Service` | In the async request `/action/service?do=async`, as each scheduled task runs | The remaining arguments of `requestService()` | `var/Widget/Service.php:283` (`Widget\Service::asyncHandle()`) |

For usage, see [Dynamic hooks](../plugin/hooks.md#dynamic-hooks) and [HTTP Requests and Async Tasks](../plugin/http-async.md).

## Additional notes

- `attachmentDataHandle` of `Widget_Upload`: in 1.3.0 Typecho itself never calls `Widget\Upload::attachmentDataHandle()`, so registering this hook has no effect.
- `richEditor` of `admin/write-post.php` / `admin/write-page.php`: once registered, the built-in editor is no longer loaded, so `markdownEditor` of `admin/editor-js.php` is not triggered either. See [Replacing the editor](../plugin/patterns.md#editor).
- `loginSuccess` / `loginFailure` of `Widget_Login` fire only for the admin login form; `loginSucceed` / `loginFail` of `Widget_User` fire for every login path (including XML-RPC). Both pairs receive the plaintext password.
- `end` of `index.php` fires only when the page finishes output normally; requests that end with a redirect, JSON, and so on (such as all actions) do not trigger it.
- `title`, `content`, `contentEx`, `excerpt` and `excerptEx` of `Widget_Abstract_Contents` apply to all content objects, including posts, pages, attachments and every kind of post list; the hooks with the same names on `Widget_Abstract_Comments` apply to all comment objects.
