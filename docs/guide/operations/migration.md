---
title: 从其他博客迁移
sidebar_position: 8
---

# 从其他博客迁移

本页包括三种迁移：从 WordPress 等其他程序导入内容、把 Typecho 搬到新服务器、给 Typecho 换域名。无论哪一种，开始前都请按[备份与恢复](./backup.md)做一次完整备份。

## 从 WordPress 导入 {#wordpress}

Typecho 官方的 WordPress 导入插件 **WordpressToTypecho** 放在插件仓库 [github.com/typecho/plugins](https://github.com/typecho/plugins/tree/master/WordpressToTypecho) 中，**不随 Typecho 安装包发布**。插件版本为 1.0.3 Beta，仓库中最近一次修改是 2021-11-01。

:::warning 插件没有针对 1.3 更新
这个插件 2021 年以后没有再更新，是为 1.3 之前的版本编写的。它可以在 1.3 上启用，“从 WordPress 导入数据”页面也能打开（启用时 PHP 日志中会有一条 `Undefined variable $error` 警告，不影响使用），但导入结果没有针对 1.3 做过调整。请先在测试站点上导入一遍，确认结果后再用于正式站点。
:::

### 插件会做什么

按插件源码（`Action.php`），点击导入后插件会：

1. **先删除当前 Typecho 中的全部文章、页面、附件记录、评论、分类、标签及其关联**。请只在**新安装**的站点上使用；
2. 从 WordPress 的 `posts` 表导入文章和页面，内容 ID 沿用 WordPress 的 ID：
   - 已发布的保持发布；其他状态（草稿、待审、私密等）都写成状态 `draft`。但 1.3 用内容类型 `post_draft` / `page_draft` 表示草稿，插件写入的是“类型 `post` + 状态 `draft`”，与 1.3 的草稿表示方式不同，导入后请逐一检查这些文章；
   - 作者 ID 原样沿用 WordPress 的 `post_author`。WordPress 的用户不会被导入，作者 ID 在 Typecho 中不存在时，文章的作者会显示异常，需要在数据库中改 `typecho_contents` 表的 `authorId`；
3. 从 `term_taxonomy` / `terms` 导入分类和标签，从 `term_relationships` 导入对应关系；
4. 从 `comments` 表导入评论：已批准 → 通过，未批准 → 待审核，垃圾 → 垃圾。

插件**不会**导入：附件文件、WordPress 的用户、WordPress 的设置、自定义字段（post meta）、菜单与小工具。

### 步骤

1. 安装一个新的 Typecho 1.3 站点。WordPress 的 MySQL 数据库要能从这台服务器连接到；插件只支持 MySQL（启用时会检查 `Mysqli` 或 `Pdo_Mysql` 是否可用）。
2. 从插件仓库下载 `WordpressToTypecho` 目录，放到 `usr/plugins/WordpressToTypecho/`。
3. 后台“控制台 → 插件”中启用它，然后进入插件设置，填写 WordPress 数据库的地址、端口、用户名、密码、库名和表前缀（默认 `wp_`）。
4. 打开“控制台”菜单中新增的“从 WordPress 导入数据”，开始导入。数据量大时可能超时，请先调高 PHP 的 `max_execution_time`。
5. 导入完成后：
   - 把 WordPress 的 `wp-content/uploads/` 复制到新服务器**同样的路径**下，并让 Web 服务器能访问到，旧文章里的图片地址就能继续使用；或者按[下文](#replace-urls)批量替换正文中的地址；
   - 到“设置 → 永久链接”设置与原站相同的文章地址格式（可以用自定义格式），以免搜索引擎和外部链接失效；
   - 到“管理 → 分类”和“管理 → 标签”刷新计数，见[下文](#counts)；
   - 导入完成后可以禁用并删除插件。

## 从 Emlog 等其他程序导入 {#others}

Typecho 安装包和官方插件仓库中**没有** Emlog 的导入工具（官方仓库里另有一个 `MagikeToTypecho`，只用于 Magike）。社区有人写过各种转换脚本和插件，可以在 GitHub 上搜索“emlog typecho”等关键词，或到[支持论坛](https://forum.typecho.org)询问。使用第三方工具前请注意：

- 看它最近一次更新的时间，以及是否说明支持 Typecho 1.3；
- 一定先在测试站点上运行，确认文章、分类、评论都正确后，再用于正式站点；
- 转换后同样要处理附件、永久链接和分类计数，做法同上。

## 换服务器（域名不变） {#new-server}

1. 在旧服务器上备份三部分：数据库导出、`usr/`、`config.inc.php`（见[备份与恢复](./backup.md)）。
2. 在新服务器上准备同一版本的 Typecho 程序文件（`admin/`、`var/`、`install/`、`index.php`、`install.php`），版本号以原站点后台页脚为准。
3. 放回 `usr/` 和 `config.inc.php`，并导入数据库。MySQL / PostgreSQL 的数据库要先创建好。
4. 按新环境修改 `config.inc.php`：
   - 数据库地址、用户名、密码有变化时，修改 `addServer()` 中的值；
   - **使用 SQLite 时**，`'file'` 是绝对路径，网站目录换了位置就要同步修改；
   - `__TYPECHO_UPLOAD_ROOT_DIR__`、`__TYPECHO_BACKUP_DIR__`、`__TYPECHO_LANG_DIR__` 等绝对路径常量也要检查。
5. 确认 `usr/uploads/` 对 PHP 进程可写，并按[Web 服务器配置](./web-server.md)配好永久链接规则。
6. 修改本机的 `hosts` 文件，把域名指向新服务器，检查首页、文章、后台登录、上传附件都正常后，再修改 DNS。

数据库和 `config.inc.php` 都原样迁移时，不需要重新运行安装程序。

## 换域名 {#new-domain}

Typecho 的大部分链接根据**当前访问的域名**生成，换域名后文章、分类、后台的链接会自动变成新域名。需要手动处理的是：

| 位置 | 怎么改 |
|---|---|
| 站点地址（选项 `siteUrl`） | 用新域名登录后台，在“设置 → 基本”中修改。后台进不去时直接改数据库，见下文 |
| `config.inc.php` 中的常量 | `__TYPECHO_SITE_URL__`、`__TYPECHO_ROOT_URL__`、`__TYPECHO_URL_PREFIX__`、`__TYPECHO_UPLOAD_URL__`、`__TYPECHO_THEME_URL__`、`__TYPECHO_PLUGIN_URL__` 中写了旧域名的；`__TYPECHO_COOKIE_OPTIONS__` 的 `domain`。见[config.inc.php 与常量](./config-file.md) |
| 文章正文中的图片和内链 | 正文保存的是插入时的完整地址，需要批量替换，见下文 |
| 用户的个人主页地址 | 安装时管理员的主页地址就是站点地址。在“管理 → 用户”或“个人设置”中修改，或改 `typecho_users` 表的 `url` 字段 |
| 主题和插件的设置 | 设置里如果填过完整地址（如 Logo 图片），逐个检查 |

完成后所有用户都需要重新登录：登录 Cookie 的名字与网站地址有关，换了域名旧 Cookie 就失效了。

最后在 Web 服务器上把旧域名 301 跳转到新域名，例如 nginx：

```nginx
server {
    listen 80;
    server_name old.example.com;
    return 301 https://new.example.com$request_uri;
}
```

### 直接修改数据库中的站点地址

下文 SQL 假设表前缀是默认的 `typecho_`，请按 `config.inc.php` 中的实际前缀修改。

```sql
UPDATE typecho_options SET value = 'https://new.example.com'
WHERE name = 'siteUrl' AND user = 0;
```

### 批量替换正文中的地址 {#replace-urls}

适用于换域名、从 HTTP 切换到 HTTPS、改了附件地址前缀等情况。**先备份数据库**，然后执行：

```sql
UPDATE typecho_contents
SET text = REPLACE(text, 'http://old.example.com/', 'https://new.example.com/');

UPDATE typecho_fields
SET str_value = REPLACE(str_value, 'http://old.example.com/', 'https://new.example.com/');
```

- `REPLACE()` 在 MySQL、PostgreSQL 和 SQLite 中都可用。
- 第一条处理文章、页面和修订版本的正文，第二条处理字符串类型的自定义字段。附件记录中保存的是相对路径，不受影响。
- 替换的字符串带上末尾的 `/`，避免误改 `old.example.com.cn` 这类相似的域名。

## 分类、标签的文章数不对 {#counts}

导入数据、恢复备份或手动改过数据库之后，分类和标签旁边显示的文章数可能不对。这个数字保存在 `metas` 表的 `count` 字段里，不会自动重算。

**刷新方法**：到后台“管理 → 分类”（或“管理 → 标签”），勾选要刷新的项目（可以点表头的复选框全选），在“选中项”下拉菜单中点击“刷新”。分类较多时需要等待一会儿。

按源码（`var/Widget/Metas/EditTrait.php` 中的 `refreshCountByTypeAndStatus()`），刷新后的数字是**已发布的文章**数：独立页面、草稿、私密、待审核的文章都不计入。所以刷新后数字比你预期的少，可能是正常的。

刷新标签**不会**删除计数为 0 的空标签。源码（`var/Widget/Metas/Tag/Edit.php` 中的 `clearTags()`）本意是在刷新后顺带清理不再关联任何内容的标签，但 1.3.0 中查询条件把类型写成了 `tags`（应为 `tag`），实际什么都不删，刷新后空标签仍然存在。空标签需要在“管理 → 标签”中手动选中删除，见[分类与标签](../content/categories-tags.md#counts)。
