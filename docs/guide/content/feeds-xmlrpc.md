---
title: 订阅源与离线客户端
sidebar_position: 11
---

# 订阅源与离线客户端

Typecho 自带 RSS 2.0、RSS 1.0 和 Atom 三种订阅源（Feed），以及一个 XML-RPC 接口，供离线写作客户端（MetaWeblog 等协议）发布和管理文章，也用于接收 Pingback。

## 订阅源地址 {#feeds}

下表是开启地址重写（伪静态）时的地址；没开启时在站点地址后加 `/index.php`，例如 `https://example.com/index.php/feed/`。

| 内容 | RSS 2.0 | RSS 1.0 | Atom 1.0 |
|---|---|---|---|
| 全站最新文章 | `/feed/` | `/feed/rss/` | `/feed/atom/` |
| 全站最新评论 | `/feed/comments/` | `/feed/rss/comments/` | `/feed/atom/comments/` |
| 某个分类 | `/feed/category/分类缩略名/` | `/feed/rss/category/分类缩略名/` | `/feed/atom/category/分类缩略名/` |
| 某个标签 | `/feed/tag/标签缩略名/` | `/feed/rss/tag/标签缩略名/` | `/feed/atom/tag/标签缩略名/` |
| 某篇文章的评论 | `/feed/archives/文章ID/` | `/feed/rss/archives/文章ID/` | `/feed/atom/archives/文章ID/` |

规律是：在任意页面地址前加 `/feed`（RSS 2.0）、`/feed/rss`（RSS 1.0）或 `/feed/atom`（Atom），就得到这个页面的订阅源，作者页、日期归档也一样。三种格式分别返回 `application/rss+xml`、`application/rdf+xml`、`application/atom+xml`。

主题的 `<head>` 中会输出当前页面对应的三条 `<link rel="alternate">`，浏览器和阅读器可以自动发现。

### 哪些文章进入订阅源

订阅源每次输出最新的 10 条，只包括同时满足以下条件的文章：

- 公开状态，且发布日期已到（定时发布的文章到时间后才进入）；
- 编辑器中勾选了“允许在聚合中出现”（`allowFeed`）；
- 没有设置密码。

隐藏、私密、待审核的文章和草稿都不会出现。唯一的例外是作者本人：登录后在同一个浏览器中打开订阅源地址，会看到自己的私密文章（同样要求允许聚合、没有密码）；阅读器和其他访客得到的是未登录时的结果。规则详见[可见性与密码保护](./visibility.md)。

### 评论订阅源 {#comment-feed}

上面的条件只适用于文章订阅源。全站评论订阅源（`/feed/comments/`）输出最新 10 条已通过的评论，不检查评论所属文章的状态和“允许在聚合中出现”：文章改为私密或隐藏、或者取消了聚合之后，它已经通过的评论仍会出现在这里（只有密码保护文章的评论内容显示为“内容被隐藏”）。如果不希望这些评论出现在订阅源中，请把它们改为待审核或删除。

### 全文还是摘要：`feedFullText`

“设置 → 阅读”中的“聚合全文输出”（`feedFullText`）：

| 界面选项 | 值 | 效果 |
|---|---|---|
| 全文输出 | `1`（默认） | 订阅源中是文章全文 |
| 仅输出摘要 | `0` | 文章中有摘要分割线 `<!--more-->` 时，只输出分割线之前的部分，后面附一个指向原文的 `[...]` 链接；**没有分割线的文章仍输出全文** |

