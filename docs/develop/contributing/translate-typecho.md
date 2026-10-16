---
title: 翻译 Typecho 界面
sidebar_position: 3
---

# 翻译 Typecho 界面

Typecho 的界面文字以简体中文写在源码中，其他语言由语言包提供。本页面向翻译者，说明语言包仓库的结构、怎样用 Poedit 翻译和更新、怎样提交。站长安装和切换语言包的方法见[界面语言](../../guide/extend/languages.md)。

## typecho/languages 仓库

所有语言包都在 GitHub 的 [typecho/languages](https://github.com/typecho/languages) 仓库（`master` 分支）中：

| 文件 | 内容 |
|---|---|
| `messages.pot` | 翻译模板：从 Typecho 源码中提取的全部待翻译字符串，`msgid` 是中文原文，`msgstr` 为空 |
| `<语言代码>.po` | 各语言的译文，例如 `en_US.po`、`ja_JP.po`、`zh_TW.po` |

目前有 `ceb`、`el_GR`、`en_US`、`es_ES`、`fa_IR`、`fil_PH`、`fr_FR`、`ja_JP`、`pt_BR`、`ru_RU`、`tr_TR`、`ug_CN`、`zh_TW` 共 13 种语言。仓库不保存 `.mo` 文件（在 `.gitignore` 中），它们由 CI 生成。

仓库中有两个工作流：

- **Build mo files**（`ci.yml`）：每次推送到 `master` 时，用 `msgfmt` 把每个 `.po` 编译成同名的 `.mo`，打包为 `langs.zip`，上传到标签为 `ci` 的 Release。站长下载的就是这个文件；
- **Auto-update messages.pot**（`update.yml`）：检出 `typecho/typecho` 的最新代码，用其中的 `tools/build_pot.php` 重新生成 `messages.pot` 并提交。Typecho 发布新版本时会自动触发它，也可以手动运行。

`messages.pot` 的生成规则见[开发环境与前端构建](../core/build.md#pot)。

## 用 Poedit 翻译

[Poedit](https://poedit.net/) 是一个免费的 gettext 翻译编辑器，也可以使用其他支持 `.po` 的工具。

### 更新已有的语言

1. Fork typecho/languages 并克隆到本地；
2. 用 Poedit 打开要修改的 `.po` 文件，例如 `ja_JP.po`；
3. 用菜单 Translation → Update from POT File 选择仓库中的 `messages.pot`，用模板更新译文。更新后：
   - 新增的字符串作为未翻译条目加入；
   - 原文有改动的字符串被标记为 fuzzy（Poedit 中显示为 Needs Work），并带上旧的译文作为参考；
   - 源码中已经不存在的字符串变为废弃（obsolete）条目，不再参与翻译；
4. 翻译未翻译的条目；逐条检查 fuzzy 条目，改好译文后去掉 fuzzy 标记。fuzzy 条目不会被编译进 `.mo`，界面上仍显示中文原文；
5. 保存。

不用 Poedit 时，可以用 gettext 的命令行工具完成同样的合并：

```bash
msgmerge --update ja_JP.po messages.pot   # 用模板更新 .po，原文有改动的条目标记为 fuzzy
msgfmt --check --statistics -o /dev/null ja_JP.po   # 检查格式并统计已翻译、模糊、未翻译的条数
```

### 新增一种语言

1. 以 `en_US.po` 为起点复制一份，文件名改为新语言的代码，例如 `de_DE.po`；也可以在 Poedit 中用 `messages.pot` 新建翻译。从英文开始的好处是可以对照英文译文翻译；
2. 在 Poedit 中设置这个文件的语言（Translation → Properties），然后按上文用 `messages.pot` 更新，补齐 `en_US.po` 中缺少的字符串；
3. 找到 `msgid "lang"` 这一条，把译文设为这门语言**用它自己的文字**写成的名称，例如 `Deutsch`。后台“设置 → 基本”中语言下拉框显示的就是它。

```po
msgid "lang"
msgstr "Deutsch"
```

注意：`%s`、`%d` 等占位符的数量和顺序要与原文一致，HTML 标签要原样保留，只翻译其中的文字。

### 在站点中检查

```bash
msgfmt -o de_DE.mo de_DE.po
```

把生成的 `de_DE.mo` 放进一个 Typecho 站点的 `usr/langs/`（目录不存在就新建），在“设置 → 基本”中选择这门语言，逐页查看后台和默认主题。Poedit 保存时也可以自动生成 `.mo`。

## 提交 Pull Request

把修改后的 `.po` 文件（不要包含 `.mo`）提交到你 Fork 的仓库，然后向 `typecho/languages` 的 `master` 分支发起 Pull Request，在说明中写明改动的语言和内容。合并后，CI 会重新生成 `langs.zip`。

## 已知问题：http 与 https 链接不一致 {#http-https}

1.3.0 把界面中一些带链接的提示文字里的 `http://` 改成了 `https://`，有的还改了文字。gettext 按 `msgid` 的完整文本匹配，而现有的 `.po` 文件中这些条目仍是旧的原文，两者对不上，所以这些文字在任何语言下都显示为中文原文：

| 位置 | 1.3.0 中的原文（节选） |
|---|---|
| 所有后台页面页脚 | `由 <a href="https://typecho.org">%s</a> 强力驱动, 版本 %s` |
| 编辑器 → 自定义字段 | `…使用方法参见 <a href="https://docs.typecho.org/help/custom-fields">帮助文档</a>` |
| 设置 → 评论 → 评论日期格式 | `具体写法请参考 <a href="https://www.php.net/manual/zh/function.date.php">PHP 日期格式写法</a>.` |
| 设置 → 评论 | `对评论者个人主页链接使用 <a href="https://en.wikipedia.org/wiki/Nofollow">nofollow 属性</a>` |
| 设置 → 评论 | `启用 <a href="https://gravatar.com">Gravatar</a> 头像服务, 最高显示评级为 %s 的头像` |
| 设置 → 阅读 → 文章日期格式 | `请参考 <a href="https://www.php.net/manual/zh/function.date.php">PHP 日期格式写法</a>.` |
| 编辑用户 → 用户组 | `具体的权限分配表请<a href="https://docs.typecho.org/develop/acl">参考这里</a>.` |
| 编辑用户、个人设置 → 个人主页地址 | `此用户的个人主页地址, 请用 <code>https://</code> 开头.` |
| 个人设置 → 撰写设置 | 两条含 `<a href="https://daringfireball.net/projects/markdown/">Markdown</a>` 的说明 |
| 安装程序 | `Typecho 基于 <a href="https://www.gnu.org/copyleft/gpl.html">GPL</a> 协议发布…` |
| 默认主题页脚 | `由 <a href="https://typecho.org">Typecho</a> 强力驱动` |
| `classic-22` 主题页脚 | `Powered by <a href="https://typecho.org">Typecho</a>` |

修正方法：按上文用当前的 `messages.pot` 更新 `.po` 文件。这些条目会作为 fuzzy 条目出现，译文仍是旧的、带 `http://` 链接的版本。把译文中的链接改成与原文一致的 `https://`（文字有变化的同时修改文字），去掉 fuzzy 标记，再提交 Pull Request。
