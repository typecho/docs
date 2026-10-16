---
title: 1.3 新特性与变更
sidebar_position: 5
---

# 1.3 新特性与变更

Typecho 1.3.0 于 2026 年 1 月 20 日发布。本页面向站长，列出从 1.2 升级到 1.3 后能直接感受到的变化，以及需要留意的改名和移除项。升级步骤见[升级](./upgrade.md)。

:::note
本页只列 1.3.0 正式版中的内容。`master` 分支上在 1.3.0 之后合并的改动（例如一次选择多个附件上传）尚未发布，不在此列。
:::

## 新特性

| 特性 | 说明 |
|---|---|
| 修订版本 | 编辑已经发布的文章或页面并保存时，改动保存为一份“修订版本”（新内容类型 `revision`），已发布的内容保持不变，直到你再次发布 |
| 页面树 | 独立页面可以设置父页面，组成多级结构。“管理 → 独立页面”中会显示每个页面的子页面数量，点击进入子页面列表（`manage-pages.php?parent=`），也可以直接为某个页面新增子页面 |
| 新上传器 | 附件上传改用新的实现，不再依赖 plupload / moxie（以及其中的 Flash 文件 `Moxie.swf`）。支持把文件拖进上传区域；单个文件的大小上限取自 PHP 的 `upload_max_filesize` |
| 新官方主题 classic-22 | 基于 Pico CSS 2 的新主题，与 `default` 一起随安装包提供，位于 `usr/themes/classic-22` |
| Ctrl/Cmd+S 保存 | 在 Markdown 编辑器中按 <kbd>Ctrl</kbd>+<kbd>S</kbd>（macOS 为 <kbd>Cmd</kbd>+<kbd>S</kbd>）保存草稿，不必点按钮 |
| 国际化域名（IDN） | “站点地址”和“个人主页地址”改用网址输入框；以 `xn--` 编码形式保存的中文等国际化域名，在后台会转换回原文显示（需要 PHP `intl` 扩展） |
| 高清头像 | 头像可以额外输出 2 倍、3 倍尺寸的 Gravatar 地址（`srcset`），在高分辨率屏幕上更清晰。后台“管理 → 评论”已默认启用；前台评论列表默认不启用，需要主题在 `listComments()` 中传入 `avatarHighRes => true`，自带的两个主题都没有开启 |
| PostgreSQL SSL | PostgreSQL 连接可以启用 SSL（`sslmode=require`），安装时选择即可 |
| MySQL socket | 安装时“数据库地址”可以填 Unix socket 路径，见[安装](./installation.mdx#使用-mysql-socket) |

## 环境要求提高

1.3 需要 **PHP 7.4.0 或更高版本**（1.2.x 为 7.2.0）。升级前请先确认 PHP 版本，否则替换文件后整个站点都会报语法错误。详见[环境要求](./requirements.md)。

## 改名与移除

升级程序会自动处理数据库中的这些变化。但如果你的主题、插件或自己写的脚本直接使用了旧名字，就需要手动修改。

| 1.2 | 1.3 | 影响 |
|---|---|---|
| 选项 `commentsRequireURL` | `commentsRequireUrl` | 读取 `$this->options->commentsRequireURL` 的主题或插件会读到空值，请改成新名字 |
| 已发布内容的草稿：类型 `post_draft` / `page_draft`（且有父级） | 类型 `revision` | 直接查询 `contents` 表草稿的代码需要同时考虑 `revision`。没有父级的 `post_draft` / `page_draft`（从未发布过的草稿）不变 |
| 路由 `feed` 由 `Widget\Archive` 处理 | 由 `Widget\Feed` 处理 | 订阅地址不变；自定义了路由表或接管订阅输出的插件需要检查 |
| 路由 `comment_page` 由 `Widget\Archive` 处理 | 由 `Widget\CommentPage` 处理 | 评论分页地址不变；同上 |
| 默认主题有 `archive.php` | `usr/themes/default/archive.php` 已删除 | 分类、标签、搜索等归档页改由 `index.php` 输出。升级时只替换程序文件、保留旧的 `usr/themes/default` 的站点不受影响 |
| 附件信息、插件与主题设置用 PHP `serialize` 存储 | 改用 JSON 存储 | 直接读取 `options` 表或附件 `text` 字段的插件需要按 JSON 解析 |

## 升级时的注意事项

- 1.3 只能从 1.1.0 及以上版本升级，1.1 站点建议先升级到 1.2.1，见[升级](./upgrade.md#能否直接升级)。
- 1.3.0 正式版在附件超过 100 个的站点上可能出现升级卡住的问题，处理办法见[升级](./upgrade.md#已知问题附件超过-100-个的站点升级时卡住)。
