---
title: 钩子列表
sidebar_position: 1
---

# 钩子列表

Typecho 1.3.0 中全部的插件钩子。注册回调的方法、三种触发方式的含义见[使用钩子](../plugin/hooks.md)。

## 怎样读这张表

- **句柄**：注册时传给 `Typecho\Plugin::factory()` 的名字。类句柄写成 1.3 的类名（如 `Widget\Base\Contents`）或表中的名字（如 `Widget_Abstract_Contents`）都可以，Typecho 会换算成表中的名字，见[句柄的名字](../plugin/hooks.md#句柄的名字)。
- **钩子**：赋值时的属性名，例如 `Typecho\Plugin::factory('Widget\Archive')->beforeRender = ...`。
- **类型**：
  - `call`：通知，回调按“参数”一栏的顺序收到参数，返回值一般不使用；
  - `filter`：过滤，回调收到的第一个参数就是要过滤的值（“参数”一栏的第一项），**必须返回**处理后的值；
  - `trigger + call` / `trigger + filter`：同上，并且只要有回调执行，Typecho 就**跳过自己的默认处理**，由插件接管；
  - `deprecated magic`：用已弃用的魔术方法触发，效果等同 `call`。
- **参数**：调用处传给钩子的表达式，原样摘自源码。`$this` 是触发钩子的对象本身，类型就是本节标题下的类；文件句柄中的 `$post`、`$page` 是正在编辑的文章或页面。
- **调用位置**：`文件:行号`，行号对应 Typecho 1.3.0。同一个钩子在多处触发时全部列出。

`self::pluginHandle()` 触发的钩子，句柄是对象实际所属的类。表中按源码中所在的类列出；`Widget\Contents\EditTrait` 中用 `static::pluginHandle()` 触发的 `getDefaultFieldItems` 和 `isFieldReadOnly` 分别列在使用它的 `Widget\Contents\Post\Edit` 和 `Widget\Contents\Page\Edit` 下。

## 总览

| 句柄 | 所在文件或类 | 钩子数 |
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

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `begin` | call | （无） | `admin/common.php:18` |

### `admin/editor-js.php` {#admin-editor-js-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `markdownEditor` | call | `$content` | `admin/editor-js.php:140` |

### `admin/footer.php` {#admin-footer-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `begin` | call | （无） | `admin/footer.php:2` |
| `end` | call | （无） | `admin/footer.php:7` |

### `admin/header.php` {#admin-header-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `header` | filter | `$header` | `admin/header.php:11` |

### `admin/menu.php` {#admin-menu-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `navBar` | call | （无） | `admin/menu.php:10` |

### `admin/profile.php` {#admin-profile-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `bottom` | call | （无） | `admin/profile.php:60` |

### `admin/theme-editor.php` {#admin-theme-editor-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `bottom` | call | `$files` | `admin/theme-editor.php:46` |

### `admin/write-js.php` {#admin-write-js-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `write` | call | （无） | `admin/write-js.php:2` |

### `admin/write-page.php` {#admin-write-page-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `advanceOption` | call | `$page` | `admin/write-page.php:177` |
| `bottom` | deprecated magic | `$page` | `admin/write-page.php:214` |
| `content` | call | `$page` | `admin/write-page.php:94` |
| `option` | call | `$page` | `admin/write-page.php:145` |
| `richEditor` | trigger + call | `$page` | `admin/write-page.php:207` |

### `admin/write-post.php` {#admin-write-post-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `advanceOption` | call | `$post` | `admin/write-post.php:173` |
| `bottom` | call | `$post` | `admin/write-post.php:211` |
| `content` | call | `$post` | `admin/write-post.php:78` |
| `option` | call | `$post` | `admin/write-post.php:119` |
| `richEditor` | trigger + call | `$post` | `admin/write-post.php:204` |

### `index.php` {#index-php}

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `begin` | call | （无） | `index.php:20` |
| `end` | call | （无） | `index.php:26` |

### `Widget_Abstract_Comments` {#widget-abstract-comments}

类：`Widget\Base\Comments`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `autoP` | trigger + filter | `$text` | `var/Widget/Base/Comments.php:366` |
| `content` | trigger + filter | `$text, $this` | `var/Widget/Base/Comments.php:486` |
| `contentEx` | filter | `$text, $this` | `var/Widget/Base/Comments.php:492` |
| `filter` | filter | `$row, $this` | `var/Widget/Base/Comments.php:247` |
| `gravatar` | trigger + call | `$size, $rating, $default, $this` | `var/Widget/Base/Comments.php:290` |
| `markdown` | trigger + filter | `$text` | `var/Widget/Base/Comments.php:349` |

### `Widget_Abstract_Contents` {#widget-abstract-contents}

类：`Widget\Base\Contents`

| 钩子 | 类型 | 参数 | 调用位置 |
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

类：`Widget\Base\Metas`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `filter` | filter | `$row, $this` | `var/Widget/Base/Metas.php:96` |

### `Widget_Abstract_Users` {#widget-abstract-users}

类：`Widget\Base\Users`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `filter` | filter | `$row, $this` | `var/Widget/Base/Users.php:67` |

### `Widget_Archive` {#widget-archive}

类：`Widget\Archive`

| 钩子 | 类型 | 参数 | 调用位置 |
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

类：`Widget\Backup`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `export` | call | `$fp` | `var/Widget/Backup.php:129` |
| `import` | deprecated magic | `$type, $header, $body` | `var/Widget/Backup.php:330` |

### `Widget_Comments_Archive` {#widget-comments-archive}

类：`Widget\Comments\Archive`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `cancelReply` | trigger + call | `$word, $this` | `var/Widget/Comments/Archive.php:457` |
| `listComments` | trigger + call | `$this->singleCommentOptions, $this` | `var/Widget/Comments/Archive.php:295` |
| `pageNav` | trigger + call | `$this->currentPage, $this->total, $this->options->commentsPageSize, $prev, $next, $splitPage, $splitWord, $template, $query` | `var/Widget/Comments/Archive.php:245` |
| `reply` | trigger + call | `$word, $this` | `var/Widget/Comments/Archive.php:411` |

### `Widget_Comments_Edit` {#widget-comments-edit}

类：`Widget\Comments\Edit`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `comment` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:356` |
| `delete` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:186` |
| `edit` | filter | `$comment, $this` | `var/Widget/Comments/Edit.php:303` |
| `finishComment` | call | `$this` | `var/Widget/Comments/Edit.php:366` |
| `finishDelete` | call | `$comment, $this` | `var/Widget/Comments/Edit.php:197` |
| `finishEdit` | call | `$this` | `var/Widget/Comments/Edit.php:313` |
| `mark` | call | `$comment, $this, $status` | `var/Widget/Comments/Edit.php:90` |

### `Widget_Contents_Attachment_Edit` {#widget-contents-attachment-edit}

类：`Widget\Contents\Attachment\Edit`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `delete` | call | `$post, $this` | `var/Widget/Contents/Attachment/Edit.php:306` |
| `finishDelete` | call | `$post, $this` | `var/Widget/Contents/Attachment/Edit.php:323` |

### `Widget_Contents_Page_Edit` {#widget-contents-page-edit}

类：`Widget\Contents\Page\Edit`

| 钩子 | 类型 | 参数 | 调用位置 |
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

类：`Widget\Contents\Page\Rows`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `listPages` | trigger + call | `$pageOptions, $this` | `var/Widget/Contents/Page/Rows.php:95` |

### `Widget_Contents_Post_Edit` {#widget-contents-post-edit}

类：`Widget\Contents\Post\Edit`

| 钩子 | 类型 | 参数 | 调用位置 |
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

类：`Widget\Feed`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `commentFeedItem` | trigger + call | `$feed->getType(), $comments` | `var/Widget/Feed.php:139` |
| `feedItem` | trigger + call | `$feed->getType(), $archive` | `var/Widget/Feed.php:168` |

### `Widget_Feedback` {#widget-feedback}

类：`Widget\Feedback`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `comment` | filter | `$comment, $this->content` | `var/Widget/Feedback.php:258` |
| `finishComment` | call | `$this` | `var/Widget/Feedback.php:271` |
| `finishTrackback` | call | `$this` | `var/Widget/Feedback.php:350` |
| `trackback` | filter | `$trackback, $this->content` | `var/Widget/Feedback.php:344` |

### `Widget_Login` {#widget-login}

类：`Widget\Login`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `loginFailure` | call | `$this->user, $this->request->get('name'), $this->request->get('password'), $this->request->is('remember=1')` | `var/Widget/Login.php:75` |
| `loginSuccess` | call | `$this->user, $this->request->get('name'), $this->request->get('password'), $this->request->is('remember=1')` | `var/Widget/Login.php:88` |

### `Widget_Logout` {#widget-logout}

类：`Widget\Logout`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `logout` | call | （无） | `var/Widget/Logout.php:33` |

### `Widget_Metas_Category_List` {#widget-metas-category-list}

类：`Widget\Metas\Category\Rows`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `listCategories` | trigger + call | `$categoryOptions, $this` | `var/Widget/Metas/Category/Rows.php:59` |

### `Widget_Register` {#widget-register}

类：`Widget\Register`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `finishRegister` | call | `$this` | `var/Widget/Register.php:92` |
| `register` | filter | `$dataStruct` | `var/Widget/Register.php:86` |

### `Widget_Upload` {#widget-upload}

类：`Widget\Upload`

| 钩子 | 类型 | 参数 | 调用位置 |
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

类：`Widget\User`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `hashValidate` | trigger + call | `$password, $user['password']` | `var/Widget/User.php:170` |
| `login` | trigger + call | `$name, $password, $temporarily, $expire` | `var/Widget/User.php:148` |
| `loginFail` | call | `$this, $name, $password, $temporarily, $expire` | `var/Widget/User.php:194` |
| `loginSucceed` | call | `$this, $name, $password, $temporarily, $expire` | `var/Widget/User.php:189` |
| `logout` | trigger + call | （无） | `var/Widget/User.php:125` |
| `simpleLoginFail` | call | `$this` | `var/Widget/User.php:241` |
| `simpleLoginSucceed` | call | `$this, $user` | `var/Widget/User.php:253` |

### `Widget_XmlRpc` {#widget-xmlrpc}

类：`Widget\XmlRpc`

| 钩子 | 类型 | 参数 | 调用位置 |
|---|---|---|---|
| `finishPingback` | call | `$this` | `var/Widget/XmlRpc.php:1728` |
| `pingback` | filter | `$pingback, $post` | `var/Widget/XmlRpc.php:1722` |
| `textFilter` | filter | `$input['text'], $this` | `var/Widget/XmlRpc.php:342` |
| `upload` | call | `$this` | `var/Widget/XmlRpc.php:1389` |

## 动态钩子

除上面写明名字的钩子外，还有三类钩子的名字在运行时拼出：

| 钩子名 | 句柄 | 何时触发 | 回调收到 | 调用位置 |
|---|---|---|---|---|
| `call` + 方法名（首字母大写），如 `callWordCount` | 对象实际所属的类，如模板中的 `Widget_Archive` | 调用 Widget 对象上一个不存在的方法 `$widget->wordCount()` 时。类型为 trigger + call：没有回调时输出 `$widget->wordCount` 的值 | Widget 对象、参数数组 | `var/Typecho/Widget.php:431`（`Typecho\Widget::__call()`） |
| `___` + 属性名，如 `___readingTime` | 同上 | 读取 Widget 对象上一个不存在的属性 `$widget->readingTime` 时（不是数据库字段，也没有 `___readingTime()` 方法）。类型为 trigger + call：有回调时，回调的返回值就是属性值，否则为 `null` | Widget 对象 | `var/Typecho/Widget.php:467`（`Typecho\Widget::__get()`） |
| `Helper::requestService()` 的任务名 | `Widget_Service` | 异步请求 `/action/service?do=async` 中，逐个执行安排好的任务 | `requestService()` 的其余参数 | `var/Widget/Service.php:283`（`Widget\Service::asyncHandle()`） |

用法见[动态钩子](../plugin/hooks.md#动态钩子)和 [HTTP 请求与异步任务](../plugin/http-async.md)。

## 补充说明

- `Widget_Upload` 的 `attachmentDataHandle`：1.3.0 中 Typecho 自己没有调用 `Widget\Upload::attachmentDataHandle()`，注册这个钩子不会被触发。
- `admin/write-post.php` / `admin/write-page.php` 的 `richEditor`：注册后自带的编辑器不再加载，`admin/editor-js.php` 的 `markdownEditor` 也就不会被触发，见[替换编辑器](../plugin/patterns.md#editor)。
- `Widget_Login` 的 `loginSuccess` / `loginFailure` 只在后台登录表单中触发；`Widget_User` 的 `loginSucceed` / `loginFail` 在所有登录途径（包括 XML-RPC）中触发。两组钩子都会收到明文密码。
- `index.php` 的 `end` 只在页面正常输出结束时触发；以跳转、JSON 等方式结束的请求（如所有 Action）不会触发它。
- `Widget_Abstract_Contents` 的 `title`、`content`、`contentEx`、`excerpt`、`excerptEx` 对所有内容对象生效，包括文章、页面、附件以及各种文章列表；`Widget_Abstract_Comments` 的同名钩子对所有评论对象生效。
