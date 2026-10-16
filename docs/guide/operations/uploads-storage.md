---
title: 附件存储位置
sidebar_position: 6
---

# 附件存储位置

后台上传的附件默认保存在网站目录的 `usr/uploads/年/月/` 下，文件名是随机数字，例如 `usr/uploads/2026/10/4159619356.png`，访问地址是“站点地址 + 这个路径”。

三个常量可以改变存放位置和访问地址，都写在 `config.inc.php` 中。

:::info 测试情况
本页的路径与地址规则已在 Typecho 1.3.0（源码 `v1.3.0-10`）+ Apache + PHP-FPM（PHP 8.5）上通过后台上传接口实测，包括下文“已知问题”中的删除行为。
:::

## 三个常量

| 常量 | 默认值 | 决定什么 |
|---|---|---|
| `__TYPECHO_UPLOAD_DIR__` | `/usr/uploads` | 附件的**相对路径**前缀。上传时写进附件记录（`path` 字段），之后不会再变 |
| `__TYPECHO_UPLOAD_ROOT_DIR__` | 网站根目录（`__TYPECHO_ROOT_DIR__`） | 相对路径从哪个目录算起，即文件**实际存放**在哪里 |
| `__TYPECHO_UPLOAD_URL__` | 站点地址 | 附件**访问地址**的前缀 |

它们的关系（`var/Widget/Upload.php`）：

```text
文件位置 = UPLOAD_ROOT_DIR + UPLOAD_DIR + /年/月/文件名
记录路径 =                   UPLOAD_DIR + /年/月/文件名
访问地址 = UPLOAD_URL      + 记录路径
```

例如：

```php
define('__TYPECHO_UPLOAD_ROOT_DIR__', '/data/typecho');
define('__TYPECHO_UPLOAD_DIR__', '/files');
define('__TYPECHO_UPLOAD_URL__', 'https://cdn.example.com');
```

上传一张图片后，文件保存在 `/data/typecho/files/2026/10/3810523630.png`，记录路径为 `/files/2026/10/3810523630.png`，地址为 `https://cdn.example.com/files/2026/10/3810523630.png`。

## 常见用法

### 把上传文件放到网站目录之外

适合容器、只读部署或单独的数据盘。设置 `__TYPECHO_UPLOAD_ROOT_DIR__`，并让 Web 服务器把对应的地址映射到这个目录：

```php
define('__TYPECHO_UPLOAD_ROOT_DIR__', '/data/typecho');
```

```nginx
# 访问 /usr/uploads/... 时读取 /data/typecho/usr/uploads/...
location ^~ /usr/uploads/ {
    root /data/typecho;
}
```

（这段 nginx 配置未经实测。）目录需要让 PHP 进程可写；Typecho 会按需逐级创建 `年/月` 子目录，权限为 `0755`。

### 让附件走 CDN

只设置 `__TYPECHO_UPLOAD_URL__`，文件仍保存在原处，由 CDN 回源到你的站点：

```php
define('__TYPECHO_UPLOAD_URL__', 'https://cdn.example.com');
```

### 已有附件怎么办

这三个常量只影响**之后**的上传和地址生成：

- 已有附件的记录路径仍是原来的 `/usr/uploads/...`。改了 `__TYPECHO_UPLOAD_ROOT_DIR__` 后，要把原来的文件按相同的相对路径搬到新目录下，否则后台无法读取和替换它们；
- 已发布的文章正文里，图片地址是插入时的完整地址，不会随 `__TYPECHO_UPLOAD_URL__` 改变，需要的话批量替换正文，做法见[从其他博客迁移](./migration.md#replace-urls)。

## 已知问题：自定义根目录时删除附件不删文件

1.3.0 中删除附件时，Typecho 总是按**网站根目录**拼接路径去删除文件（`var/Widget/Upload.php` 中的 `deleteHandle()`），没有使用 `__TYPECHO_UPLOAD_ROOT_DIR__`。所以设置了这个常量后，在后台删除附件，记录被删掉了，文件却留在磁盘上。实测确认如此。

需要清理时，可以对比磁盘上的文件和数据库中附件记录（`typecho_contents` 表中 `type = 'attachment'` 的行）手动删除。

## 对象存储（S3、OSS、COS 等）

Typecho 本身只会把附件写到本地文件系统。需要把附件存到对象存储时，要用插件：Typecho 在 `Widget\Upload` 上提供了 `uploadHandle`、`modifyHandle`、`deleteHandle`、`attachmentHandle`、`attachmentDataHandle` 五个插件接口，插件接管后，上传、替换、删除和生成地址都由插件完成。

- 选插件时，确认它支持 1.3、并且实现了**删除**接口，否则后台删除附件时对象存储上的文件不会被删除；
- 插件开发者可以参考开发文档中的插件章节（编写中）。

只想让附件**通过 CDN 访问**、文件仍放在服务器上时，不需要插件，用上文的 `__TYPECHO_UPLOAD_URL__` 即可。

## 上传大小限制

单个文件的大小上限由 PHP 的 `upload_max_filesize` 和 `post_max_size` 决定；nginx 还有 `client_max_body_size`（默认 1M）。上传大文件失败时，三处都要检查。
