---
title: 备份与恢复
sidebar_position: 7
---

# 备份与恢复

升级、迁移、换服务器、批量修改数据之前，都应该先备份。一个 Typecho 站点由三部分组成，**完整的备份要三部分都有**：

| 部分 | 内容 | 怎么备份 |
|---|---|---|
| 数据库 | 文章、页面、评论、分类标签、用户，以及**全部设置**（站点设置、当前主题、启用的插件及其配置、永久链接规则） | 数据库自带的导出工具（推荐），或后台“备份”（只含内容，见下文） |
| `usr/` 目录 | 主题、插件、语言包、**上传的附件**；使用 SQLite 时，数据库文件默认也在这里 | 打包文件 |
| `config.inc.php` | 数据库连接和自定义常量 | 复制文件 |

:::caution `usr/` 目录同样需要备份
升级不会覆盖 `usr/`，但这不等于不用备份它：后台“备份”功能**不包含任何文件**，附件、主题和插件只存在于 `usr/` 中；使用 SQLite 时，整个数据库也在 `usr/` 里。升级、迁移前请把 `usr/` 和数据库一起备份，这与[升级](../getting-started/upgrade.md)页的要求一致。
:::

## 后台的“备份”功能 {#builtin}

Typecho 从 1.1 版开始内置备份与恢复，不需要另装备份插件。入口在后台“控制台 → 备份”（`admin/backup.php`），**只有管理员**能使用。

![备份页面](./img/backup.png)

### 包含什么、不包含什么

页面上写着：“此备份操作仅包含**内容数据**, 并不会涉及任何**设置信息**”。按 `var/Widget/Backup.php`，备份文件包含这 6 张表：

| 表 | 内容 |
|---|---|
| `contents` | 文章、页面、附件记录、草稿和修订版本 |
| `comments` | 评论、引用通告 |
| `metas` | 分类、标签 |
| `relationships` | 内容与分类、标签的对应关系 |
| `users` | 用户（含密码的哈希值） |
| `fields` | 自定义字段 |

**不包含**：

- `options` 表，即所有设置：站点名称和地址、阅读与评论设置、永久链接规则、当前主题及主题设置、启用了哪些插件及插件设置；
- 任何文件：附件本身、主题、插件；
- 第三方插件自己建的数据表。插件可以通过 `Widget\Backup` 的 `export` / `import` 接口把自己的数据写进备份文件，是否这样做取决于插件。

### 导出

点击 <kbd>开始备份 »</kbd>，浏览器会下载一个 `.dat` 文件，文件名形如 `20261016_blog.example.com_6ad1dbcfc14a1.dat`（日期、站点域名、随机串）。这是 Typecho 自己的二进制格式，文件首尾都是 `%TYPECHO_BACKUP_0001%` 标记，只能用 Typecho 的“恢复数据”导入，不能用数据库工具导入。

数据量很大时导出可能超时，页面上也建议此时“直接使用数据库提供的备份工具备份数据”。

### 恢复

后台“备份”页右侧的“恢复数据”有两种方式：

| 方式 | 做法 | 适合 |
|---|---|---|
| 上传 | 选择本地的 `.dat` 文件，点击 <kbd>上传并恢复 »</kbd> | 文件不大时。大小受 PHP 的 `upload_max_filesize` 和 `post_max_size` 限制 |
| 从服务器 | 把 `.dat` 文件放到服务器的备份目录，在下拉框中选择后点击 <kbd>选择并恢复 »</kbd> | 文件较大，或上传受限时 |

备份目录默认是 `usr/backups`（目录不存在时自己创建），可以用 `__TYPECHO_BACKUP_DIR__` 常量改成别的绝对路径，页面上的提示“将备份文件手动上传至服务器的 … 目录下后, 这里会出现文件选项”会显示实际路径。只有扩展名为 `.dat` 的文件才会出现在列表中。

```php title="config.inc.php"
define('__TYPECHO_BACKUP_DIR__', '/data/typecho-backups');
```

:::danger 恢复会清空现有内容
确认对话框写着“恢复操作将清除所有现有数据”：对于备份文件中出现的每一张表，Typecho 会先**清空**这张表，再写入备份中的数据。恢复前请先导出一份当前数据。
:::

恢复过程中还会：

- 用备份中的第一个管理员帐号重新登录当前浏览器（因为 `users` 表被替换了），所以恢复后你可能变成另一个管理员身份；
- 在 PostgreSQL 上重置各表的自增序列；
- 成功时提示“数据恢复完成”。

因为设置不在备份里，把备份恢复到一个**新安装**的站点后，还需要重新设置站点信息、永久链接、主题和插件。

恢复只会还原备份中的内容：备份之后删除的文章会回来，但备份之后修改的站点设置（例如站点名称）保持修改后的值，不会被还原。

## 数据库层面的备份 {#database}

要备份**全部数据**（包括设置和插件自己的表），请用数据库自带的工具。下面的命令中，用户名、库名请换成 `config.inc.php` 中的值。

**MySQL / MariaDB**：

```bash
# 导出（会提示输入密码）
mysqldump --single-transaction --default-character-set=utf8mb4 \
  -u typecho -p typecho > typecho-$(date +%Y%m%d).sql

# 导入到一个已创建的空库
mysql --default-character-set=utf8mb4 -u typecho -p typecho < typecho-20261016.sql
```

不要把密码直接写在命令行参数里（如 `--password=...`），它会出现在 shell 历史和进程列表中。需要在脚本里使用时，把密码写进权限为 `600` 的 `~/.my.cnf`。

**PostgreSQL**：

```bash
pg_dump -U typecho -h localhost -Fc typecho > typecho-$(date +%Y%m%d).dump
pg_restore -U typecho -h localhost -d typecho --clean typecho-20261016.dump
```

**SQLite**：数据库就是一个文件，路径见 `config.inc.php` 中的 `'file'`。直接复制时最好先暂停写入（例如临时关闭站点），或者用 `sqlite3` 的在线备份命令：

```bash
sqlite3 /srv/www/typecho/usr/站点数据库.db ".backup '/backup/typecho.db'"
```

## 文件层面的备份 {#files}

```bash
cd /srv/www/typecho
tar -czf /backup/typecho-files-$(date +%Y%m%d).tar.gz usr config.inc.php
```

如果你自己创建过 `.htaccess`，或改过后台目录名、在 `usr/` 之外放了其他文件（例如用 `__TYPECHO_UPLOAD_ROOT_DIR__` 把附件放在别处），也要一并备份。

`admin/`、`var/`、`install/`、`index.php`、`install.php` 是程序文件，可以随时从安装包重新获得，不必备份；但要记下当前的版本号（后台页脚），恢复时使用同一版本的程序。

## 备份建议

- **升级前**：数据库导出 + `usr/` + `config.inc.php`，三样都要。
- **日常**：用定时任务每天导出数据库、每周打包 `usr/`，并把备份复制到另一台机器或对象存储上。和网站放在同一块磁盘上的备份，防不住磁盘故障和服务器被入侵。
- **恢复演练**：偶尔把备份恢复到一个测试站点，确认备份真的能用。
- 备份文件里有用户的密码哈希和评论者的邮箱、IP，请妥善保管；不要放在能通过网页下载的位置。默认的 `usr/backups` 位于网站目录内，用完后请把其中的 `.dat` 文件删除，或按[Web 服务器配置](./web-server.md)中的示例禁止访问这个目录。
