---
title: 内容、评论、分类与用户对象
sidebar_position: 3
---

# 内容、评论、分类与用户对象

模板中循环的每一行数据都是一个 Widget 对象：文章和页面是 `Widget\Base\Contents` 的子类，评论是 `Widget\Base\Comments`，分类和标签是 `Widget\Base\Metas`，用户是 `Widget\Base\Users`。本页列出这四类对象可用的方法、魔术属性和数据库字段。

## 读取方式

- **字段和魔术属性**：`$posts->title`、`$posts->permalink` 读取值；魔术属性在第一次读取时计算并缓存。
- **输出**：表中的方法大多直接输出。对任何字段或属性调用同名方法，也会输出它的值，例如 `$posts->permalink()`、`$comments->coid()`。
- 这些对象中的 `options`、`user`、`db` 等是受保护的成员，在模板中只能通过 `$this->options`（即 `Widget\Archive`）访问，不能写成 `$posts->options`。

## 所有 Widget 共有的成员 {#widget}

`Typecho\Widget` 是所有 Widget 的基类：

| 方法 | 说明 |
|---|---|
| `static alias(string $widgetClass, string $aliasClass)` | widget别名 |
| `static alloc($params = null, $request = null, $disableSandboxOrCallback = true): Widget` | alloc widget instance |
| `static allocWithAlias(?string $alias, $params = null, $request = null, $disableSandboxOrCallback = true): Widget` | alloc widget instance with alias |
| `alt(...$args)` | 根据余数输出 |
| `altBy(int $current, ...$args)` | 根据余数输出 |
| `static destory(string $alias)` | **已弃用**。释放组件 |
| `static destroy(?string $alias = null)` | 释放组件 |
| `execute()` | execute function. |
| `have(): bool` | 返回堆栈是否为空 |
| `next()` | 返回堆栈每一行的值 |
| `on(bool $condition)` | post事件触发 |
| `parse(string $template)` | 格式化解析堆栈内的所有数据 |
| `push(array $value)` | 将每一行的值压入堆栈 |
| `pushAll(array $values)` | 将所有行的值压入堆栈 |
| `template(string $template): string` | 按模版渲染 |
| `to(&$variable): Widget` | 将类本身赋值 |
| `toArray($column): array` | 把所有行的指定列取成数组 |
| `toColumn($column)` | 取当前行的指定列 |
| `static widget(string $alias, $params = null, $request = null, $disableSandboxOrCallback = true): Widget` | 工厂方法,将类静态化放置到列表中 |

| 属性 | 类型 | 说明 |
|---|---|---|
| `length` | `int` | 输出数据长度 |
| `parameter` | `Config` | `alloc()` 传入的参数（`Typecho\Config`） |
| `request` | `Request` | 请求对象 |
| `response` | `Response` | 响应对象 |
| `sequence` | `int` | 输出顺序值 |

- `next()` 循环到末尾时返回 `false`，并把指针重置到开头，所以同一个对象可以循环多次。
- `alt('odd', 'even')` 按当前序号轮流输出参数，常用于隔行样式。
- `alloc()` 按类名缓存实例，同一个类第二次 `alloc()` 得到的是第一次的结果；需要多个实例时用 `allocWithAlias()`，见[在模板中调用 Widget](../theme/widgets.md)。

## 内容：Widget\Base\Contents {#contents}

文章、独立页面、附件、`Widget\Archive` 本身，以及 `Contents\Post\Recent`、`Contents\Page\Rows`、`related()` 返回的对象，都是这个类的子类。

### 字段

