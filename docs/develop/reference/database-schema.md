---
title: 数据表结构
sidebar_position: 10
---

# 数据表结构

Typecho 1.3.0 安装后建立 7 张表。表名前加安装时设置的前缀（默认 `typecho_`），在查询构造器中用 `table.contents` 这样的写法代替，见[数据库操作](../plugin/database.md)。

“类型”一栏是 MySQL 中的类型。PostgreSQL 和 SQLite 中整数字段都是不带 `unsigned` 的 `INT` / `int(10)`，自增主键分别是序列（`表名_seq`）和 `INTEGER PRIMARY KEY`；其他差异在表下注明。

| 表 | 内容 |
|---|---|
| [`contents`](#contents) | 文章、独立页面、附件，以及它们的草稿和修订版本 |
| [`comments`](#comments) | 评论、引用通告（trackback）、pingback |
| [`metas`](#metas) | 分类和标签 |
| [`relationships`](#relationships) | 内容与分类、标签的对应关系 |
| [`fields`](#fields) | 自定义字段 |
| [`users`](#users) | 用户 |
| [`options`](#options) | 站点设置、主题和插件设置、个人设置 |

## contents {#contents}

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `cid` | `int(10) unsigned` 自增 | | 主键 |
| `title` | `varchar(150)` | `NULL` | 标题；附件为文件名 |
| `slug` | `varchar(150)` | `NULL` | 缩略名，**唯一**；附件为文件名 |
| `created` | `int(10) unsigned` | `0` | 发布时间，Unix 时间戳 |
| `modified` | `int(10) unsigned` | `0` | 最后修改时间 |
| `text` | `longtext`（PostgreSQL、SQLite 为 `text`） | | 正文。Markdown 内容以 `<!--markdown-->` 开头；附件为文件信息的 JSON |
| `order` | `int(10) unsigned` | `0` | 独立页面的排序 |
| `authorId` | `int(10) unsigned` | `0` | 作者的 `users.uid` |
| `template` | `varchar(32)` | `NULL` | 独立页面使用的自定义模板文件名 |
| `type` | `varchar(16)` | `'post'` | 内容类型，见下表 |
| `status` | `varchar(16)` | `'publish'` | 状态，见下表 |
| `password` | `varchar(32)` | `NULL` | 访问密码，明文保存；为空表示不加密 |
| `commentsNum` | `int(10) unsigned` | `0` | 已通过的评论数（含引用通告） |
| `allowComment` | `char(1)` | `'0'` | 允许评论，`'1'` / `'0'` |
| `allowPing` | `char(1)` | `'0'` | 允许被引用，`'1'` / `'0'` |
| `allowFeed` | `char(1)` | `'0'` | 出现在订阅源中，`'1'` / `'0'` |
| `parent` | `int(10) unsigned` | `0` | 见 `type` 表 |

索引：`slug` 唯一索引，`created` 普通索引。

### contents.type

| 值 | 含义 | `parent` 的含义 |
|---|---|---|
| `post` | 文章 | — |
| `page` | 独立页面 | 父页面的 `cid`，`0` 为顶级页面 |
| `attachment` | 附件 | 所属文章或页面的 `cid`，`0` 为未归属 |
| `post_draft` | 从未发布过的文章草稿 | — |
| `page_draft` | 从未发布过的页面草稿 | 父页面的 `cid` |
| `revision` | 已发布内容的修订版本：在已发布的文章或页面上“保存草稿”时生成，发布后删除 | 原文章或页面的 `cid` |

### contents.status

| 值 | 后台显示 | 含义 |
|---|---|---|
| `publish` | 公开 | 公开发布。`created` 晚于当前时间的是定时发布，到时间前前台不显示 |
| `hidden` | 隐藏 | 不出现在列表和订阅源中，知道地址的人可以访问 |
| `private` | 私密 | 只有作者本人可见 |
| `waiting` | 待审核 | 贡献者提交的文章，等待编辑或管理员审核 |

“密码保护”不是一种状态：设置了 `password` 的内容 `status` 仍为 `publish`。草稿也不是一种状态，而是用 `type` 区分（`post_draft`、`page_draft`、`revision`），草稿行的 `status` 一般是 `publish`。各种状态对访客、作者和其他用户的效果见[文章的可见性](../../guide/content/visibility.md)。

## comments {#comments}

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `coid` | `int(10) unsigned` 自增 | | 主键 |
| `cid` | `int(10) unsigned` | `0` | 所属内容的 `contents.cid` |
| `created` | `int(10) unsigned` | `0` | 提交时间，Unix 时间戳 |
| `author` | `varchar(150)` | `NULL` | 评论者名字；引用通告为来源站点名 |
| `authorId` | `int(10) unsigned` | `0` | 登录用户发表的评论为其 `users.uid`，访客为 `0` |
| `ownerId` | `int(10) unsigned` | `0` | 被评论内容的作者 `users.uid` |
| `mail` | `varchar(150)` | `NULL` | 邮箱 |
| `url` | `varchar(255)` | `NULL` | 网址；引用通告为来源地址 |
| `ip` | `varchar(64)` | `NULL` | IP 地址 |
| `agent` | `varchar(511)` | `NULL` | User-Agent |
| `text` | `text` | | 评论内容 |
| `type` | `varchar(16)` | `'comment'` | 类型，见下表 |
| `status` | `varchar(16)` | `'approved'` | 状态，见下表 |
| `parent` | `int(10) unsigned` | `0` | 回复的评论的 `coid`，`0` 为直接评论 |

索引：`cid`、`created`。

### comments.type

| 值 | 含义 |
|---|---|
| `comment` | 评论 |
| `trackback` | 引用通告 |
| `pingback` | Pingback（通过 XML-RPC 收到） |

### comments.status

| 值 | 后台显示 | 含义 |
|---|---|---|
| `approved` | 已通过 | 前台显示，计入 `contents.commentsNum` |
| `waiting` | 待审核 | 等待审核，只有提交者本人在前台能看到 |
| `spam` | 垃圾 | 垃圾评论 |

## metas {#metas}

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `mid` | `int(10) unsigned` 自增 | | 主键 |
| `name` | `varchar(150)` | `NULL` | 名称 |
| `slug` | `varchar(150)` | `NULL` | 缩略名 |
| `type` | `varchar(32)`（PostgreSQL 为 `VARCHAR(16)`） | 无 | `category` 分类、`tag` 标签 |
| `description` | `varchar(150)` | `NULL` | 描述 |
| `count` | `int(10) unsigned` | `0` | 下面已发布文章的数量 |
| `order` | `int(10) unsigned` | `0` | 分类的排序 |
| `parent` | `int(10) unsigned` | `0` | 父分类的 `mid`，`0` 为顶级分类；标签总是 `0` |

索引：`slug`。默认分类的 `mid` 保存在选项 `defaultCategory` 中。

## relationships {#relationships}

| 字段 | 类型 | 说明 |
|---|---|---|
| `cid` | `int(10) unsigned` | `contents.cid` |
| `mid` | `int(10) unsigned` | `metas.mid` |

主键（SQLite 为唯一索引）：`(cid, mid)`。文章与分类、标签是多对多关系，每一对占一行。草稿和修订版本也有自己的关系行。

## fields {#fields}

1.3 中自定义字段的表，每个内容的每个字段一行。

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `cid` | `int(10) unsigned` | | `contents.cid` |
| `name` | `varchar(150)` | | 字段名 |
| `type` | `varchar(8)` | `'str'` | `str`、`int`、`float`、`json` |
| `str_value` | `text` | | `str` 和 `json` 类型的值（`json` 存编码后的字符串） |
| `int_value` | `int(10)` | `0` | `int` 类型的值，其他类型为 `0` |
| `float_value` | `float`（PostgreSQL、SQLite 为 `real`） | `0` | `float` 类型的值，其他类型为 `0` |

主键（SQLite 为唯一索引）：`(cid, name)`；索引：`int_value`、`float_value`。字段的用法见[模板中的自定义字段](../theme/custom-fields.md)。

## users {#users}

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `uid` | `int(10) unsigned` 自增 | | 主键 |
| `name` | `varchar(32)` | `NULL` | 登录名，**唯一** |
| `password` | `varchar(64)` | `NULL` | 密码哈希。从旧版本或其他程序迁移来的 `$P$` 开头的哈希也能验证 |
| `mail` | `varchar(150)` | `NULL` | 邮箱，**唯一**，也可以用来登录 |
| `url` | `varchar(150)` | `NULL` | 个人主页 |
| `screenName` | `varchar(32)` | `NULL` | 昵称，显示用 |
| `created` | `int(10) unsigned` | `0` | 注册时间 |
| `activated` | `int(10) unsigned` | `0` | 最后活动时间，已登录用户的每次请求都会更新 |
| `logged` | `int(10) unsigned` | `0` | 每次登录时被设为当时 `activated` 的值，即上一次登录期间的最后活动时间 |
| `group` | `varchar(16)` | `'visitor'` | 用户组：`administrator`、`editor`、`contributor`、`subscriber`、`visitor`，见[权限分配表](../../guide/content/users.md#permission-table) |
| `authCode` | `varchar(64)` | `NULL` | 登录凭证，每次登录重新生成 |

唯一索引：`name`、`mail`。

## options {#options}

| 字段 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `name` | `varchar(32)` | | 选项名 |
| `user` | `int(10) unsigned` | `0` | `0` 为全站选项，否则是这个用户的个人选项 |
| `value` | `text` | | 值，数组以 JSON 保存 |

主键（SQLite 为唯一索引）：`(name, user)`。

除[选项名一览](./option-names.md)中的全站选项外，`options` 表中还有：

| `name` | `user` | 内容 |
|---|---|---|
| `theme:主题目录名` | `0` | 主题设置，见[主题设置](../theme/theme-options.md) |
| `plugin:插件名` | `0` | 插件设置，见[插件配置](../plugin/settings.md) |
| `_plugin:插件名` | `0` 或用户 ID | 插件个人设置的默认值 / 某个用户的值 |
| 个人设置的选项名（如 `autoSave`、`markdown`） | 用户 ID | 用户在“个人设置”中保存的值，覆盖同名的全站选项 |

`name` 是 `varchar(32)`，MySQL 和 PostgreSQL 中更长的选项名无法保存。`_plugin:` 前缀占去 8 个字符，所以插件名不要超过 24 个字符。
