---
title: Content, Comment, Category and User Objects
sidebar_position: 3
translation: machine
source_commit: c4fe96854858d8912390c7dfe6c2eab21c87ac5c
---

# Content, Comment, Category and User Objects

Every row of data you loop over in a template is a widget object: posts and pages are subclasses of `Widget\Base\Contents`, comments of `Widget\Base\Comments`, categories and tags of `Widget\Base\Metas`, and users of `Widget\Base\Users`. This page lists the methods, magic properties and database fields available on these four kinds of objects.

## How to read them

- **Fields and magic properties**: read values with `$posts->title`, `$posts->permalink`; magic properties are computed and cached on first read.
- **Output**: most methods in the tables output directly. Calling a method with the same name as any field or property also outputs its value, for example `$posts->permalink()`, `$comments->coid()`.
- `options`, `user`, `db` and similar members of these objects are protected. In templates you can only reach them through `$this->options` (that is, `Widget\Archive`), not as `$posts->options`.

## Members shared by all widgets {#widget}

`Typecho\Widget` is the base class of all widgets:

| Method | Description |
|---|---|
| `static alias(string $widgetClass, string $aliasClass)` | Widget alias |
| `static alloc($params = null, $request = null, $disableSandboxOrCallback = true): Widget` | alloc widget instance |
| `static allocWithAlias(?string $alias, $params = null, $request = null, $disableSandboxOrCallback = true): Widget` | alloc widget instance with alias |
| `alt(...$args)` | Outputs based on the remainder |
| `altBy(int $current, ...$args)` | Outputs based on the remainder |
| `static destory(string $alias)` | **Deprecated**. Releases the widget |
| `static destroy(?string $alias = null)` | Releases the widget |
| `execute()` | execute function. |
| `have(): bool` | Returns whether the stack is non-empty |
| `next()` | Returns the value of each row in the stack |
| `on(bool $condition)` | Triggers on post |
| `parse(string $template)` | Formats and parses all data in the stack |
| `push(array $value)` | Pushes a row's values onto the stack |
| `pushAll(array $values)` | Pushes all rows' values onto the stack |
| `template(string $template): string` | Renders with a template |
| `to(&$variable): Widget` | Assigns the object itself to a variable |
| `toArray($column): array` | Collects the given column of all rows into an array |
| `toColumn($column)` | Gets the given column of the current row |
| `static widget(string $alias, $params = null, $request = null, $disableSandboxOrCallback = true): Widget` | Factory method that registers the class instance in a static list |

| Property | Type | Description |
|---|---|---|
| `length` | `int` | Length of the output data |
| `parameter` | `Config` | Parameters passed to `alloc()` (`Typecho\Config`) |
| `request` | `Request` | Request object |
| `response` | `Response` | Response object |
| `sequence` | `int` | Output sequence number |

- `next()` returns `false` when the loop reaches the end and resets the pointer to the beginning, so you can loop over the same object more than once.
- `alt('odd', 'even')` outputs its arguments in turn based on the current sequence number; it is commonly used for alternating row styles.
- `alloc()` caches instances by class name, so a second `alloc()` of the same class returns the first result. When you need several instances, use `allocWithAlias()`; see [Calling Widgets in Templates](../theme/widgets.md).

## Content: Widget\Base\Contents {#contents}

Posts, pages, attachments, `Widget\Archive` itself, and the objects returned by `Contents\Post\Recent`, `Contents\Page\Rows` and `related()` are all subclasses of this class.

### Fields

| Field | Type | Description |
|---|---|---|
| `cid` | int | Content ID |
| `title` | string | Title |
| `slug` | string | Slug |
| `created` | int | Publish time (timestamp) |
| `modified` | int | Last modified time (timestamp) |
| `text` | string | Raw body text |
| `order` | int | Sort order (the "Page Order" of a page; the order of an attachment within its post) |
| `authorId` | int | User ID of the author |
| `template` | string | File name of the custom template chosen for a page |
| `type` | string | `post`, `page`, `attachment`, `post_draft`, `page_draft`, `revision` |
| `status` | string | `publish`, `hidden`, `private`, `waiting` |
| `password` | string | Content password; an empty string when there is none |
| `commentsNum` | int | Number of approved comments (including trackbacks) |
| `allowComment` / `allowPing` / `allowFeed` | `'1'` / `'0'` | Allow comments, allow pings, include in feeds |
| `parent` | int | Parent ID (of a child page, of the content an attachment belongs to, of the content a revision belongs to) |
| `date` | `Typecho\Date` | Generated from `created`, `$posts->date->format('Y-m-d')` |

### Methods