| 字段 | 类型 | 说明 |
|---|---|---|
| `cid` | int | 内容 ID |
| `title` | string | 标题 |
| `slug` | string | 缩略名 |
| `created` | int | 发布时间（时间戳） |
| `modified` | int | 最后修改时间（时间戳） |
| `text` | string | 原始正文 |
| `order` | int | 排序（独立页面的“页面顺序”；附件在文章中的顺序） |
| `authorId` | int | 作者用户 ID |
| `template` | string | 独立页面选择的自定义模板文件名 |
| `type` | string | `post`、`page`、`attachment`、`post_draft`、`page_draft`、`revision` |
| `status` | string | `publish`、`hidden`、`private`、`waiting` |
| `password` | string | 内容密码，没有时为空字符串 |
| `commentsNum` | int | 已通过的评论数（含引用） |
| `allowComment` / `allowPing` / `allowFeed` | `'1'` / `'0'` | 允许评论、被引用、出现在聚合中 |
| `parent` | int | 父级 ID（子页面、附件所属内容、修订版本所属内容） |
| `date` | `Typecho\Date` | 由 `created` 生成，`$posts->date->format('Y-m-d')` |

### 方法

| 方法 | 说明 |
|---|---|
| `allow(...$permissions): bool` | 获取文章权限 |
| `applySlug(?string $slug, $cid, string $title = ''): string` | 为内容应用缩略名 |
| `author(string $item = 'screenName')` | 输出当前作者 |
| `category(string $split = ',', bool $link = true, ?string $default = null)` | 输出文章分类 |
| `commentsNum(...$args)` | 输出文章评论数 |
| `content($more = false)` | 输出文章内容 |
| `date(?string $format = null)` | 输出文章发布日期 |
| `delete(Query $condition): int` | 删除内容 |
| `directory(string $split = '/', bool $link = true, ?string $default = null)` | 输出文章多级分类 |
| `excerpt(int $length = 100, string $trim = '...')` | 输出文章摘要 |
| `filter(array $row): array` | 通用过滤器 |
| `getPrimaryKey(): string` | 返回主键字段名 |
| `getRouterParam(string $key): string` | 返回生成地址时用到的路由参数值 |
| `getTemplates(): array` | 获取当前所有自定义模板 |
| `insert(array $rows): int` | 插入内容 |
| `isWriteable(Query $condition): bool` | 内容是否可以被修改 |
| `push(array $value): array` | 将每行的值压入堆栈 |
| `select(...$fields): Query` | 获取查询对象 |
| `size(Query $condition): int` | 按照条件计算内容数量 |
| `tags(string $split = ',', bool $link = true, ?string $default = null)` | 输出文章标签 |
| `title(int $length = 0, string $trim = '...')` | 输出标题 |
| `update(array $rows, Query $condition): int` | 更新内容 |

常用方法的参数：

- `title($length = 0, $trim = '...')`：`$length` 大于 0 时截断标题；
- `content($more = false)`：传入文字时，有 `<!--more-->` 的文章只输出摘要并加上“阅读全文”链接；
- `excerpt($length = 100, $trim = '...')`：输出纯文本摘要；
- `date($format = null)`：不传格式时使用“设置 → 阅读”中的文章日期格式；
- `category($split = ',', $link = true, $default = null)`、`tags(...)`、`directory($split = '/', ...)`：输出分类、标签、多级分类，`$default` 为没有时的文字；
- `author($item = 'screenName')`：输出作者的某个字段，例如 `author('url')`；
- `commentsNum('0 条', '1 条', '%d 条')`：按评论数选用参数，`%d` 换成数字；
- `allow('comment')` / `allow('ping')` / `allow('feed')`：返回是否允许，考虑了密码保护和“自动关闭评论”设置。

`insert()`、`update()`、`delete()`、`select()`、`size()`、`push()`、`filter()`、`isWriteable()`、`applySlug()` 供 Typecho 和插件操作数据库使用。

