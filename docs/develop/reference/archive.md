---
title: Widget\Archive
sidebar_position: 2
---

# Widget\Archive

`Widget\Archive` 是前台页面的主对象，模板中的 `$this` 就是它，用法见[主题开发概览](../theme/index.md)。它继承自 `Widget\Base\Contents`，所以除了本页列出的方法，还可以使用[内容对象](./row-widgets.md#contents)的全部方法和属性（`title()`、`content()`、`permalink` 等），以及所有 Widget 共有的 `have()`、`next()`、`to()` 等（见[所有 Widget 共有的成员](./row-widgets.md#widget)）。

## 方法与属性的调用方式

- **方法**：`$this->title()` 这类方法大多直接输出（`echo`），返回值为空；签名中带返回类型的（如 `is(): bool`、`getArchiveTitle(): ?string`）返回值，不输出。
- **魔术属性**：表中“魔术属性”一栏的名字可以像属性一样读取，例如 `$this->commentUrl`，第一次读取时调用对应的 `___commentUrl()` 方法并缓存结果。数据库字段（如 `$this->cid`）也是这样读取的。
- **任何属性都可以当方法调用来输出**：调用一个不存在的方法 `$this->xxx()` 时，会输出 `$this->xxx` 的值（插件可以通过 `callXxx` 接口接管）。例如 `$this->commentUrl()` 输出评论提交地址。

## 方法

| 方法 | 说明 |
|---|---|
| `addArchiveTitle(string $archiveTitle)` | 增加标题。**1.3.0 中这个方法不能使用**：已有标题时报错（`[] operator not supported for strings`），没有标题时抛出 `TypeError`。要在标题后追加文字，用 `setArchiveTitle()` 自己拼接，例如 `$this->setArchiveTitle(($this->getArchiveTitle() ?? '') . ' - 第 2 页')` |
| `archiveTitle($defines = null, string $before = ' &raquo; ', string $end = '')` | 输出归档标题 |
| `attachments(int $limit = 0, int $offset = 0): Related` | 获取附件对象 |
| `comments(): Archive` | 获取评论归档对象 |
| `content($more = null)` | 输出文章内容 |
| `execute()` | 执行函数 |
| `footer()` | 支持页脚自定义 |
| `getArchiveDescription(): ?string` | 返回归档描述（分类描述、文章摘要，首页和搜索页为站点描述） |
| `getArchiveFeedAtomUrl(): string` | 返回当前页面的 Atom 订阅地址 |
| `getArchiveFeedRssUrl(): string` | 返回当前页面的 RSS 1.0 订阅地址 |
| `getArchiveFeedUrl(): string` | 返回当前页面的 RSS 2.0 订阅地址 |
| `getArchiveKeywords(): ?string` | 返回归档关键词（文章标签、分类名等） |
| `getArchiveSlug(): ?string` | 返回归档缩略名。首页上调用会抛出错误，见上文 |
| `getArchiveTitle(): ?string` | 返回归档标题，首页为 `null` |
| `getArchiveType(): ?string` | 返回页面类型，取值见[页面上下文与 is()](../theme/context.md) |
| `getArchiveUrl(): ?string` | 返回当前页面地址 |
| `getCountSql(): ?Query` | 返回列表计数用的查询对象 |
| `getCurrentPage(): int` | 返回当前页码 |
| `getDescription(): ?string` | **已弃用**。 |
| `getFeed()` | **已弃用**。Get the value of feed |
| `getFeedAtomUrl(): string` | **已弃用**。 |
| `getFeedRssUrl(): string` | **已弃用**。 |
| `getFeedUrl(): string` | **已弃用**。 |
| `getKeywords(): ?string` | **已弃用**。 |
| `getThemeDir(): ?string` | 返回当前主题目录的绝对路径 |
| `getThemeFile(): ?string` | 返回将要（或正在）输出的模板文件名 |
| `getTotal(): int` | 返回列表的文章总数。只能在列表页调用，见上文 |
| `getTotalPage(): int` | 获取页数 |
| `header(?string $rule = null)` | 输出头部元数据 |
| `is(string $archiveType, ?string $archiveSlug = null): bool` | 判断归档类型和名称 |
| `keywords(string $split = ',', string $default = '')` | 输出关键字 |
| `need(string $fileName)` | 获取主题文件 |
| `pageLink(string $word = '&laquo; Previous Entries', string $page = 'prev')` | 前一页 |
| `pageNav(string $prev = '&laquo;', string $next = '&raquo;', int $splitPage = 3, string $splitWord = '...', $template = '')` | 输出分页 |
| `pings(): Ping` | 获取回响归档对象 |
| `query($select)` | 提交查询 |
| `related(int $limit = 5, ?string $type = null): Contents` | 获取关联内容组件 |
| `remember(string $cookieName, bool $return = false)` | 输出cookie记忆别名 |
| `render()` | 输出视图 |
| `select(...$fields): Query` | 重载select |
| `setArchiveDescription(string $archiveDescription)` | 设置归档描述 |
| `setArchiveFeedAtomUrl(string $archiveFeedAtomUrl)` | 设置 Atom 订阅地址 |
| `setArchiveFeedRssUrl(string $archiveFeedRssUrl)` | 设置 RSS 1.0 订阅地址 |
| `setArchiveFeedUrl(string $archiveFeedUrl)` | 设置 RSS 2.0 订阅地址 |
| `setArchiveKeywords(string $archiveKeywords)` | 设置归档关键词 |
| `setArchiveSlug(string $archiveSlug)` | 设置归档缩略名 |
| `setArchiveTitle(string $archiveTitle)` | 设置归档标题 |
| `setArchiveType(string $archiveType)` | 设置页面类型 |
| `setArchiveUrl(?string $archiveUrl): void` | 设置当前页面地址 |
| `setCountSql($countSql)` | 设置计数查询 |
| `setFeed($feed)` | **已弃用**。Set the value of feed |
| `setThemeDir(string $themeDir)` | 设置主题目录 |
| `setThemeFile(string $themeFile)` | 指定要输出的模板文件 |
| `setTotal(int $total)` | 设置文章总数 |
| `theLink(Contents $content, string $format = '%s', ?string $default = null, array $custom = [])` | 输出某个内容对象的标题链接，`thePrev()` / `theNext()` 内部使用 |
| `theNext(string $format = '%s', ?string $default = null, array $custom = [])` | 显示下一个内容的标题链接 |
| `thePrev(string $format = '%s', ?string $default = null, array $custom = [])` | 显示上一个内容的标题链接 |

模板中最常用的是：`is()`、`need()`、`header()`、`footer()`、`archiveTitle()`、`keywords()`、`pageNav()`、`pageLink()`、`content()`、`comments()`、`thePrev()`、`theNext()`、`related()`、`remember()`，以及 `getArchive…()` 系列。`execute()`、`render()`、`query()`、`select()` 和 `set…()` 系列供 Typecho 和插件内部使用。

几点注意：

- `getTotal()`、`getTotalPage()` 依赖列表查询，**只能在列表页**（首页、分类、标签、搜索、归档）调用；在文章页、独立页面上调用会抛出 `Typed property Widget\Archive::$countSql must not be accessed before initialization`。
- 首页没有归档缩略名，在首页调用 `getArchiveSlug()` 或 `is('index', '…')` 会抛出类似的错误，见[页面上下文与 is()](../theme/context.md)。
- `pings()` 在 1.3.0 中会导致致命错误，不能使用，替代写法见[评论模板](../theme/comments.md#pings)。
- 标为“已弃用”的 `getDescription()`、`getKeywords()`、`getFeedUrl()`、`getFeedRssUrl()`、`getFeedAtomUrl()` 是 `getArchiveDescription()` 等的旧名字，新代码请用后者。
- `pageNav()` 的 `$template` 参数可以是数组或查询字符串，可用的键及默认值：`wrapTag`（`ol`）、`wrapClass`（`page-navigator`）、`itemTag`（`li`）、`textTag`（`span`）、`currentClass`（`current`）、`prevClass`（`prev`）、`nextClass`（`next`）。`classic-22` 用的是 `['wrapTag' => 'ul', 'itemTag' => 'li']`。总数不超过每页篇数时不输出任何内容。

## 魔术属性

`Widget\Archive` 自己声明的魔术属性：

| 属性 | 类型 | 说明 |
|---|---|---|
| `commentUrl` | `string` | 评论地址 |
| `directory` | `array` | 包含父级缩略名的多级路径数组 |

其余属性（`title`、`permalink`、`fields`、`categories`、`tags`、`author` 等）继承自 `Widget\Base\Contents`，见[内容对象](./row-widgets.md#contents)。

`$this->options`（站点设置，见 [$this->options](./options.md)）和 `$this->user`（当前登录用户，未登录时各字段为 `null`）在模板中也可以直接使用。
