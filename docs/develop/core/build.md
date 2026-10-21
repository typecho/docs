---
title: 开发环境与前端构建
sidebar_position: 8
---

# 开发环境与前端构建

Typecho 的 PHP 代码不需要编译，改完即可运行；需要构建的只有后台和 `classic-22` 主题的 CSS、JS，以及翻译模板 `messages.pot`。构建工具都在仓库的 `tools/` 目录中。

## 运行开发副本

需要 PHP 7.4 或更高版本，以及任意一种支持的数据库（本地开发用 SQLite 最简单）。环境要求的完整列表见[环境要求](../../guide/getting-started/requirements.md)。

```bash
git clone https://github.com/typecho/typecho.git
cd typecho

# 命令行安装，使用 SQLite
TYPECHO_SITE_URL=http://127.0.0.1:8000 \
TYPECHO_DB_ADAPTER=Pdo_SQLite \
TYPECHO_DB_FILE=$PWD/usr/dev.db \
TYPECHO_USER_NAME=admin \
TYPECHO_USER_PASSWORD=admin123456 \
php install.php

# 用 PHP 内置服务器运行
php -S 127.0.0.1:8000
```

在网站根目录直接运行 `php -S`（不指定路由脚本）即可：存在的文件（`admin/*.php`、CSS、图片）直接返回，不存在的路径交给根目录的 `index.php`，所以前台地址和 `/action/*` 都能工作。命令行安装的全部环境变量见[命令行与无人值守安装](../../guide/getting-started/cli-install.md)。

开发时在 `config.inc.php` 中打开调试模式，出错时显示完整的异常信息和调用栈：

```php title="config.inc.php"
define('__TYPECHO_DEBUG__', true);
```

`config.inc.php`、SQLite 数据库文件、`usr/uploads/`，以及除 `HelloWorld`、`default`、`classic-22` 以外的插件和主题都在 `.gitignore` 中，不会被提交。

## 前端资源构建

### 准备

`tools/package.json` 要求 Node.js 18（`"engines": {"node": "18.x"}`）。依赖中的 `node-sass` 需要在安装时编译原生模块，在更新的 Node.js 版本上会编译失败，所以请使用 Node.js 18：

```bash
cd tools
npm ci          # 按 package-lock.json 安装
```

### npm 脚本

| 命令 | 源文件 | 输出 | 做什么 |
|---|---|---|---|
| `npm run build_js` | `admin/src/js/*.js` | `admin/js/*.js` | 用 uglify-js 逐个压缩 |
| `npm run build_css` | `admin/src/scss/*.scss` | `admin/css/*.css` | 用 node-sass 编译（压缩输出）。以 `_` 开头的文件是被引用的片段，不单独输出，实际生成 `grid.css`、`install.css`、`style.css` |
| `npm run build_css:theme` | `usr/themes/classic-22/static/scss/*.scss` | `usr/themes/classic-22/static/css/*.css` | 编译 `classic-22` 主题的样式。1.3.0 中生成的结果不能使用，改用下文的 Dart Sass 命令 |

`build_css` 还会把 `admin/src/img/icons/`、`admin/src/img/editor/` 中的小图标合成雪碧图，输出 `admin/img/icons.png`、`icons@2x.png`、`editor.png`、`editor@2x.png`，并把坐标写进 CSS。雪碧图的中间文件缓存在 `tools/tmp/`（已被 `.gitignore` 忽略）。

构建结果（`admin/js/`、`admin/css/`、`admin/img/`、主题的 `static/css/`）是提交到仓库中的，发行包直接使用它们；`admin/src/` 和主题的 `static/scss/` 只在仓库中，打包时被去掉。修改了源文件就要运行对应的脚本，并把源文件和构建结果一起提交。压缩器版本不同时，压缩后的 JS 可能与仓库中的版本在变量名等细节上不同，这不影响功能。