### 魔术属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `attachment` | `?Config` | 附件信息（只对附件有效）：`name`、`path`、`size`、`type`、`mime`、`url`、`isImage` 等 |
| `author` | `Users` | 文章作者 |
| `categories` | `array` | 所属分类数组，每项含 `mid`、`name`、`slug`、`permalink` 等 |
| `category` | `string` | 第一个分类的缩略名 |
| `commentUrl` | `string` | 评论地址 |
| `content` | `?string` | 获取文章内容 |
| `dateWord` | `string` | 获取词义化日期 |
| `day` | `string` | 发布日（两位） |
| `description` | `?string` | 对文章的简短纯文本描述 |
| `directory` | `array` | 多级目录结构 |
| `excerpt` | `?string` | 获取文章内容摘要 |
| `feedAtomUrl` | `string` | Atom 订阅地址 |
| `feedRssUrl` | `string` | RSS 1.0 订阅地址 |
| `feedUrl` | `string` | RSS 2.0 订阅地址 |
| `fields` | `Config` | 自定义字段（`Typecho\Config`） |
| `hidden` | `bool` | 是否为隐藏文章 |
| `isMarkdown` | `bool` | 正文是否为 Markdown（以 `<!--markdown-->` 开头） |
| `month` | `string` | 发布月（两位） |
| `path` | `string` | 相对站点根目录的路径 |
| `permalink` | `string` | 完整地址 |
| `plainExcerpt` | `?string` | 对文章的简短纯文本描述 |
| `respondId` | `string` | 回复框id |
| `responseUrl` | `string` | 回复地址 |
| `summary` | `?string` | 输出文章的第一行作为摘要 |
| `tags` | `array` | 将tags取出 |
| `text` | `string` | 原始正文（Markdown 文章不含开头的 `<!--markdown-->` 标记） |
| `theId` | `string` | 锚点id |
| `title` | `string` | 标题；密码保护且未解锁时为“此内容被密码保护” |
| `trackbackUrl` | `string` | trackback地址 |
| `url` | `string` | 同 `permalink` |
| `year` | `string` | 发布年（四位） |

- `author` 是作者对象（`Widget\Base\Users` 的子类），例如 `$posts->author->screenName`、`$posts->author->permalink`。
- `content`、`excerpt`、`summary` 是处理后的 HTML；`plainExcerpt`、`description` 是纯文本。密码保护且未解锁时，`content` 为密码表单。
- `fields` 是自定义字段（`Typecho\Config`），见[模板中的自定义字段](../theme/custom-fields.md)。
- `hidden` 表示“密码保护且当前访客未解锁”，不是“隐藏”状态。
- `Contents\Post\Recent` 只查询了部分字段（不含 `text`），所以它的 `content`、`excerpt` 等为空，需要正文时请用 `\Widget\Archive` 取列表，见[在模板中调用 Widget](../theme/widgets.md)。

## 评论：Widget\Base\Comments {#comments}

`$this->comments()` 列表中的每一条、`Comments\Recent` 的每一行，都是这个类的子类。

### 字段

| 字段 | 类型 | 说明 |
|---|---|---|
| `coid` | int | 评论 ID |
| `cid` | int | 所属内容 ID |
| `created` | int | 时间（时间戳） |
| `author` | string | 称呼 |
| `authorId` | int | 登录用户发表时为用户 ID，否则为 0 |
| `ownerId` | int | 所属内容的作者 ID |
| `mail` / `url` / `ip` / `agent` | string | 邮箱、网址、IP、浏览器标识 |
| `text` | string | 原始内容 |
| `type` | string | `comment`、`trackback`、`pingback` |
| `status` | string | `approved`、`waiting`、`spam` |
| `parent` | int | 父评论 ID |
| `date` | `Typecho\Date` | 由 `created` 生成 |

### 方法

