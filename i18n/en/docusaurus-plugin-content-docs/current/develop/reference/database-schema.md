---
title: Database Schema
sidebar_position: 10
translation: machine
source_commit: 342fdf14c307f4efef7d31add7e5c261f14c71b4
---

# Database Schema

Typecho 1.3.0 creates 7 tables during installation. Table names carry the prefix set during installation (`typecho_` by default); in the query builder you write `table.contents` instead. See [Database Operations](../plugin/database.md).

The "Type" column shows the MySQL type. In PostgreSQL and SQLite, integer columns are `INT` / `int(10)` without `unsigned`, and auto-increment primary keys are a sequence (`<table name>_seq`) and `INTEGER PRIMARY KEY` respectively; other differences are noted below each table.

| Table | Contents |
|---|---|
| [`contents`](#contents) | Posts, pages, attachments, and their drafts and revisions |
| [`comments`](#comments) | Comments, trackbacks, pingbacks |
| [`metas`](#metas) | Categories and tags |
| [`relationships`](#relationships) | Links between content and categories/tags |
| [`fields`](#fields) | Custom fields |
| [`users`](#users) | Users |
| [`options`](#options) | Site settings, theme and plugin settings, personal settings |

## contents {#contents}

| Column | Type | Default | Description |
|---|---|---|---|
| `cid` | `int(10) unsigned` auto-increment | | Primary key |
| `title` | `varchar(150)` | `NULL` | Title; the file name for attachments |
| `slug` | `varchar(150)` | `NULL` | Slug, **unique**; the file name for attachments |
| `created` | `int(10) unsigned` | `0` | Publish time, Unix timestamp |
| `modified` | `int(10) unsigned` | `0` | Last modified time |
| `text` | `longtext` (`text` in PostgreSQL and SQLite) | | Body. Markdown content starts with `<!--markdown-->`; for attachments, JSON with the file information |
| `order` | `int(10) unsigned` | `0` | Sort order of pages |
| `authorId` | `int(10) unsigned` | `0` | The author's `users.uid` |
| `template` | `varchar(32)` | `NULL` | File name of the custom template used by a page |
| `type` | `varchar(16)` | `'post'` | Content type (see the table below) |
| `status` | `varchar(16)` | `'publish'` | Status (see the table below) |
| `password` | `varchar(32)` | `NULL` | Access password, stored in plain text; empty means no password |
| `commentsNum` | `int(10) unsigned` | `0` | Number of approved comments (including trackbacks) |
| `allowComment` | `char(1)` | `'0'` | Allow comments, `'1'` / `'0'` |
| `allowPing` | `char(1)` | `'0'` | Allow pings, `'1'` / `'0'` |
| `allowFeed` | `char(1)` | `'0'` | Appear in feeds, `'1'` / `'0'` |
| `parent` | `int(10) unsigned` | `0` | See the `type` table |

Indexes: unique index on `slug`, regular index on `created`.

### contents.type

| Value | Meaning | Meaning of `parent` |
|---|---|---|
| `post` | Post | — |
| `page` | Page | `cid` of the parent page, `0` for a top-level page |
| `attachment` | Attachment | `cid` of the post or page it belongs to, `0` if unattached |
| `post_draft` | Draft of a post that has never been published | — |
| `page_draft` | Draft of a page that has never been published | `cid` of the parent page |
| `revision` | Revision of published content: created when you "Save draft" on a published post or page, deleted after publishing | `cid` of the original post or page |

### contents.status

| Value | Shown in admin | Meaning |
|---|---|---|
| `publish` | Public | Published publicly. Content whose `created` is later than the current time is scheduled and not shown on the front end until then |
| `hidden` | Hidden | Not shown in lists or feeds; anyone who knows the URL can access it |
| `private` | Private | Visible only to the author |
| `waiting` | Pending review | Post submitted by a contributor, waiting for review by an editor or administrator |

"Password protected" is not a status: content with a `password` set still has the `status` `publish`. Drafts are not a status either but are distinguished by `type` (`post_draft`, `page_draft`, `revision`); the `status` of a draft row is usually `publish`. For how each status affects visitors, the author and other users, see [Post Visibility](../../guide/content/visibility.md).

## comments {#comments}

| Column | Type | Default | Description |
|---|---|---|---|
| `coid` | `int(10) unsigned` auto-increment | | Primary key |
| `cid` | `int(10) unsigned` | `0` | `contents.cid` of the content it belongs to |
| `created` | `int(10) unsigned` | `0` | Submission time, Unix timestamp |
| `author` | `varchar(150)` | `NULL` | Commenter's name; the source site name for trackbacks |
| `authorId` | `int(10) unsigned` | `0` | The `users.uid` for comments by logged-in users, `0` for visitors |
| `ownerId` | `int(10) unsigned` | `0` | `users.uid` of the author of the content being commented on |
| `mail` | `varchar(150)` | `NULL` | Email |
| `url` | `varchar(255)` | `NULL` | Website; the source URL for trackbacks |
| `ip` | `varchar(64)` | `NULL` | IP address |
| `agent` | `varchar(511)` | `NULL` | User-Agent |
| `text` | `text` | | Comment content |
| `type` | `varchar(16)` | `'comment'` | Type (see the table below) |
| `status` | `varchar(16)` | `'approved'` | Status (see the table below) |
| `parent` | `int(10) unsigned` | `0` | `coid` of the comment being replied to, `0` for a direct comment |

Indexes: `cid`, `created`.

### comments.type

| Value | Meaning |
|---|---|
| `comment` | Comment |
| `trackback` | Trackback |
| `pingback` | Pingback (received through XML-RPC) |

### comments.status

| Value | Shown in admin | Meaning |
|---|---|---|
| `approved` | Approved | Shown on the front end, counted in `contents.commentsNum` |
| `waiting` | Pending | Waiting for review; only the submitter can see it on the front end |
| `spam` | Spam | Spam comment |

## metas {#metas}

| Column | Type | Default | Description |
|---|---|---|---|
| `mid` | `int(10) unsigned` auto-increment | | Primary key |
| `name` | `varchar(150)` | `NULL` | Name |
| `slug` | `varchar(150)` | `NULL` | Slug |
| `type` | `varchar(32)` (`VARCHAR(16)` in PostgreSQL) | None | `category` for categories, `tag` for tags |
| `description` | `varchar(150)` | `NULL` | Description |
| `count` | `int(10) unsigned` | `0` | Number of published posts under it |
| `order` | `int(10) unsigned` | `0` | Sort order of categories |
| `parent` | `int(10) unsigned` | `0` | `mid` of the parent category, `0` for a top-level category; always `0` for tags |

Indexes: `slug`. The `mid` of the default category is stored in the option `defaultCategory`.

## relationships {#relationships}

| Column | Type | Description |
|---|---|---|
| `cid` | `int(10) unsigned` | `contents.cid` |
| `mid` | `int(10) unsigned` | `metas.mid` |

Primary key (a unique index in SQLite): `(cid, mid)`. Posts and categories/tags have a many-to-many relationship, one row per pair. Drafts and revisions also have their own relationship rows.

## fields {#fields}

The custom field table in 1.3, one row per field per content item.

| Column | Type | Default | Description |
|---|---|---|---|
| `cid` | `int(10) unsigned` | | `contents.cid` |
| `name` | `varchar(150)` | | Field name |
| `type` | `varchar(8)` | `'str'` | `str`, `int`, `float`, `json` |
| `str_value` | `text` | | Value for the `str` and `json` types (`json` stores the encoded string) |
| `int_value` | `int(10)` | `0` | Value for the `int` type, `0` for other types |
| `float_value` | `float` (`real` in PostgreSQL and SQLite) | `0` | Value for the `float` type, `0` for other types |

Primary key (a unique index in SQLite): `(cid, name)`; indexes: `int_value`, `float_value`. For how to use fields, see [Custom Fields in Templates](../theme/custom-fields.md).

## users {#users}

| Column | Type | Default | Description |
|---|---|---|---|
| `uid` | `int(10) unsigned` auto-increment | | Primary key |
| `name` | `varchar(32)` | `NULL` | Login name, **unique** |
| `password` | `varchar(64)` | `NULL` | Password hash. Hashes starting with `$P$` migrated from older versions or other software also verify |
| `mail` | `varchar(150)` | `NULL` | Email, **unique**, can also be used to log in |
| `url` | `varchar(150)` | `NULL` | Homepage |
| `screenName` | `varchar(32)` | `NULL` | Nickname, used for display |
| `created` | `int(10) unsigned` | `0` | Registration time |
| `activated` | `int(10) unsigned` | `0` | Last activity time, updated on every request of a logged-in user |
| `logged` | `int(10) unsigned` | `0` | On each login, set to the value `activated` had before the login, i.e. the last activity time during the previous login session |
| `group` | `varchar(16)` | `'visitor'` | User group: `administrator`, `editor`, `contributor`, `subscriber`, `visitor` (see the [permission table](../../guide/content/users.md#permission-table)) |
| `authCode` | `varchar(64)` | `NULL` | Login credential, regenerated on every login |

Unique indexes: `name`, `mail`.

## options {#options}

| Column | Type | Default | Description |
|---|---|---|---|
| `name` | `varchar(32)` | | Option name |
| `user` | `int(10) unsigned` | `0` | `0` for a site-wide option, otherwise this user's personal option |
| `value` | `text` | | Value; arrays are stored as JSON |

Primary key (a unique index in SQLite): `(name, user)`.

Besides the site-wide options in the [option name list](./option-names.md), the `options` table also contains:

| `name` | `user` | Contents |
|---|---|---|
| `theme:<directory>` | `0` | Theme settings (see [Theme Settings](../theme/theme-options.md)) |
| `plugin:<plugin name>` | `0` | Plugin settings (see [Plugin Configuration](../plugin/settings.md)) |
| `_plugin:<plugin name>` | `0` or user ID | Plugin personal settings: the default values when `user` is `0`, or that user's values when `user` is a user ID |
| Personal setting option names (such as `autoSave`, `markdown`) | User ID | Values a user saves in "Profile", overriding the site-wide option of the same name |

`name` is `varchar(32)`, so longer option names cannot be saved in MySQL and PostgreSQL. The `_plugin:` prefix takes 8 characters, so keep plugin names to 24 characters or fewer.