| Method | Description |
|---|---|
| `allow(...$permissions): bool` | Gets post permissions |
| `applySlug(?string $slug, $cid, string $title = ''): string` | Applies a slug to the content |
| `author(string $item = 'screenName')` | Outputs the current author |
| `category(string $split = ',', bool $link = true, ?string $default = null)` | Outputs the post categories |
| `commentsNum(...$args)` | Outputs the post comment count |
| `content($more = false)` | Outputs the post content |
| `date(?string $format = null)` | Outputs the post publish date |
| `delete(Query $condition): int` | Deletes content |
| `directory(string $split = '/', bool $link = true, ?string $default = null)` | Outputs the post's multi-level categories |
| `excerpt(int $length = 100, string $trim = '...')` | Outputs the post excerpt |
| `filter(array $row): array` | Generic filter |
| `getPrimaryKey(): string` | Returns the primary key field name |
| `getRouterParam(string $key): string` | Returns the route parameter value used to build URLs |
| `getTemplates(): array` | Gets all current custom templates |
| `insert(array $rows): int` | Inserts content |
| `isWriteable(Query $condition): bool` | Whether the content can be modified |
| `push(array $value): array` | Pushes each row's values onto the stack |
| `select(...$fields): Query` | Gets the query object |
| `size(Query $condition): int` | Counts content matching the condition |
| `tags(string $split = ',', bool $link = true, ?string $default = null)` | Outputs the post tags |
| `title(int $length = 0, string $trim = '...')` | Outputs the title |
| `update(array $rows, Query $condition): int` | Updates content |

Arguments of the common methods:

- `title($length = 0, $trim = '...')`: truncates the title when `$length` is greater than 0;
- `content($more = false)`: when you pass text, posts containing `<!--more-->` output only the excerpt followed by a "read more" link;
- `excerpt($length = 100, $trim = '...')`: outputs a plain-text excerpt;
- `date($format = null)`: without a format, uses the post date format from "Settings → Reading";
- `category($split = ',', $link = true, $default = null)`, `tags(...)`, `directory($split = '/', ...)`: output categories, tags and multi-level categories; `$default` is the text shown when there are none;
- `author($item = 'screenName')`: outputs one field of the author, for example `author('url')`;
- `commentsNum('0 comments', '1 comment', '%d comments')`: picks an argument based on the comment count, replacing `%d` with the number;
- `allow('comment')` / `allow('ping')` / `allow('feed')`: return whether it is allowed, taking password protection and the "auto-close comments" setting into account.

`insert()`, `update()`, `delete()`, `select()`, `size()`, `push()`, `filter()`, `isWriteable()` and `applySlug()` are for Typecho and plugins to work with the database.

### Magic properties

| Property | Type | Description |
|---|---|---|
| `attachment` | `?Config` | Attachment info (attachments only): `name`, `path`, `size`, `type`, `mime`, `url`, `isImage`, etc. |
| `author` | `Users` | Post author |
| `categories` | `array` | Array of categories; each item has `mid`, `name`, `slug`, `permalink`, etc. |
| `category` | `string` | Slug of the first category |
| `commentUrl` | `string` | Comment URL |
| `content` | `?string` | Gets the post content |
| `dateWord` | `string` | Gets the human-readable date |
| `day` | `string` | Publish day (two digits) |
| `description` | `?string` | Short plain-text description of the post |
| `directory` | `array` | Multi-level directory structure |
| `excerpt` | `?string` | Gets the post content excerpt |
| `feedAtomUrl` | `string` | Atom feed URL |
| `feedRssUrl` | `string` | RSS 1.0 feed URL |
| `feedUrl` | `string` | RSS 2.0 feed URL |
| `fields` | `Config` | Custom fields (`Typecho\Config`) |
| `hidden` | `bool` | Whether the post is hidden |
| `isMarkdown` | `bool` | Whether the body is Markdown (starts with `<!--markdown-->`) |
| `month` | `string` | Publish month (two digits) |
| `path` | `string` | Path relative to the site root |
| `permalink` | `string` | Full URL |
| `plainExcerpt` | `?string` | Short plain-text description of the post |
| `respondId` | `string` | Reply box ID |
| `responseUrl` | `string` | Reply URL |
| `summary` | `?string` | Outputs the first line of the post as a summary |
| `tags` | `array` | Gets the tags |
| `text` | `string` | Raw body text (for Markdown posts, without the leading `<!--markdown-->` marker) |
| `theId` | `string` | Anchor ID |
| `title` | `string` | Title; "This content is password protected" when password protected and not unlocked |
| `trackbackUrl` | `string` | Trackback URL |
| `url` | `string` | Same as `permalink` |
| `year` | `string` | Publish year (four digits) |