| 方法 | 说明 |
|---|---|
| `author(?bool $autoLink = null, ?bool $noFollow = null)` | 输出作者相关 |
| `autoP(?string $text): ?string` | 把纯文本转为带段落的 HTML |
| `date(?string $format = null)` | 输出文章发布日期 |
| `delete(Query $condition): int` | 删除数据 |
| `excerpt(int $length = 100, string $trim = '...')` | 输出评论摘要 |
| `filter(array $row): array` | 通用过滤器 |
| `getPrimaryKey(): string` | 返回主键字段名 |
| `getRouterParam(string $key): string` | 返回生成地址时用到的路由参数值 |
| `gravatar(int $size = 32, ?string $default = null, $highRes = false)` | 调用gravatar输出用户头像 |
| `insert(array $rows): int` | 增加评论 |
| `mail(bool $link = false)` | 输出邮箱地址 |
| `markdown(?string $text): ?string` | 把 Markdown 转为 HTML |
| `push(array $value): array` | 将每行的值压入堆栈 |
| `select(...$fields): Query` | 获取查询对象 |
| `size(Query $condition): int` | 按照条件计算评论数量 |
| `update(array $rows, Query $condition): int` | 更新评论 |

- `author($autoLink = null, $noFollow = null)`：输出称呼，默认按“设置 → 评论”决定是否链接到评论者网址、是否加 `nofollow`；
- `gravatar($size = 32, $default = null, $highRes = false)`：只对 `type` 为 `comment` 的评论输出头像；
- `mail($link = false)`：输出邮箱，为 `true` 时输出 `mailto:` 链接。

### 魔术属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `commentPage` | `int` | 获取当前评论页码 |
| `content` | `?string` | 获取当前评论内容 |
| `dateWord` | `string` | 输出词义化日期 |
| `parentContent` | `Contents` | 获取当前内容结构 |
| `permalink` | `string` | 获取当前评论链接 |
| `theId` | `string` | 锚点id |
| `title` | `?string` | 获取当前评论标题 |

`parentContent` 是评论所属的内容对象，例如 `$comments->parentContent->title`。

### 评论列表专有的成员：Widget\Comments\Archive

`$this->comments()` 返回的列表另外提供：

| 方法 | 说明 |
|---|---|
| `alt(...$args)` | 重载alt函数,以适应多级评论 |
| `cancelReply(string $word = '')` | 取消评论回复链接 |
| `execute()` | 执行函数 |
| `levelsAlt(...$args)` | 根据深度余数输出 |
| `listComments($singleCommentOptions = null)` | 列出评论 |
| `num(...$args)` | 输出文章评论数 |
| `pageNav(string $prev = '&laquo;', string $next = '&raquo;', int $splitPage = 3, string $splitWord = '...', $template = '')` | 输出分页 |
| `push(array $value): array` | 将每行的值压入堆栈 |
| `reply(string $word = '')` | 评论回复链接 |
| `threadedComments()` | 递归输出评论 |

| 属性 | 类型 | 说明 |
|---|---|---|
| `children` | `array` | 子评论 |
| `commentPage` | `int` | 重载评论页码获取 |
| `isTopLevel` | `bool` | 是否到达顶层 |
| `parentContent` | `Contents` | 重载内容获取 |

`listComments()` 的选项和 `threadedComments` 回调见[评论模板](../theme/comments.md)。`levels` 是嵌套深度（顶层为 0）。

## 分类与标签：Widget\Base\Metas {#metas}

`Metas\Category\Rows`、`Metas\Tag\Cloud` 的每一行，以及 `$this->categories`、`$this->tags` 中的分类、标签，都对应这个类。

### 字段

| 字段 | 类型 | 说明 |
|---|---|---|
| `mid` | int | ID |
| `name` | string | 名称 |
| `slug` | string | 缩略名 |
| `type` | string | `category` 或 `tag` |
| `description` | string | 描述 |
| `count` | int | 文章数（不含子分类） |
| `order` | int | 排序 |
| `parent` | int | 父分类 ID |

`$this->categories`、`$this->tags` 是普通数组（不是 Widget 对象）。分类数组的每项包含 `mid`、`name`、`slug`、`description`、`order`、`parent`、`count`、`permalink`；标签数组的每项包含 `mid`、`name`、`slug`、`description`、`count`、`permalink`。

