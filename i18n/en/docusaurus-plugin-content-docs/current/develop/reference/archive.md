---
title: Widget\Archive
sidebar_position: 2
translation: machine
source_commit: 1d967a0c62b5a260a8146b63efb3c9323aa91cc3
---

# Widget\Archive

`Widget\Archive` is the main object of front-end pages: `$this` in a template is this object. For usage, see [Theme Development Overview](../theme/index.md). It extends `Widget\Base\Contents`, so besides the methods listed on this page you can also use all methods and properties of [content objects](./row-widgets.md#contents) (`title()`, `content()`, `permalink` and so on), plus `have()`, `next()`, `to()` and the other members shared by all widgets (see [Members shared by all widgets](./row-widgets.md#widget)).

## How methods and properties are called

- **Methods**: most methods like `$this->title()` output directly (`echo`) and return nothing; those with a return type in their signature (such as `is(): bool` and `getArchiveTitle(): ?string`) return a value and do not output anything.
- **Magic properties**: names in the "Magic properties" table can be read like properties, for example `$this->commentUrl`. The first read calls the corresponding `___commentUrl()` method and caches the result. Database fields (such as `$this->cid`) are read the same way.
- **Any property can be called as a method to output it**: when you call a method that does not exist, `$this->xxx()`, the value of `$this->xxx` is output (plugins can take this over through the `callXxx` hook). For example, `$this->commentUrl()` outputs the comment submission URL.

## Methods

| Method | Description |
|---|---|
| `addArchiveTitle(string $archiveTitle)` | Appends to the title. **This method cannot be used in 1.3.0**: it raises an error when a title already exists (`[] operator not supported for strings`) and throws a `TypeError` when there is no title. To append text to the title, build the string yourself with `setArchiveTitle()`, for example `$this->setArchiveTitle(($this->getArchiveTitle() ?? '') . ' - Page 2')` |
| `archiveTitle($defines = null, string $before = ' &raquo; ', string $end = '')` | Outputs the archive title |
| `attachments(int $limit = 0, int $offset = 0): Related` | Gets the attachments object |
| `comments(): Archive` | Gets the comment archive object |
| `content($more = null)` | Outputs the post content |
| `execute()` | Execute function |
| `footer()` | Supports custom footer output |
| `getArchiveDescription(): ?string` | Returns the archive description (category description, post excerpt; the site description on the home page and search pages) |
| `getArchiveFeedAtomUrl(): string` | Returns the Atom feed URL of the current page |
| `getArchiveFeedRssUrl(): string` | Returns the RSS 1.0 feed URL of the current page |
| `getArchiveFeedUrl(): string` | Returns the RSS 2.0 feed URL of the current page |
| `getArchiveKeywords(): ?string` | Returns the archive keywords (post tags, category names, etc.) |
| `getArchiveSlug(): ?string` | Returns the archive slug. Calling it on the home page throws an error, see above |
| `getArchiveTitle(): ?string` | Returns the archive title; `null` on the home page |
| `getArchiveType(): ?string` | Returns the page type; for possible values see [Page Context and is()](../theme/context.md) |
| `getArchiveUrl(): ?string` | Returns the URL of the current page |
| `getCountSql(): ?Query` | Returns the query object used to count the list |
| `getCurrentPage(): int` | Returns the current page number |
| `getDescription(): ?string` | **Deprecated**. |
| `getFeed()` | **Deprecated**. Get the value of feed |
| `getFeedAtomUrl(): string` | **Deprecated**. |
| `getFeedRssUrl(): string` | **Deprecated**. |
| `getFeedUrl(): string` | **Deprecated**. |
| `getKeywords(): ?string` | **Deprecated**. |
| `getThemeDir(): ?string` | Returns the absolute path of the current theme directory |
| `getThemeFile(): ?string` | Returns the name of the template file that will be (or is being) output |
| `getTotal(): int` | Returns the total number of posts in the list. Can only be called on list pages, see above |
| `getTotalPage(): int` | Gets the number of pages |
| `header(?string $rule = null)` | Outputs header metadata |
| `is(string $archiveType, ?string $archiveSlug = null): bool` | Checks the archive type and name |
| `keywords(string $split = ',', string $default = '')` | Outputs the keywords |
| `need(string $fileName)` | Includes a theme file |
| `pageLink(string $word = '&laquo; Previous Entries', string $page = 'prev')` | Previous page |
| `pageNav(string $prev = '&laquo;', string $next = '&raquo;', int $splitPage = 3, string $splitWord = '...', $template = '')` | Outputs pagination |
| `pings(): Ping` | Gets the pings archive object |
| `query($select)` | Runs the query |
| `related(int $limit = 5, ?string $type = null): Contents` | Gets the related content widget |
| `remember(string $cookieName, bool $return = false)` | Outputs a remembered cookie value |
| `render()` | Renders the view |
| `select(...$fields): Query` | Overrides select |
| `setArchiveDescription(string $archiveDescription)` | Sets the archive description |
| `setArchiveFeedAtomUrl(string $archiveFeedAtomUrl)` | Sets the Atom feed URL |
| `setArchiveFeedRssUrl(string $archiveFeedRssUrl)` | Sets the RSS 1.0 feed URL |
| `setArchiveFeedUrl(string $archiveFeedUrl)` | Sets the RSS 2.0 feed URL |
| `setArchiveKeywords(string $archiveKeywords)` | Sets the archive keywords |
| `setArchiveSlug(string $archiveSlug)` | Sets the archive slug |
| `setArchiveTitle(string $archiveTitle)` | Sets the archive title |
| `setArchiveType(string $archiveType)` | Sets the page type |
| `setArchiveUrl(?string $archiveUrl): void` | Sets the URL of the current page |
| `setCountSql($countSql)` | Sets the count query |
| `setFeed($feed)` | **Deprecated**. Set the value of feed |
| `setThemeDir(string $themeDir)` | Sets the theme directory |
| `setThemeFile(string $themeFile)` | Sets the template file to output |
| `setTotal(int $total)` | Sets the total number of posts |
| `theLink(Contents $content, string $format = '%s', ?string $default = null, array $custom = [])` | Outputs the title link of a content object; used internally by `thePrev()` / `theNext()` |
| `theNext(string $format = '%s', ?string $default = null, array $custom = [])` | Shows the title link of the next content item |
| `thePrev(string $format = '%s', ?string $default = null, array $custom = [])` | Shows the title link of the previous content item |

The ones you use most in templates are `is()`, `need()`, `header()`, `footer()`, `archiveTitle()`, `keywords()`, `pageNav()`, `pageLink()`, `content()`, `comments()`, `thePrev()`, `theNext()`, `related()`, `remember()`, and the `getArchive…()` family. `execute()`, `render()`, `query()`, `select()` and the `set…()` family are for internal use by Typecho and plugins.

A few things to note:

- `getTotal()` and `getTotalPage()` depend on the list query and **can only be called on list pages** (home, category, tag, search, archive); calling them on a post or page throws `Typed property Widget\Archive::$countSql must not be accessed before initialization`.
- The home page has no archive slug. Calling `getArchiveSlug()` or `is('index', '…')` on the home page throws a similar error; see [Page Context and is()](../theme/context.md).
- `pings()` causes a fatal error in 1.3.0 and cannot be used. For an alternative, see [Comment Template](../theme/comments.md#pings).
- The methods marked "Deprecated", `getDescription()`, `getKeywords()`, `getFeedUrl()`, `getFeedRssUrl()` and `getFeedAtomUrl()`, are old names for `getArchiveDescription()` and the like; use the latter in new code.
- The `$template` argument of `pageNav()` can be an array or a query string. The available keys and their defaults are: `wrapTag` (`ol`), `wrapClass` (`page-navigator`), `itemTag` (`li`), `textTag` (`span`), `currentClass` (`current`), `prevClass` (`prev`), `nextClass` (`next`). `classic-22` uses `['wrapTag' => 'ul', 'itemTag' => 'li']`. Nothing is output when the total does not exceed the number of posts per page.

## Magic properties

Magic properties declared by `Widget\Archive` itself:

| Property | Type | Description |
|---|---|---|
| `commentUrl` | `string` | Comment URL |
| `directory` | `array` | Multi-level path array including parent slugs |

The other properties (`title`, `permalink`, `fields`, `categories`, `tags`, `author` and so on) are inherited from `Widget\Base\Contents`; see [content objects](./row-widgets.md#contents).

`$this->options` (site settings, see [$this->options](./options.md)) and `$this->user` (the currently logged-in user; all fields are `null` when nobody is logged in) are also available directly in templates.