- `author` is the author object (a subclass of `Widget\Base\Users`), for example `$posts->author->screenName`, `$posts->author->permalink`.
- `content`, `excerpt` and `summary` are processed HTML; `plainExcerpt` and `description` are plain text. When password protected and not unlocked, `content` is the password form.
- `fields` holds the custom fields (`Typecho\Config`); see [Custom Fields in Templates](../theme/custom-fields.md).
- `hidden` means "password protected and not unlocked by the current visitor", not the "hidden" status.
- `Contents\Post\Recent` queries only some of the fields (not `text`), so its `content`, `excerpt` and so on are empty. When you need the body, fetch the list with `\Widget\Archive`; see [Calling Widgets in Templates](../theme/widgets.md).

## Comments: Widget\Base\Comments {#comments}

Each item in the `$this->comments()` list and each row of `Comments\Recent` is a subclass of this class.

### Fields

| Field | Type | Description |
|---|---|---|
| `coid` | int | Comment ID |
| `cid` | int | ID of the content it belongs to |
| `created` | int | Time (timestamp) |
| `author` | string | Name |
| `authorId` | int | User ID when posted by a logged-in user, otherwise 0 |
| `ownerId` | int | Author ID of the content it belongs to |
| `mail` / `url` / `ip` / `agent` | string | Email, website, IP, user agent |
| `text` | string | Raw content |
| `type` | string | `comment`, `trackback`, `pingback` |
| `status` | string | `approved`, `waiting`, `spam` |
| `parent` | int | Parent comment ID |
| `date` | `Typecho\Date` | Generated from `created` |

### Methods

| Method | Description |
|---|---|
| `author(?bool $autoLink = null, ?bool $noFollow = null)` | Outputs the author |
| `autoP(?string $text): ?string` | Converts plain text into HTML with paragraphs |
| `date(?string $format = null)` | Outputs the post publish date |
| `delete(Query $condition): int` | Deletes data |
| `excerpt(int $length = 100, string $trim = '...')` | Outputs the comment excerpt |
| `filter(array $row): array` | Generic filter |
| `getPrimaryKey(): string` | Returns the primary key field name |
| `getRouterParam(string $key): string` | Returns the route parameter value used to build URLs |
| `gravatar(int $size = 32, ?string $default = null, $highRes = false)` | Outputs the user avatar via Gravatar |
| `insert(array $rows): int` | Adds a comment |
| `mail(bool $link = false)` | Outputs the email address |
| `markdown(?string $text): ?string` | Converts Markdown to HTML |
| `push(array $value): array` | Pushes each row's values onto the stack |
| `select(...$fields): Query` | Gets the query object |
| `size(Query $condition): int` | Counts comments matching the condition |
| `update(array $rows, Query $condition): int` | Updates comments |

- `author($autoLink = null, $noFollow = null)`: outputs the name; by default, "Settings → Discussion" decides whether it links to the commenter's website and whether `nofollow` is added;
- `gravatar($size = 32, $default = null, $highRes = false)`: outputs an avatar only for comments whose `type` is `comment`;
- `mail($link = false)`: outputs the email address; with `true`, outputs a `mailto:` link.

### Magic properties

| Property | Type | Description |
|---|---|---|
| `commentPage` | `int` | Gets the current comment page number |
| `content` | `?string` | Gets the current comment content |
| `dateWord` | `string` | Outputs the human-readable date |
| `parentContent` | `Contents` | Gets the current content structure |
| `permalink` | `string` | Gets the current comment link |
| `theId` | `string` | Anchor ID |
| `title` | `?string` | Gets the current comment title |

`parentContent` is the content object the comment belongs to, for example `$comments->parentContent->title`.

### Members specific to comment lists: Widget\Comments\Archive

The list returned by `$this->comments()` also provides:

| Method | Description |
|---|---|
| `alt(...$args)` | Overrides alt to support threaded comments |
| `cancelReply(string $word = '')` | Cancel-reply link |
| `execute()` | Execute function |
| `levelsAlt(...$args)` | Outputs based on the depth remainder |
| `listComments($singleCommentOptions = null)` | Lists comments |
| `num(...$args)` | Outputs the post comment count |
| `pageNav(string $prev = '&laquo;', string $next = '&raquo;', int $splitPage = 3, string $splitWord = '...', $template = '')` | Outputs pagination |
| `push(array $value): array` | Pushes each row's values onto the stack |
| `reply(string $word = '')` | Reply link |
| `threadedComments()` | Outputs comments recursively |

| Property | Type | Description |
|---|---|---|
| `children` | `array` | Child comments |
| `commentPage` | `int` | Overrides getting the comment page number |
| `isTopLevel` | `bool` | Whether the top level has been reached |
| `parentContent` | `Contents` | Overrides getting the content |

