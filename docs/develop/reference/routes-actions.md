---
title: 默认路由与 Action
sidebar_position: 9
---

# 默认路由与 Action

Typecho 的前台地址由路由表决定，表单提交和 Ajax 请求由 Action 处理。插件添加路由和 Action 的方法见[路由、Action 与后台面板](../plugin/routes-actions.md)。

:::note 本页的生成方式
路由表取自 Typecho 1.3.0 `install.php` 中的 `install_get_default_routers()`（安装时写入选项 `routingTable`），占位符取自 `var/Typecho/Router/Parser.php`，Action 表取自 `var/Widget/Action.php` 中的 `$map`，每个 Action 处理的操作取自对应类的 `action()` 方法。
:::

## 默认路由

安装后路由表中有 25 条路由，按下表的顺序匹配：

| 路由名 | 默认路径 | 处理类 | 方法 | 页面 |
|---|---|---|---|---|
| `index` | `/` | `\Widget\Archive` | `render` | 首页 |
| `archive` | `/blog/` | `\Widget\Archive` | `render` | 首页设为独立页面时的文章列表页 |
| `do` | `/action/[action:alpha]` | `\Widget\Action` | `action` | 所有 Action，见下文 |
| `post` | `/archives/[cid:digital]/` | `\Widget\Archive` | `render` | 文章 |
| `attachment` | `/attachment/[cid:digital]/` | `\Widget\Archive` | `render` | 附件 |
| `category` | `/category/[slug]/` | `\Widget\Archive` | `render` | 分类 |
| `tag` | `/tag/[slug]/` | `\Widget\Archive` | `render` | 标签 |
| `author` | `/author/[uid:digital]/` | `\Widget\Archive` | `render` | 作者 |
| `search` | `/search/[keywords]/` | `\Widget\Archive` | `render` | 搜索结果 |
| `index_page` | `/page/[page:digital]/` | `\Widget\Archive` | `render` | 首页分页 |
| `archive_page` | `/blog/page/[page:digital]/` | `\Widget\Archive` | `render` | 文章列表页分页 |
| `category_page` | `/category/[slug]/[page:digital]/` | `\Widget\Archive` | `render` | 分类分页 |
| `tag_page` | `/tag/[slug]/[page:digital]/` | `\Widget\Archive` | `render` | 标签分页 |
| `author_page` | `/author/[uid:digital]/[page:digital]/` | `\Widget\Archive` | `render` | 作者分页 |
| `search_page` | `/search/[keywords]/[page:digital]/` | `\Widget\Archive` | `render` | 搜索结果分页 |
| `archive_year` | `/[year:digital:4]/` | `\Widget\Archive` | `render` | 按年归档 |
| `archive_month` | `/[year:digital:4]/[month:digital:2]/` | `\Widget\Archive` | `render` | 按月归档 |
| `archive_day` | `/[year:digital:4]/[month:digital:2]/[day:digital:2]/` | `\Widget\Archive` | `render` | 按日归档 |
| `archive_year_page` | `/[year:digital:4]/page/[page:digital]/` | `\Widget\Archive` | `render` | 按年归档分页 |
| `archive_month_page` | `/[year:digital:4]/[month:digital:2]/page/[page:digital]/` | `\Widget\Archive` | `render` | 按月归档分页 |
| `archive_day_page` | `/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/` | `\Widget\Archive` | `render` | 按日归档分页 |
| `comment_page` | `[permalink:string]/comment-page-[commentPage:digital]` | `\Widget\CommentPage` | `action` | 评论分页 |
| `feed` | `/feed[feed:string:0]` | `\Widget\Feed` | `render` | 订阅源，如 `/feed/`、`/feed/atom/`、`/feed/category/tech/` |
| `feedback` | `[permalink:string]/[type:alpha]` | `\Widget\Feedback` | `action` | 提交评论、引用通告，如 `/archives/1/comment` |
| `page` | `/[slug].html` | `\Widget\Archive` | `render` | 独立页面 |

- `post`、`page`、`category`、`category_page` 四条的路径可以在“设置 → 永久链接”中修改，见[永久链接设置](../../guide/settings/permalink.md)；
- 地址中的 `/index.php` 前缀（未启用地址重写时）不属于路由路径；
- 生成某条路由的地址用 `\Typecho\Router::url('路由名', ['参数' => 值], 前缀)`。

### 匹配规则

- 按表中顺序逐条匹配，使用第一条匹配的路由；
- 处理类抛出代码为 404 的异常时（例如 `/archives/9999/` 没有这篇文章），继续尝试后面的路由，全部不匹配才显示 404 页面；
- 路径末尾的 `/` 可有可无：`/category/tech` 和 `/category/tech/` 都匹配 `category`；
- 匹配到的占位符的值作为请求参数传给处理类，用 `$this->request->get('名字')` 读取。

