---
title: 获取帮助
sidebar_position: 5
---

# 获取帮助

遇到文档没有解答的问题时，可以通过下面三个渠道求助。后台页面的页脚（登录、注册页除外）有其中两个入口：“支持论坛”和“报告错误”。

| 渠道 | 地址 | 适合 |
|---|---|---|
| 支持论坛 | [forum.typecho.org](https://forum.typecho.org) | 使用问题、主题和插件的用法、经验交流。后台页脚“支持论坛” |
| GitHub Issues | [github.com/typecho/typecho/issues](https://github.com/typecho/typecho/issues) | 程序本身的 Bug 和功能建议。后台页脚“报告错误” |
| Telegram | [t.me/typechodev](https://t.me/typechodev) | 关注开发动态、与开发者交流。这是 Typecho 的 Telegram 频道，列在 Typecho 仓库 `CONTRIBUTING.md` 的 Community 一节 |

:::tip 选对渠道
- “怎么做”“为什么我的站点这样”之类的问题，请先去论坛。
- 确认是 Typecho 程序的 Bug，并且能稳定复现，再到 GitHub Issues 提交。
- 第三方主题或插件的问题，请先联系它的作者。
:::

:::warning 安全漏洞不要公开提交
发现安全漏洞时，请不要在论坛或 Issues 中公开细节，按 Typecho 仓库 `SECURITY.md` 的说明发邮件到 `security@typecho.org`。
:::

## 先自己排查

提问之前，这几步往往能直接找到原因，也能让你的问题描述更准确：

1. **确认版本**：后台页脚显示当前版本号。先升级到最新正式版，看问题是否还在，见[升级](../getting-started/upgrade.md)。
2. **确认环境**：对照[环境要求](../getting-started/requirements.md)检查 PHP 版本和扩展。
3. **排除插件和主题**：禁用全部插件、切换回自带的 `default` 主题，再看问题是否出现。
4. **看错误信息**：记下页面上的报错，以及 PHP 和 Web 服务器的错误日志。

## 如何描述问题

一个能被快速解决的问题，需要让别人能**重现**它。GitHub 上的 Bug 模板（“上报 BUG”）要求包含下面几部分，在论坛提问时也建议按这个结构写：

| 部分 | 写什么 |
|---|---|
| 描述 | 简明地说清楚出了什么问题 |
| 复现方式 | 从哪个页面开始、点了什么、看到了什么，一步一步写 |
| 期望的结果 | 你原本希望看到什么 |
| 截图 | 有条件的话附上截图或录屏 |
| 平台 | 下面 6 项环境信息 |

### 环境信息

| 字段 | 示例 | 去哪里查 |
|---|---|---|
| 操作系统版本 | Ubuntu 24.04 | 服务器上执行 `cat /etc/os-release` |
| Apache/Nginx 版本 | nginx 1.26.2 | `nginx -v` 或 `apachectl -v` |
| 数据库版本 | MySQL 8.0.39 | 连接到 Typecho 使用的数据库后执行 `SELECT VERSION();`（MySQL、MariaDB、PostgreSQL）或 `SELECT sqlite_version();`（SQLite）。`mysql --version` 显示的是客户端版本，不是数据库服务器的版本 |
| PHP 版本 | PHP 8.2.20 | `php -v`。注意命令行与 Web 服务器使用的 PHP 可能不是同一个 |
| Typecho 版本 | 1.3.0 | 后台页脚 |
| 浏览器版本 | Chrome 130 | 浏览器的“关于”页面 |

此外请写明：

- **完整的错误信息**，原样复制文字，不要只发截图；
- **最近做过的改动**，如刚升级、刚启用某个插件、刚换了服务器或域名；
- **已经尝试过的办法**，以及它们的结果。

:::caution
贴配置或日志之前，请删掉数据库密码、`config.inc.php` 中的连接信息、Cookie 等敏感内容。
:::

### 示例

```text
描述：升级到 1.3.0 后，点击“完成升级”页面一直加载，最终 504。

复现方式：
1. 在 1.2.1 站点上用新版文件替换 admin/ var/ install/ index.php install.php
2. 管理员登录后台，自动跳转到升级页
3. 点击“完成升级”，页面持续加载约 60 秒后返回 504

期望的结果：显示“升级已经完成”

平台：
- 操作系统版本：Debian 12
- Apache/Nginx 版本：nginx 1.22.1
- 数据库版本：MariaDB 10.11.6
- PHP 版本：PHP 8.2.20
- Typecho 版本：1.2.1 → 1.3.0
- 浏览器版本：Firefox 131

已尝试：禁用全部插件后重试，结果相同。站点约有 800 个附件。
```

（这个例子的现象与[升级](../getting-started/upgrade.md#已知问题附件达到-100-个的站点升级时可能卡住)页里写到的已知问题一致。）