For the options of `listComments()` and the `threadedComments` callback, see [Comment Template](../theme/comments.md). `levels` is the nesting depth (0 at the top level).

## Categories and tags: Widget\Base\Metas {#metas}

Each row of `Metas\Category\Rows` and `Metas\Tag\Cloud`, as well as the categories and tags in `$this->categories` and `$this->tags`, corresponds to this class.

### Fields

| Field | Type | Description |
|---|---|---|
| `mid` | int | ID |
| `name` | string | Name |
| `slug` | string | Slug |
| `type` | string | `category` or `tag` |
| `description` | string | Description |
| `count` | int | Number of posts (excluding child categories) |
| `order` | int | Sort order |
| `parent` | int | Parent category ID |

`$this->categories` and `$this->tags` are plain arrays (not widget objects). Each item of the category array contains `mid`, `name`, `slug`, `description`, `order`, `parent`, `count`, `permalink`; each item of the tag array contains `mid`, `name`, `slug`, `description`, `count`, `permalink`.

### Methods

| Method | Description |
|---|---|
| `delete(Query $condition): int` | Deletes records |
| `filter(array $row): array` | Generic filter |
| `getPrimaryKey(): string` | Returns the primary key field name |
| `getRouterParam(string $key): string` | Returns the route parameter value used to build URLs |
| `insert(array $rows): int` | Inserts a record |
| `push(array $value): array` | Pushes each row's values onto the stack |
| `scanTags($inputTags)` | Gets IDs from tags |
| `select(...$fields): Query` | Gets the raw query object |
| `size(Query $condition): int` | Gets the total number of records |
| `update(array $rows, Query $condition): int` | Updates records |

### Magic properties

| Property | Type | Description |
|---|---|---|
| `directory` | `array` | Multi-level path array including parent slugs |
| `feedAtomUrl` | `string` | Atom feed URL |
| `feedRssUrl` | `string` | RSS 1.0 feed URL |
| `feedUrl` | `string` | RSS 2.0 feed URL |
| `permalink` | `string` | Full URL |
| `theId` | `string` | Anchor ID |
| `title` | `string` | Same as `name` |
| `url` | `string` | Same as `permalink` |

Categories obtained through `Metas\Category\Rows` also have `levels` (depth, 0 at the top level) and `children` (array of child categories); see [List Widgets](./list-widgets.md).

## Users: Widget\Base\Users {#users}

`$this->author` and `$this->user` (the currently logged-in user) correspond to this class.

### Fields

| Field | Type | Description |
|---|---|---|
| `uid` | int | User ID |
| `name` | string | Username |
| `screenName` | string | Display name |
| `mail` | string | Email |
| `url` | string | Homepage |
| `group` | string | User group: `administrator`, `editor`, `contributor`, `subscriber`, `visitor` |
| `created` / `activated` / `logged` | int | Registration time, last activity time, last login time |
| `password` / `authCode` | string | Password hash and login credential; do not output them in templates |

### Methods

| Method | Description |
|---|---|
| `delete(Query $condition): int` | Deletes records |
| `filter(array $row): array` | Generic filter |
| `getPrimaryKey(): string` | Returns the primary key field name |
| `getRouterParam(string $key): string` | Returns the route parameter value used to build URLs |
| `gravatar(int $size = 40, string $rating = 'X', ?string $default = null, ?string $class = null)` | Outputs the user avatar via Gravatar |
| `insert(array $rows): int` | Adds a record |
| `push(array $value): array` | Pushes each row's values onto the stack |
| `select(...$fields): Query` | Query method |
| `size(Query $condition): int` | Gets the number of all records |
| `update(array $rows, Query $condition): int` | Updates records |

`gravatar($size = 40, $rating = 'X', $default = null, $class = null)` outputs an avatar `<img>`.

### Magic properties

| Property | Type | Description |
|---|---|---|
| `feedAtomUrl` | `string` | Atom feed URL |
| `feedRssUrl` | `string` | RSS 1.0 feed URL |
| `feedUrl` | `string` | RSS 2.0 feed URL |
| `permalink` | `string` | Full URL |
| `personalOptions` | `Config` | The user's personal settings (`Typecho\Config`) |

`$this->user` (`Widget\User`) additionally provides `hasLogin()` (whether the user is logged in) and `pass($group, $return = false)` (whether the user has reached a given user group). When you check permissions in a template, pass `$return = true`, for example `$this->user->pass('editor', true)`, so it only returns a boolean; without it, a visitor who is not logged in is redirected (on the front end, to the site home page), and a logged-in user without enough permission gets a 403. When nobody is logged in, all fields of `$this->user` are `null`.
