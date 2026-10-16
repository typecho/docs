---
title: 升级
sidebar_position: 4
---

# 升级

Typecho 升级分两步：先**替换程序文件**，再在后台**执行升级程序**（更新数据库）。本页以从 1.2.x 升级到 1.3.0 为例。

:::danger 先备份
升级会修改数据库，无法自动回退。开始之前请备份数据库、`usr/` 目录和 `config.inc.php`，做法见[备份](../operations/backup.mdx)。
:::

## 升级入口：控制台的新版本提示

有新版本发布时，编辑和管理员打开后台控制台（“概要”页）会在页面标题下方看到一条提示：

> 您当前使用的版本是 1.2.1
> **官方最新版本是 1.3.0**

第二行链接到官方下载页 [typecho.org/download](https://typecho.org/download)。这条提示的来源：

- 后台通过 `/action/ajax?do=checkVersion`（`Widget\Ajax::checkVersion()`）请求 `https://typecho.org/version.json`，官方发布的版本号比当前版本新时才显示；
- 需要服务器能访问 typecho.org，请求超时为 10 秒，失败时不显示任何提示；
- 结果缓存在浏览器的 `sessionStorage` 里，同一个浏览器会话内不会重复检查。

没看到提示不代表没有新版本，也可以直接去 [GitHub Releases](https://github.com/typecho/typecho/releases) 查看。

## 能否直接升级

1.3 的升级程序只支持从 **1.1.0 及以上**版本升级（`Widget\Upgrade::MIN_VERSION = '1.1.0'`）。

| 当前版本 | 升级路线 |
|---|---|
| 1.2.x | 直接升级到 1.3.0 |
| 1.1.x | **建议先升级到 1.2.1，再升级到 1.3.0**（原因见下） |
| 1.1.0 以前（0.x、1.0） | 必须先升级到 1.2.1，再升级到 1.3.0。直接运行 1.3 的升级程序会提示“请先升级至版本 1.1.0” |

**为什么 1.1 要经过 1.2.1**：1.3 删除了 1.2.1 及以前的全部历史升级脚本，只保留 1.3.0 自己的一步。1.1 站点的版本号能通过 `MIN_VERSION` 检查，但 1.2.1 中的两项表结构调整（`comments.agent` 改为 `varchar(511)`、`comments.url` 改为 `varchar(255)`）在 1.3 中已经不存在，1.1 直接升级到 1.3 时这两列不会被修改。先用 1.2.1 升级一次就能补上。

1.2.1 的安装包在 [GitHub Releases 的 v1.2.1](https://github.com/typecho/typecho/releases/tag/v1.2.1) 页面下载（附件 `typecho.zip`），升级步骤与本页相同。

## 第一步：替换程序文件

1. 下载新版安装包并解压到临时目录：

   ```bash
   wget https://github.com/typecho/typecho/releases/latest/download/typecho.zip
   unzip typecho.zip -d /tmp/typecho-new
   ```

2. 删除网站目录中的旧程序文件，再从新包复制过去（下文以 `/srv/www/typecho` 为网站目录）：

   ```bash
   cd /srv/www/typecho
   rm -rf admin var install index.php install.php
   cp -R /tmp/typecho-new/admin /tmp/typecho-new/var /tmp/typecho-new/install \
         /tmp/typecho-new/index.php /tmp/typecho-new/install.php .
   ```

需要替换的就是这 5 项：

| 替换 | 说明 |
|---|---|
| `admin/` | 后台。改过后台目录名（`__TYPECHO_ADMIN_DIR__`）的站点，请替换你自己命名的那个目录 |
| `var/` | 程序核心 |
| `install/` | 安装程序的建表脚本与表单 |
| `index.php` | 入口 |
| `install.php` | 安装程序 |

| 不要覆盖 | 原因 |
|---|---|
| `config.inc.php` | 保存数据库连接，新包里没有这个文件 |
| `usr/` | 你的主题、插件和上传文件 |

:::tip 关于新主题
新包的 `usr/themes/` 里带有 1.3 新增的官方主题 `classic-22`。想使用它，单独把 `usr/themes/classic-22` 复制到网站目录即可。不要整体覆盖 `usr/themes/default`，否则你对默认主题的修改会丢失。
:::

先删除再复制，是为了清掉新版本已经删除的旧文件。只覆盖不删除，旧文件会残留在 `admin/` 和 `var/` 里。

## 第二步：在后台执行升级

文件替换后，用**管理员**帐号登录后台。只要程序版本比数据库中记录的版本新，访问任何后台页面都会被带到升级页（`admin/upgrade.php`，“备份”页除外，方便你最后再备份一次）：

> **检测到新版本!**
> 您已经更新了系统程序, 我们还需要执行一些后续步骤来完成升级
> 此程序将把您的系统从 **1.2.1** 升级到 **1.3.0**

点击 <kbd>完成升级 »</kbd>，看到“升级已经完成”即表示成功。非管理员帐号不会被带到升级页，所以升级必须由管理员完成。

1.3.0 的升级程序会做这些事：

| 改动 | 说明 |
|---|---|
| 路由 | 重写 `feed` 与 `comment_page` 两条路由，分别交给 `Widget\Feed` 与 `Widget\CommentPage` 处理 |
| 选项改名 | `commentsRequireURL` 改为 `commentsRequireUrl` |
| 草稿 | 已发布内容的草稿（`post_draft` / `page_draft` 且有父级）改为新类型 `revision`（修订版本） |
| 附件数据 | 附件信息由 PHP 序列化格式改为 JSON，每批 100 条 |
| 插件与主题设置 | `plugins`、`actionTable`、`panelTable` 以及所有 `plugin:*`、`theme:*` 选项由 PHP 序列化格式改为 JSON |

各项变化对站点的影响见 [1.3 新特性与变更](./whats-new-1-3.md)。

## 已知问题：附件超过 100 个的站点升级时卡住

**影响范围**：1.3.0 正式版。附件较多（100 个以上）的站点，点击“完成升级”后页面一直加载，直到 PHP 执行超时，升级始终无法完成。

**原因**：升级程序按每批 100 条转换附件数据，但只有遇到需要转换的旧格式附件时才会推进读取位置。如果连续 100 个附件都不需要转换（例如已经是 JSON 格式），读取位置不再前进，同一批数据被反复读取，形成死循环。

**状态**：已在 `master` 分支修复（提交 [`4c97a37`](https://github.com/typecho/typecho/commit/4c97a373664771f4a8a9e8094f9be0fef77f4c70)，“Fix infinite loop in attachment upgrade when batch has no serialized data”），但截至 1.3.0 还没有包含该修复的正式版。

**处理办法**：在执行第二步之前，用修复后的版本替换新程序中的 `var/Utils/Upgrade.php` 这一个文件。该提交只改动了这个循环，不涉及其他文件：

```bash
cd /srv/www/typecho
curl -fL -o var/Utils/Upgrade.php \
  https://raw.githubusercontent.com/typecho/typecho/4c97a373664771f4a8a9e8094f9be0fef77f4c70/var/Utils/Upgrade.php
```

如果已经遇到卡住的情况：替换这个文件后重新登录后台，再点一次“完成升级”即可。升级程序在完成前不会更新数据库中的版本号，前面已经执行过的改动可以安全地再执行一次。

## 升级之后

- 到“控制台”确认页脚显示的版本号已经是新版本。
- 逐个检查启用中的插件和当前主题。1.3 把插件、主题设置改为 JSON 存储，并调整了部分内部类，较老的插件可能需要更新。
- 出现空白页或 500 错误时，先禁用插件、切换回自带主题排查。

## 用容器部署的站点

容器中的程序文件来自镜像，升级方式是换用新版本标签的镜像并重建容器，`usr/` 等数据目录要通过挂载保留（哪些目录需要挂载，以镜像说明页 [hub.docker.com/r/joyqi/typecho](https://hub.docker.com/r/joyqi/typecho) 为准）；之后同样要登录后台执行第二步。官方镜像 `joyqi/typecho` 没有 `latest` 标签，请使用带版本号的标签（如 `1.3.0-php8.2-apache`）。
