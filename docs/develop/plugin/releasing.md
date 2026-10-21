---
title: 发布插件
sidebar_position: 10
---

# 发布插件

Typecho 目前没有官方维护的插件目录，插件由作者自行发布。常见的做法是放在 GitHub 上：用户从仓库或 Release 下载压缩包，解压后上传到 `usr/plugins/`。

## 发布前检查

- **目录名、命名空间、`@package` 一致**：目录 `Guestbook` 对应命名空间 `TypechoPlugin\Guestbook`，见[插件结构与生命周期](./structure.md)。
- **头注释完整**：描述、`@package`、`@author`、`@version`、`@link`。每次发布前更新 `@version`。
- **写上 `@since`**：用到了 1.3 才有的类和方法时写 `@since 1.3.0`。与主题不同，Typecho **会**按插件的 `@since` 拒绝启用：当前版本低于它时，点击“启用”只会得到“无法在此版本的typecho下正常工作”的提示，见[@since 检查](./structure.md#since-检查)。
- **`implements PluginInterface` 不带反斜线**：先 `use Typecho\Plugin\PluginInterface;`，否则后台会把插件当作“即插即用”而不调用 `activate()`，见[头注释](./structure.md#header)。
- **每个 PHP 文件开头都有** `if (!defined('__TYPECHO_ROOT_DIR__')) exit;`（后台面板文件检查 `__TYPECHO_ADMIN__`）。
- **`deactivate()` 撤销了 `activate()` 的改动**：路由、Action、菜单和面板都删除了，见[路由、Action 与后台面板](./routes-actions.md)。
- **完整走一遍生命周期**：在 `__TYPECHO_DEBUG__` 打开的站点上启用、保存设置、使用每个功能、禁用、再启用，确认没有警告；PHP 7.4 和较新的 PHP 8 都试一遍。用到数据库时，在 MySQL、PostgreSQL、SQLite 上都试一遍，见[数据库操作](./database.md#create-table)。
- **后台每个页面都会执行的回调不能出错**：`admin/menu.php` 的 `navBar`、`admin/common.php` 的 `begin`、`admin/footer.php` 的 `end` 等钩子中一旦抛出异常，所有后台页面都会出错，用户连禁用插件的页面都打不开。在这些回调中捕获异常。

## 语言包

插件界面文字的翻译放在插件目录的 `lang/` 下，随插件一起发布。生成翻译模板、编译 `.mo`、在插件中加载的方法见[主题与插件国际化](../i18n.md)。

## 打包

压缩包解压后应当得到**以插件名命名的一个目录**，里面直接是 `Plugin.php` 等文件：

```text
Guestbook-1.0.0.zip
└── Guestbook/
    ├── Plugin.php
    ├── Action.php
    ├── panel.php
    └── lang/
```

目录名就是插件名：Typecho 用它找到插件类、保存插件设置，改了名就是另一个插件。在 README 中提醒用户：从 GitHub 下载源码压缩包时，解压出的目录名会带上 `-main`、`-master` 这样的后缀，需要改回 `Guestbook` 再上传，否则插件无法启用。在仓库中用 Git 打包可以直接得到正确的结构，并且不包含 `.git` 等文件：

```bash
git archive --format=zip --prefix=Guestbook/ -o Guestbook-1.0.0.zip HEAD
```

开发用的文件（测试脚本、前端源码、`node_modules` 等）不要放进压缩包。用到第三方 PHP 库时，Typecho 不使用 Composer，需要把库的文件一起放进插件目录并自己 `require`。

单文件插件（`usr/plugins/Name.php`）直接发布这一个文件即可。

## 在 GitHub 上发布

1. 仓库的 README 写明：插件名称、功能、兼容的 Typecho 版本和 PHP 版本、需要的 PHP 扩展、安装方法（上传到 `usr/plugins/`、在“控制台 → 插件”中启用）、设置项说明、许可证；
2. 给仓库加上 topic `typecho-plugin`，这是 GitHub 上 Typecho 插件最常用的 topic，用户在[寻找插件与主题](../../guide/extend/finding-plugins.md)中会按它搜索；
3. 每个版本建一个 Release，附上按上文打包的压缩包，在说明中列出改动；
4. 到 [Typecho 论坛](https://forum.typecho.org/) 发帖介绍插件，附上仓库地址。

## 版本升级

用户升级插件时，通常是覆盖插件目录中的文件，**不会**重新启用插件。所以新版本中：

- `activate()` 中新注册的钩子、新添加的路由和面板，在用户禁用再启用插件之前不会生效。有这类改动时，在升级说明中写明“升级后请禁用再启用插件”。禁用会清空插件设置，也要一并提醒；
- 新增的设置项在用户保存一次设置之前没有值，读取时要有默认处理；新增的个人设置在重新启用之前读取会抛出异常，见[插件配置](./settings.md)；
- 删除设置项时保留一个同名的 `Hidden` 元素，否则插件设置页面会出错，见[插件配置](./settings.md#插件升级时删除设置项)；
- 数据表结构有变化时，在代码中检查并补上新字段，不能只依赖 `activate()` 中的建表语句。