### 方法

| 方法 | 说明 |
|---|---|
| `delete(Query $condition): int` | 删除记录 |
| `filter(array $row): array` | 通用过滤器 |
| `getPrimaryKey(): string` | 返回主键字段名 |
| `getRouterParam(string $key): string` | 返回生成地址时用到的路由参数值 |
| `insert(array $rows): int` | 插入一条记录 |
| `push(array $value): array` | 将每行的值压入堆栈 |
| `scanTags($inputTags)` | 根据tag获取ID |
| `select(...$fields): Query` | 获取原始查询对象 |
| `size(Query $condition): int` | 获取记录总数 |
| `update(array $rows, Query $condition): int` | 更新记录 |

### 魔术属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `directory` | `array` | 包含父级缩略名的多级路径数组 |
| `feedAtomUrl` | `string` | Atom 订阅地址 |
| `feedRssUrl` | `string` | RSS 1.0 订阅地址 |
| `feedUrl` | `string` | RSS 2.0 订阅地址 |
| `permalink` | `string` | 完整地址 |
| `theId` | `string` | 锚点id |
| `title` | `string` | 同 `name` |
| `url` | `string` | 同 `permalink` |

通过 `Metas\Category\Rows` 取得的分类还有 `levels`（层级，顶层为 0）和 `children`（子分类数组），见[列表类 Widget](./list-widgets.md)。

## 用户：Widget\Base\Users {#users}

`$this->author`、`$this->user`（当前登录用户）对应这个类。

### 字段

| 字段 | 类型 | 说明 |
|---|---|---|
| `uid` | int | 用户 ID |
| `name` | string | 用户名 |
| `screenName` | string | 昵称 |
| `mail` | string | 邮箱 |
| `url` | string | 个人主页 |
| `group` | string | 用户组：`administrator`、`editor`、`contributor`、`subscriber`、`visitor` |
| `created` / `activated` / `logged` | int | 注册时间、最后活动时间、上次登录时间 |
| `password` / `authCode` | string | 密码哈希和登录凭据，不要在模板中输出 |

### 方法

| 方法 | 说明 |
|---|---|
| `delete(Query $condition): int` | 删除记录方法 |
| `filter(array $row): array` | 通用过滤器 |
| `getPrimaryKey(): string` | 返回主键字段名 |
| `getRouterParam(string $key): string` | 返回生成地址时用到的路由参数值 |
| `gravatar(int $size = 40, string $rating = 'X', ?string $default = null, ?string $class = null)` | 调用gravatar输出用户头像 |
| `insert(array $rows): int` | 增加记录方法 |
| `push(array $value): array` | 将每行的值压入堆栈 |
| `select(...$fields): Query` | 查询方法 |
| `size(Query $condition): int` | 获得所有记录数 |
| `update(array $rows, Query $condition): int` | 更新记录方法 |

`gravatar($size = 40, $rating = 'X', $default = null, $class = null)` 输出头像 `<img>`。

### 魔术属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `feedAtomUrl` | `string` | Atom 订阅地址 |
| `feedRssUrl` | `string` | RSS 1.0 订阅地址 |
| `feedUrl` | `string` | RSS 2.0 订阅地址 |
| `permalink` | `string` | 完整地址 |
| `personalOptions` | `Config` | 该用户的个人设置（`Typecho\Config`） |

`$this->user`（`Widget\User`）另外提供 `hasLogin()`（是否已登录）和 `pass($group, $return = false)`（是否达到某个用户组）。在模板中判断权限时要传 `$return = true`，例如 `$this->user->pass('editor', true)`，它只返回布尔值；不传时，未登录会被重定向（前台重定向到站点首页），已登录但权限不够会报 403。未登录时 `$this->user` 的各字段为 `null`。
