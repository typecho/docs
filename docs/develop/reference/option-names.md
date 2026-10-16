---
title: 选项名一览
sidebar_position: 6
---

# 选项名一览

Typecho 的设置都保存在数据库 `options` 表中，每行一个选项：`name`（选项名）、`user`（`0` 为全站设置，其他值为该用户的个人设置）、`value`（值，统一以字符串保存）。模板和插件中用 `$this->options->选项名` 读取，见 [$this->options](./options.md)。

:::note 本页的生成方式
选项名和默认值取自 Typecho 1.3.0 `install.php` 中的 `install_get_default_options()`：安装时把这个函数返回的 61 项写入 `options` 表（只把 `installed` 改为 `1`）。所属设置页按 `var/Widget/Options/*.php` 和 `var/Widget/Users/Profile.php` 中保存它们的代码确定。
:::

## 类型的写法

表中“类型”一栏说明值的含义；数据库中一律是字符串。

| 写法 | 含义 |
|---|---|
| `0/1` | 开关，`1` 为开启。读取时得到字符串 `'0'` / `'1'`，可以直接用于 `if` 判断 |
| `int` | 整数（字符串形式） |
| `string` | 普通文字 |
| `JSON` | JSON 编码的数组。`plugins`、`routingTable`、`actionTable`、`panelTable` 通过 `$this->options` 读取时已经解码为数组；`theme:…` 中的值被合并成普通选项 |

## 全部默认选项