## 占位符 {#placeholders}

路径中用 `[名字]` 或 `[名字:类型...]` 表示一段可变的内容：

| 写法 | 匹配 | 例子 |
|---|---|---|
| `[name]` | 一个或多个**不含** `/` 的字符，等于 `[name:char]` | `/tag/[slug]/` |
| `[name:类型]` | 一个或多个该类型的字符 | `/archives/[cid:digital]/` |
| `[name:类型:N]` | 正好 N 个该类型的字符；`N` 为 `0` 时表示零个或多个 | `/[year:digital:4]/`、`/feed[feed:string:0]` |
| `[name:类型:最少:最多]` | 最少到最多个该类型的字符 | `/[id:digital:1:6]/` |

| 类型 | 每个字符可以是 |
|---|---|
| `string` | 任意字符，包括 `/` |
| `char` | 除 `/` 以外的任意字符 |
| `digital` | 数字 `0-9` |
| `alpha` | 字母、数字、`_`、`-` |
| `alphaslash` | 字母、数字、`_`、`-`、`/` |
| `split` | 用 `/` 分隔的多段，至少两段，例如 `a/b`、`a/b/c`；带数量时 `[name:split:N]` 表示正好 N+1 段 |

## 核心 Action {#actions}

所有 Action 的地址都是 `/action/名字`，由路由 `do` 交给 `\Widget\Action` 分发。下表是 Typecho 自带的 23 个；插件通过 `Helper::addAction()` 添加的 Action 与它们同名时，会**替换**核心的 Action。

| 名字 | 处理类 | 处理的操作 |
|---|---|---|
| `ajax` | `\Widget\Ajax` | 后台的 Ajax 请求：`do=checkVersion` 检查新版本、`do=feed` 读取官方动态、`do=editorResize` 保存编辑器高度、`do=remoteCallback` |
| `login` | `\Widget\Login` | 后台登录表单 |
| `logout` | `\Widget\Logout` | 退出登录 |
| `register` | `\Widget\Register` | 用户注册 |
| `upgrade` | `\Widget\Upgrade` | 升级程序（POST） |
| `upload` | `\Widget\Upload` | 上传附件；`do=modify&cid=` 替换附件 |
| `service` | `\Widget\Service` | 站点自己发给自己的请求：`do=async` 异步任务、`do=ping` 发送引用通告 |
| `xmlrpc` | `\Widget\XmlRpc` | XML-RPC 接口 |
| `comments-edit` | `\Widget\Comments\Edit` | 管理评论：`do=` `waiting` / `spam` / `approved` / `delete` / `delete-spam` / `get` / `edit` / `reply` |
| `contents-page-edit` | `\Widget\Contents\Page\Edit` | 独立页面：`do=` `publish` / `save` / `delete` / `deleteDraft` / `mark` / `sort` |
| `contents-post-edit` | `\Widget\Contents\Post\Edit` | 文章：`do=` `publish` / `save` / `delete` / `deleteDraft` / `mark` |
| `contents-attachment-edit` | `\Widget\Contents\Attachment\Edit` | 附件：`do=` `update` / `delete` / `clear` |
| `metas-category-edit` | `\Widget\Metas\Category\Edit` | 分类：`do=` `insert` / `update` / `delete` / `merge` / `sort` / `refresh` / `default` |
| `metas-tag-edit` | `\Widget\Metas\Tag\Edit` | 标签：`do=` `insert` / `update` / `delete` / `merge` / `refresh` |
| `options-discussion` | `\Widget\Options\Discussion` | 保存评论设置 |
| `options-general` | `\Widget\Options\General` | 保存基本设置 |
| `options-permalink` | `\Widget\Options\Permalink` | 保存永久链接设置 |
| `options-reading` | `\Widget\Options\Reading` | 保存阅读设置 |
| `plugins-edit` | `\Widget\Plugins\Edit` | 插件：`activate=` 启用、`deactivate=` 禁用、`config=` 保存设置 |
| `themes-edit` | `\Widget\Themes\Edit` | 主题：`change=` 切换、`edit&theme=` 保存模板文件、`config=` 保存主题设置 |
| `users-edit` | `\Widget\Users\Edit` | 用户：`do=` `insert` / `update` / `delete` |
| `users-profile` | `\Widget\Users\Profile` | 个人设置：`do=` `profile` / `options` / `password` / `personal`（插件的个人设置） |
| `backup` | `\Widget\Backup` | 备份：`do=export` 导出、`do=import` 恢复 |

除 `ajax`、`service`、`xmlrpc` 三个外，Action 都通过 `$this->security->protect()` 校验地址中的令牌参数 `_`，所以不能直接拼出地址调用，要用 `$security->getIndex('/action/名字')` 生成。权限要求见[权限分配表](../../guide/content/users.md#permission-table)。
