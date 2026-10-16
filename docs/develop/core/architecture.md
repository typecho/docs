---
title: 目录结构与命名空间
sidebar_position: 1
---

# 目录结构与命名空间

本节面向修改 Typecho 本身的开发者。本页说明发行包中每个顶层目录和入口文件负责什么，以及 PHP 代码按什么规则组织成命名空间。

## 顶层目录

```text
typecho/
├── index.php          前台唯一入口
├── install.php        安装程序（网页与命令行）
├── config.inc.php     安装时生成的配置文件，不在仓库中
├── install/           三种数据库的建表 SQL 与安装表单
├── admin/             后台页面（PHP 模板）与后台静态资源
├── var/               全部 PHP 类
│   ├── Typecho/       框架层
│   ├── Widget/        应用层
│   ├── Utils/         工具类
│   └── IXR/           XML-RPC 库
├── usr/               站点自己的内容：主题、插件、上传文件、语言包
└── tools/             开发用的构建脚本，不进入发行包
```

### index.php

前台的唯一入口，只有几行：载入 `config.inc.php`（不存在时跳转到 `install.php`），然后依次执行 `\Widget\Init::alloc()`、`index.php:begin` 钩子、`\Typecho\Router::dispatch()`、`index.php:end` 钩子。所有前台地址、`/action/*` 请求、订阅源和 XML-RPC 都从这里进入。完整过程见[请求生命周期](./request-lifecycle.md)。

### install.php 与 install/

`install.php` 是一个独立的安装程序，既能在浏览器中逐步安装，也能在命令行用环境变量无人值守安装（见[命令行与无人值守安装](../../guide/getting-started/cli-install.md)）。它负责检查环境、写出 `config.inc.php`、执行建表脚本、写入默认选项和第一个管理员。

`install/` 目录中是每种数据库的建表脚本 `Mysql.sql`、`Pgsql.sql`、`SQLite.sql`，以及安装界面中对应的连接参数表单 `Mysql.php`、`Pgsql.php`、`SQLite.php`。

### config.inc.php

由安装程序生成，定义 `__TYPECHO_ROOT_DIR__`、`__TYPECHO_PLUGIN_DIR__`、`__TYPECHO_THEME_DIR__`、`__TYPECHO_ADMIN_DIR__` 四个常量，载入 `var/Typecho/Common.php`，调用 `\Typecho\Common::init()`，再创建数据库对象并 `\Typecho\Db::set()`。文件内容见[配置文件](../../guide/operations/config-file.md)。仓库的 `.gitignore` 忽略了它。

### admin/

后台。`admin/*.php` 是一个个页面模板，不是类，也不经过路由：浏览器直接请求 `admin/manage-posts.php` 这样的文件。每个页面先 `include 'common.php'`，再按需 `include` 公共片段（`header.php`、`menu.php`、`copyright.php`、`footer.php` 等），并在页面中直接调用 Widget，例如：

```php title="admin/manage-posts.php"
<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
$posts = \Widget\Contents\Post\Admin::alloc();
```

`admin/common.php` 初始化环境并准备好 `$options`、`$user`、`$security`、`$menu`、`$request`、`$response` 这几个变量，页面模板默认它们已经存在。

静态资源分两部分：`admin/src/`（SCSS、未压缩的 JS、雪碧图的源图标）是源文件，`admin/css/`、`admin/js/`、`admin/img/` 是构建结果，构建方法见[开发环境与前端构建](./build.md)。发行包会去掉 `admin/src/`。

后台目录的名字可以通过 `__TYPECHO_ADMIN_DIR__` 修改，见[修改后台目录](../../guide/operations/admin-dir.md)。

### var/

全部 PHP 类都在这里，按命名空间分为四个目录，见下文。

### usr/

属于站点本身的文件：

| 目录 | 内容 |
|---|---|
| `usr/themes/` | 主题。仓库只跟踪 `default` 和 `classic-22` |
| `usr/plugins/` | 插件。仓库只跟踪 `HelloWorld` |
| `usr/uploads/` | 上传的附件，第一次上传时创建 |
| `usr/langs/` | 语言包 `.mo` 文件，需要手动创建 |
| `usr/backups/` | 备份文件的默认目录 |

用 SQLite 安装时，数据库文件默认也放在 `usr/` 下。升级 Typecho 时不替换 `usr/`。

### tools/

开发工具，不进入发行包：`build.js` 与 `package.json`（构建后台和 `classic-22` 的 CSS/JS）、`build_pot.php`（生成翻译模板 `messages.pot`）、`Makefile`（打包、语法检查等）。见[开发环境与前端构建](./build.md)。

## 命名空间

`var/` 下的每个 PHP 文件都声明了命名空间，命名空间与目录一一对应：