| 选项名 | 类型 | 所属设置页 | 默认值 | 说明 |
|---|---|---|---|---|
| `theme` | string | [外观](../../guide/extend/themes.md) | `default` | 当前主题目录名 |
| `theme:default` | JSON | 外观 → 设置外观 | `{"logoUrl":"","sidebarBlock":[…5 项]}` | `default` 主题的设置；其他主题为 `theme:目录名`，启用时生成、切换时删除 |
| `timezone` | int | [基本设置](../../guide/settings/general.md) | `28800` | 时区，与 GMT 相差的秒数 |
| `lang` | string | [基本设置](../../guide/settings/general.md) | `zh_CN`（命令行安装可用 `TYPECHO_LANG` 指定） | 界面语言，`zh_CN` 或语言包文件名 |
| `charset` | string | 无 | `UTF-8` | 页面编码 |
| `contentType` | string | 无 | `text/html` | 页面内容类型 |
| `gzip` | 0/1 | 无 | `0` | 没有任何代码读取这个选项 |
| `generator` | string | 无 | `Typecho 1.3.0` | 版本标识，升级时更新 |
| `title` | string | [基本设置](../../guide/settings/general.md) | `Hello World` | 站点名称 |
| `description` | string | [基本设置](../../guide/settings/general.md) | `Your description here.` | 站点描述 |
| `keywords` | string | [基本设置](../../guide/settings/general.md) | `typecho,php,blog` | 关键词 |
| `rewrite` | 0/1 | [永久链接](../../guide/settings/permalink.md) | `0` | 是否使用地址重写 |
| `frontPage` | string | [阅读设置](../../guide/settings/reading.md) | `recent` | 站点首页：`recent`、`page:页面ID`、`file:文件名` |
| `frontArchive` | 0/1 | [阅读设置](../../guide/settings/reading.md) | `0` | 同时修改文章列表页路径 |
| `commentsRequireMail` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 必须填写邮箱 |
| `commentsWhitelist` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 评论者之前须有评论通过了审核 |
| `commentsRequireUrl` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 必须填写网址 |
| `commentsRequireModeration` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 所有评论必须经过审核 |
| `plugins` | JSON / serialize | [插件](../../guide/extend/plugins.md) | `a:0:{}` | 已启用的插件及其注册的钩子；安装时的初始值是 PHP 序列化的空数组 `a:0:{}`，之后以 JSON 保存 |
| `commentDateFormat` | string | [评论设置](../../guide/settings/discussion.md) | `F jS, Y \a\t h:i a` | 评论日期格式 |
| `siteUrl` | string | [基本设置](../../guide/settings/general.md) | 安装时的地址 | 站点地址，安装时填写；不带末尾 `/` |
| `defaultCategory` | int | 管理 → 分类 | `1` | 默认分类的 ID |
| `allowRegister` | 0/1 | [基本设置](../../guide/settings/general.md) | `0` | 是否允许注册 |
| `defaultAllowComment` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `1` | 新文章默认允许评论 |
| `defaultAllowPing` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `1` | 新文章默认允许被引用 |
| `defaultAllowFeed` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `1` | 新文章默认出现在聚合中 |
| `pageSize` | int | [阅读设置](../../guide/settings/reading.md) | `5` | 每页文章数目 |
| `postsListSize` | int | [阅读设置](../../guide/settings/reading.md) | `10` | 文章列表数目 |
| `commentsListSize` | int | [评论设置](../../guide/settings/discussion.md) | `10` | 评论列表数目 |
| `commentsHTMLTagAllowed` | string | [评论设置](../../guide/settings/discussion.md) | 空 | 允许使用的 HTML 标签和属性 |
| `postDateFormat` | string | [阅读设置](../../guide/settings/reading.md) | `Y-m-d` | 文章日期格式 |
| `feedFullText` | 0/1 | [阅读设置](../../guide/settings/reading.md) | `1` | 聚合全文输出 |
| `editorSize` | int | 编辑器 | `350` | 编辑器高度（像素），拖动编辑器底部调整后保存 |
| `autoSave` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `0` | 自动保存 |
| `markdown` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `1` | 使用 Markdown 编辑 |
| `xmlrpcMarkdown` | 0/1 | [个人设置](../../guide/content/profile.md)（按用户） | `0` | 在 XMLRPC 接口中使用 Markdown |
| `commentsMaxNestingLevels` | int | [评论设置](../../guide/settings/discussion.md) | `5` | 最多回复层数，2 到 7 |
| `commentsPostTimeout` | int | [评论设置](../../guide/settings/discussion.md) | `2592000` | 自动关闭期限（秒，界面中按天填写） |
| `commentsUrlNofollow` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 对评论者个人主页链接使用 nofollow |
| `commentsShowUrl` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 评论者名称显示时自动加上其个人主页链接 |
| `commentsMarkdown` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 在评论中使用 Markdown 语法 |
| `commentsPageBreak` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 启用评论分页 |
| `commentsThreaded` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 启用评论回复 |
| `commentsPageSize` | int | [评论设置](../../guide/settings/discussion.md) | `20` | 每页评论数 |
| `commentsPageDisplay` | string | [评论设置](../../guide/settings/discussion.md) | `last` | 默认显示 `first` / `last` 页 |
| `commentsOrder` | string | [评论设置](../../guide/settings/discussion.md) | `ASC` | `ASC` 旧的在前 / `DESC` 新的在前 |
| `commentsCheckReferer` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 检查评论来源页 |
| `commentsAutoClose` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 自动关闭评论 |
| `commentsPostIntervalEnable` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 限制同一 IP 的评论间隔 |
| `commentsPostInterval` | int | [评论设置](../../guide/settings/discussion.md) | `60` | 评论间隔（秒，界面中按分钟填写） |
| `commentsShowCommentOnly` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `0` | 仅显示评论, 不显示 Pingback 和 Trackback |
| `commentsAvatar` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 启用 Gravatar 头像 |
| `commentsAvatarRating` | string | [评论设置](../../guide/settings/discussion.md) | `G` | 头像最高评级 `G` / `PG` / `R` / `X` |
| `commentsAntiSpam` | 0/1 | [评论设置](../../guide/settings/discussion.md) | `1` | 开启反垃圾保护 |
| `routingTable` | JSON | [永久链接](../../guide/settings/permalink.md)、[阅读设置](../../guide/settings/reading.md) | 默认路由（JSON） | 路由表；永久链接和“文章列表页路径”修改其中的规则，安装时 25 条 |
| `actionTable` | JSON | 无（插件注册） | `[]` | 插件通过 `Helper::addAction()` 注册的 Action |
| `panelTable` | JSON | 无（插件注册） | `[]` | 插件通过 `Helper::addPanel()` 注册的后台面板 |
| `attachmentTypes` | string | [基本设置](../../guide/settings/general.md) | `@image@` | 允许上传的文件类型，`@image@` 等以逗号连接 |
| `secret` | string | 无 | 随机 | 安装时随机生成的 32 位密钥，用于安全令牌，不要泄露 |
| `installed` | 0/1 | 无 | `1` | 安装完成标记，安装后为 `1` |
| `allowXmlRpc` | 0/1/2 | [基本设置](../../guide/settings/general.md) | `2` | XMLRPC 接口：关闭 / 仅关闭 Pingback / 打开 |

## 个人设置

“所属设置页”为个人设置的六项（`markdown`、`xmlrpcMarkdown`、`autoSave`、`defaultAllowComment`、`defaultAllowPing`、`defaultAllowFeed`），表中的是 `user = 0` 的全站默认值。用户在个人设置中保存后，会另外写入一行 `user = 用户 ID` 的记录，该用户登录时覆盖全站默认值。

## 安装后才出现的选项

下面这些名字不在默认选项中，由站长或插件的操作产生：

| 选项名 | 何时产生 |
|---|---|
| `theme:主题目录名` | 启用某个主题时，按其 `themeConfig()` 的默认值生成；保存“设置外观”时更新；切换到其他主题时删除 |
| `plugin:插件名` | 启用带设置项的插件时生成，保存插件设置时更新，禁用插件时删除 |
| `_plugin:插件名` | 插件的个人设置：启用插件时以 `user = 0` 保存默认值，用户在个人设置中保存后写入 `user = 用户 ID` 的一行；禁用插件时全部删除 |
| 插件自定义的名字 | 插件可以用 `Helper::setOption()` 写入任意选项 |

“设置 → 评论”的表单还会提交 `commentsUniqueIpInterval`、`commentsStopWords`、`commentsIpBlackList` 三个名字，但它们不是默认选项，也没有任何代码读取。