1.3.0 中，`classic-22` 的 `style.scss` 使用了 `@use "…/@picocss/pico/scss" with (…)` 语法，而 node-sass 不支持它：`npm run build_css:theme` 会把这一行原样写进输出，生成的 `style.css` 缺少 Pico CSS 的全部样式，不能使用。仓库中的 `classic-22/static/css/style.css` 是用支持该语法的 Dart Sass 编译的（文件开头为 Pico CSS v2.0.6 的版权注释）。修改主题样式时用 Dart Sass 编译。在 `tools/` 中先运行 `npm ci`（安装 Pico CSS），然后在 `tools/` 中运行：

```bash
npx --yes --package=sass sass --style=compressed --no-source-map \
  ../usr/themes/classic-22/static/scss/style.scss \
  ../usr/themes/classic-22/static/css/style.css
```

新版 Dart Sass 编译时会对 Pico CSS 的源文件输出一些弃用警告，不影响结果。把修改后的 SCSS 和生成的 CSS 一起提交。

## Makefile

`tools/Makefile` 中的目标需要在 `tools/` 目录中运行：

| 目标 | 做什么 |
|---|---|
| `make test` | 对 `var/`、`usr/`、`admin/` 下的每个 PHP 文件运行 `php -l`，遇到错误即停止 |
| `make pot` | 运行 `php build_pot.php > messages.pot`，见下文 |
| `make langs` | 克隆 `typecho/languages`，用 `msgfmt` 把每个 `.po` 编译为 `.mo`，复制到 `usr/langs/`（会先删除整个 `usr/langs/`）。需要安装 gettext |
| `make update` | 把 GitHub 上 `typecho/typecho` 的最新代码浅克隆到 `tools/build/`，用 yuicompressor 压缩其中的 CSS 和 `admin/js/` 下的 JS（需要 Java），并对 PHP 文件做语法检查 |
| `make package` | 在 `update` 的结果上删除开发文件，打包成 `build.tar.gz` |
| `make install`、`make upgrade`、`make theme` | 用 `update` 的结果覆盖上一级目录中的对应内容：`install` 覆盖全部文件，`upgrade` 覆盖程序文件，`theme` 覆盖默认主题 |
| `make all` | `update` + `package` + `clear` |
| `make clear` | 删除 `tools/build/` |

`update` 一族的目标总是从 GitHub 获取代码，与本地工作区中的修改无关。正式的发行包不是用 Makefile 生成的，而是由 GitHub Actions 打包，见[贡献代码](../contributing/index.md#release)。

日常开发中最常用的是语法检查。CI 运行的命令与下面相同，可以在提交前在本地执行：

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l -n | (! grep -v "No syntax errors detected" )
```

它在发现任何语法错误时以非零状态退出。注意它会把 PHP 输出的所有其他信息也当作失败，所以要用 CI 覆盖的 PHP 版本（7.4、8.0、8.1、8.2）运行：在更新的 PHP 版本上，`php -l` 会对一些已弃用的写法输出 `Deprecated` 提示，这条命令也会因此失败。

## 生成 messages.pot {#pot}

`messages.pot` 是界面翻译的模板，包含源码中全部需要翻译的字符串。生成方法：

```bash
cd tools
make pot        # 等于 php build_pot.php > messages.pot
```

`build_pot.php` 的工作方式：

- 递归扫描仓库根目录下的**所有** `.php` 文件，包括 `usr/themes/` 和 `usr/plugins/` 中的文件；
- 用 PHP 的词法分析器找出 `_t()`、`_e()` 的第一个字符串参数，以及 `_n()` 的单数、复数两个字符串参数；只提取字面量字符串，变量或拼接出来的文字不会被提取；
- 相同的字符串只输出一次，不输出 `#:` 来源位置注释。

因为会扫描 `usr/` 下的所有文件，请在只有仓库自带插件和主题的干净副本上生成，否则本地安装的第三方插件、主题中的文字也会混进模板。`*.pot`、`*.po`、`*.mo` 都在 `.gitignore` 中，`messages.pot` 不提交到 Typecho 仓库，而是维护在 `typecho/languages` 仓库中，见[翻译 Typecho 界面](../contributing/translate-typecho.md)。主题和插件生成自己的翻译模板见[主题与插件国际化](../i18n.md)。