所以选择“仅输出摘要”后，还需要在文章中插入摘要分割线（编辑器工具栏按钮或 <kbd>Ctrl</kbd>+<kbd>M</kbd>），见[撰写文章](./posts.md#markdown)。

## XML-RPC 接口 {#xmlrpc}

“设置 → 基本”中的“XMLRPC 接口”（`allowXmlRpc`）有三个取值，默认为“打开”：

| 界面选项 | 值 | 离线客户端 | 接收 Pingback | 页面输出 |
|---|---|---|---|---|
| 关闭 | `0` | 不可用，接口地址返回 404 | 否 | 不输出 RSD、wlwmanifest、pingback 链接 |
| 仅关闭 Pingback 接口 | `1` | 可用 | 否，调用 `pingback.ping` 返回“requested method pingback.ping does not exist” | 输出 RSD 和 wlwmanifest 链接，不输出 pingback 链接和 `X-Pingback` 响应头 |
| 打开 | `2` | 可用 | 是 | 全部输出，文章页带 `X-Pingback` 响应头 |

不用离线客户端，也不想接收 Pingback 的站点，建议设为“关闭”，减少被扫描和滥用的入口。引用通告（Trackback）不经过 XML-RPC，不受这个选项影响，由每篇文章的“允许被引用”控制，见[评论管理与审核](./comments.md)。

### 连接离线客户端

在客户端中添加博客时，通常需要填写：

| 项目 | 填写 |
|---|---|
| 博客类型 / API | MetaWeblog API（也可以选 WordPress 或 Movable Type，Typecho 同时实现了 `metaWeblog.*`、`blogger.*`、`mt.*` 和大部分 `wp.*` 方法） |
| 接口地址（API URL / Endpoint） | `https://你的站点/index.php/action/xmlrpc`；开启地址重写时也可以用 `https://你的站点/action/xmlrpc` |
| 用户名、密码 | 后台的用户名和密码 |
| Blog ID | 填 `1` 即可 |

- 账号至少要是**贡献者**；贡献者通过客户端发布的文章同样进入待审核。管理页面、分类、作者列表的方法需要编辑，`wp.getOptions` / `wp.setOptions` 需要管理员。
- 通过 XML-RPC 登录不会让你在浏览器中的后台登录失效。

:::caution 1.3.0 中自动发现失效，请手动填写接口地址
页面头部输出的 `<link rel="EditURI" href=".../action/xmlrpc?rsd">` 和 wlwmanifest 链接，在 1.3.0 中实际不可用：请求 `?rsd` 和 `?wlw` 返回的是 XML-RPC 的解析错误，而 `?rsd=1`、`?wlw=1` 才能得到正确的 RSD 和清单（原因是空值参数被当作不存在）。依赖“输入网址自动检测”的客户端可能因此识别失败，请直接填写上表中的接口地址。
:::

:::caution 1.3.0 中通过客户端编辑旧文章会新建文章
1.3.0 的 XML-RPC 编辑接口有一个已知问题：用 `metaWeblog.editPost` 修改已有文章时，不会改动原文，而是**另外新建一篇**，原文保持不变。`wp.editPost`、`wp.editPage` 使用同一段代码，受同样影响。这个问题在 1.3.0 之后的代码中已修复，1.3.0 正式版仍受影响。使用 1.3.0 时，修改已发布的文章请到网页后台进行。已经产生了重复文章时，先在后台把重复文章中的修改复制到原文，确认原文正确后，再删除重复的那一篇。
:::

### 在客户端中写 Markdown {#markdown}

默认情况下，客户端提交的内容按 HTML 保存，客户端读取文章时得到的也是转换后的 HTML。如果你的客户端本身用 Markdown 写作，请在[个人设置](./profile.md#writing)中**同时**打开“使用 Markdown 语法编辑和解析内容”（`markdown`）和“在 XMLRPC 接口中使用 Markdown 语法”（`xmlrpcMarkdown`）。只打开后者时，内容不带 Markdown 标记，前台会把 Markdown 原文当作普通文本显示。两项都打开后，用你的账号提交的内容按 Markdown 保存（正文带 `<!--markdown-->` 标记），客户端读取时得到 Markdown 原文。这个选项按用户分别设置。

新文章的“允许评论 / 被引用”：客户端指定了就用客户端的值，没有指定时取你个人设置中的“默认允许”；“在聚合中出现”总是取个人设置。

### PHP 8.4 及以上版本

Typecho 使用的 XML-RPC 库在 PHP 8.4、8.5 上会产生“Deprecated”提示。打开 `display_errors` 时，这些提示被直接输出到 XML 响应的前面，客户端会因为响应不是合法的 XML 而报错。生产环境请保持 `display_errors = Off`（PHP 的默认生产配置就是如此），提示会只写入日志。