| 命名空间 | 目录 | 职责 |
|---|---|---|
| `Typecho\` | `var/Typecho/` | 框架层：Widget 基类、路由、插件、数据库、请求与响应、Cookie、日期、国际化、表单、HTTP 客户端等，不涉及博客业务 |
| `Widget\` | `var/Widget/` | 应用层：每个页面、数据源、后台操作都是一个 Widget，例如 `Widget\Archive`、`Widget\Contents\Post\Edit` |
| `Utils\` | `var/Utils/` | 与框架无关的工具：`Helper`、`Markdown`、`HyperDown`、`AutoP`、`PasswordHash`、`Upgrade` |
| `IXR\` | `var/IXR/` | XML-RPC 服务端与客户端，供 `Widget\XmlRpc` 和 Pingback 使用 |
| `TypechoPlugin\<插件名>\` | `usr/plugins/<插件名>/` | 插件，见[插件结构与生命周期](../plugin/structure.md) |

### var/Typecho

| 位置 | 内容 |
|---|---|
| `Common.php` | 自动加载器、`_t()`/`_e()`/`_n()` 翻译函数、`Typecho\Common` 静态工具类和版本号 `Common::VERSION` |
| `Widget.php`、`Widget/` | Widget 基类、Widget 级请求与响应（`Widget\Request`、`Widget\Response`），以及表单、分页等辅助类（`Widget\Helper\*`） |
| `Router.php`、`Router/` | 路由器、路由规则解析器 `Router\Parser`、`Router\ParamsDelegateInterface` |
| `Plugin.php`、`Plugin/` | 钩子注册与调用、`Plugin\PluginInterface` |
| `Db.php`、`Db/` | 数据库对象、查询构造器 `Db\Query`、各数据库适配器 `Db\Adapter\*` |
| `Request.php`、`Response.php` | 全局的 HTTP 请求与响应对象 |
| `Http/` | 出站 HTTP 客户端 `Http\Client` |
| `I18n.php`、`I18n/` | gettext `.mo` 文件读取 |
| `Cookie.php`、`Date.php`、`Config.php`、`Validate.php`、`Feed.php` | Cookie、时区与日期、参数对象、表单校验、RSS/Atom 生成 |

### var/Widget

应用层按业务分目录：

| 位置 | 内容 |
|---|---|
| `Base.php`、`Base/` | 所有业务 Widget 的基类 `Widget\Base`，以及四种数据行的基类 `Base\Contents`、`Base\Comments`、`Base\Metas`、`Base\Users` 和 `Base\Options` |
| `Contents/`、`Comments/`、`Metas/`、`Users/` | 各类数据的列表、编辑、后台管理 Widget |
| `Options/`、`Plugins/`、`Themes/` | 设置页、插件管理、主题管理 |
| `Archive.php` | 前台页面的总入口，模板中的 `$this` |
| `Init.php`、`Options.php`、`User.php`、`Security.php`、`Menu.php` | 初始化、站点选项、当前用户、令牌、后台菜单 |
| `Action.php`、`ActionInterface.php` | `/action/<名字>` 的分发器与接口 |
| `Feed.php`、`Feedback.php`、`CommentPage.php`、`XmlRpc.php`、`Service.php`、`Upload.php`、`Backup.php`、`Upgrade.php`、`Login.php`、`Logout.php`、`Register.php`、`Ajax.php`、`Stat.php`、`Notice.php`、`ExceptionHandle.php` | 各自独立的功能 |

Widget 的工作方式见 [Widget 组件模型](./widget.md)。

### 自动加载与旧类名

自动加载器在 `var/Typecho/Common.php` 被载入时注册（文件顶部的 `spl_autoload_register()`），不依赖 Composer：

- 带命名空间的类 `\A\B\C` 从 `var/A/B/C.php` 加载；`\TypechoPlugin\X\Y` 从 `usr/plugins/X/Y.php` 加载；
- 下划线写法的旧类名会被映射到新类名并自动建立别名：`Typecho_Foo_Bar` → `\Typecho\Foo\Bar`，`Widget_Foo` → `\Widget\Foo`，其他 `Foo_Bar` 视为插件类 `\TypechoPlugin\Foo\Bar`；
- 不符合上述机械规则的旧类名（如 `Widget_Abstract_Contents` → `\Widget\Base\Contents`、`Helper` → `\Utils\Helper`）由 `var/Widget/Init.php` 中的常量 `__TYPECHO_CLASS_ALIASES__` 显式列出。

完整的对照表见[旧类名对照表](../reference/class-aliases.md)。修改核心代码时保持这些旧类名可用的要求见[编码规范](./coding-style.md#aliases)。
