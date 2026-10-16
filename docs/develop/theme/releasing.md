---
title: 发布主题
sidebar_position: 13
---

# 发布主题

Typecho 没有在维护的官方主题目录，主题由作者自己发布。常见的做法是放在 GitHub 上：用户从仓库或 Release 下载压缩包，上传到 `usr/themes/`。

## 发布前检查

- **`index.php` 头注释完整**：`@package`（名称）、`@author`、`@version`、`@link`，以及第一行的简短描述，见[主题结构与头部信息](./anatomy.md#indexphp-的头注释)。每次发布前更新 `@version`。
- **声明兼容的 Typecho 版本**：写上 `@since 1.3.0` 并在 README 中说明。Typecho 不会根据主题的 `@since` 拒绝启用，它是给用户看的说明；用户判断能否在 1.3 上使用，主要看这一项和仓库的更新时间。
- **截图**：主题根目录放 `screenshot.png`（或 jpg、webp 等），后台主题列表会显示它，见[截图规则](./anatomy.md#截图)。
- **每个 PHP 文件开头都有** `if (!defined('__TYPECHO_ROOT_DIR__')) exit;`。
- **打开调试模式检查**：在 `__TYPECHO_DEBUG__` 下逐一访问首页、文章、页面、分类、标签、搜索、404，确认没有警告；PHP 7.4 和较新的 PHP 8 都试一遍。
- **主题设置的默认值合理**：用户刚启用、还没打开“设置外观”时，主题应当能正常显示。
- **不依赖站长手动建立的东西**：用到某个分类缩略名、页面缩略名的专属模板（如 `category/news.php`）时，在 README 中写明。

## 打包

压缩包解压后应当得到**以主题目录名命名的一个目录**，里面直接是 `index.php` 等文件：

```text
starter-1.0.0.zip
└── starter/
    ├── index.php
    ├── screenshot.png
    └── ...
```

目录名就是主题的标识，主题设置按它保存，所以要固定不变，并在 README 中提醒用户：从 GitHub 下载源码压缩包时，解压出的目录名会带上 `-main`、`-master` 这样的后缀，需要改回 `starter` 再上传。在仓库中用 Git 打包可以直接得到正确的结构，并且不包含 `.git` 等文件：

```bash
git archive --format=zip --prefix=starter/ -o starter-1.0.0.zip HEAD
```

开发用的文件（源码 SCSS、`node_modules`、测试脚本等）不要放进压缩包。

## 在 GitHub 上发布

1. 仓库的 README 写明：主题名称、截图、兼容的 Typecho 版本和 PHP 版本、安装方法（上传到 `usr/themes/`、在“控制台 → 外观”中启用）、主题设置项说明、许可证；
2. 给仓库加上 topic `typecho-theme`，这是 GitHub 上 Typecho 主题最常用的 topic，用户在[寻找插件与主题](../../guide/extend/finding-plugins.md)中会按它搜索；
3. 每个版本建一个 Release，附上按上文打包的压缩包，在说明中列出改动；
4. 到 [Typecho 论坛](https://forum.typecho.org/) 发帖介绍主题，附上仓库地址和截图。

## 版本升级

用户升级主题时，通常是删除旧目录、上传新目录。只要**目录名不变**，用户在“设置外观”中保存的设置会保留（设置在数据库中，不在主题目录里）。新版本中：

- 新增的设置项在用户保存一次设置之前没有值，模板中要有默认处理；
- 改名或删除的设置项，旧值仍留在数据库中；尤其不要使用与站点设置同名的字段，见[主题设置](./theme-options.md)；
- 预置的自定义字段改名后，旧文章中保存的值不会自动迁移。
