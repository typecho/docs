---
title: 界面语言
sidebar_position: 3
---

# 界面语言

Typecho 的界面文字以简体中文写成，安装包中**只有简体中文**。其他语言通过语言包提供：语言包是 gettext 的 `.mo` 文件，放进 `usr/langs/` 后，在后台选择即可。

:::info 测试情况
本页在 Typecho 1.3.0 + Apache + PHP-FPM（PHP 8.5）+ SQLite 上实测：从官方语言包仓库 `github.com/typecho/languages`（提交 `d7fb88a`，2025-08-07）取得 `en_US.po`、`ja_JP.po`，用 `msgfmt` 生成 `.mo`（与该仓库 CI 的做法相同）放入 `usr/langs/`，在后台依次切换为 English、日本語、简体中文；删除正在使用的语言包、删除全部语言包后保存基本设置，观察回退行为。直接下载 Release 中的 `langs.zip` 没有实测。对照的源码是 `var/Widget/Options/General.php`（`getLangs()`）和 `var/Widget/Init.php`。
:::

## 站长：安装和切换语言 {#install}

1. 到 [typecho/languages 的 Release 页面](https://github.com/typecho/languages/releases/tag/ci) 下载 `langs.zip`，里面是所有语言的 `.mo` 文件（由仓库的 CI 自动生成，最近一次更新于 2025-08-07）。目前有：`ceb`、`el_GR`、`en_US`、`es_ES`、`fa_IR`、`fil_PH`、`fr_FR`、`ja_JP`、`pt_BR`、`ru_RU`、`tr_TR`、`ug_CN`、`zh_TW`。
2. 在网站目录下创建 `usr/langs/`（安装包中没有这个目录），把需要的 `.mo` 文件上传进去，例如 `usr/langs/en_US.mo`。**不要改文件名**，文件名就是语言代码。
3. 到“设置 → 基本”，原本没有的“语言”下拉框出现了，选项是“简体中文”加上每个语言包自己的名称（如 `English`、`日本語`）。选择后保存。

实测切换为 English 后，后台菜单变为“Console / Overview / Preferences / Plugins / Appearance……”，页面标题变为“Basic settings”；切换为日本語后为“コントロールパネル / 概要 / 個人設定……”。

要点：

- 语言设置保存在 `lang` 选项中，**对整个站点生效**，所有用户看到的后台都是同一种语言，不能按用户分别设置。
- 主题和插件中用 `_t()` 输出、并且在语言包里有翻译的文字也会改变。实测切换为 English 后，默认主题侧边栏的“最新文章”“最近回复”“搜索”等变成了英文，而语言包中没有收录的文字仍是中文。前台语言主要由主题决定。
- 用命令行安装时，可以用环境变量 `TYPECHO_LANG` 直接设定语言，见[命令行与无人值守安装](../getting-started/cli-install.md)。

### 语言包放在其他目录：`__TYPECHO_LANG_DIR__`

默认从 `网站目录/usr/langs` 读取。想放到别处（例如容器中单独挂载的目录），在 `config.inc.php` 中定义绝对路径：

```php
define('__TYPECHO_LANG_DIR__', '/data/typecho-langs');
```

后台的语言列表和实际加载都从这个目录读取（本页没有实测自定义目录）。常量说明见[配置文件](../operations/config-file.md)。

### 语言包缺失时

- 正在使用的语言包被删除后，不会报错，界面**自动回到简体中文**。实测删除 `en_US.mo` 后前台恢复为中文（源码中后台走同一个加载逻辑）；放回文件后又变回英文。
- 如果 `usr/langs/` 中一个语言包都没有，“语言”下拉框不会显示。这时在“基本设置”中保存任何修改，`lang` 会被清空（实测保存为空值），之后即使放回语言包，也要重新选择一次语言。

## 已知问题：几处提示没有被翻译 {#known-issues}

1.3.0 把后台几条提示中的链接从 `http://` 改成了 `https://`，但现有语言包中对应的原文仍是 `http://`，两者对不上，所以这几句在其他语言下仍显示为中文。实测在 English 下：

- 设置 → 评论 → 评论日期格式：“具体写法请参考 PHP 日期格式写法.”
- 设置 → 阅读 → 文章日期格式：“请参考 PHP 日期格式写法.”
- 编辑用户 → 用户组：“具体的权限分配表请参考这里.”
- 编辑器 → 自定义字段：“自定义字段可以扩展你的模板功能, 使用方法参见 帮助文档”

这需要在语言包仓库中更新翻译，站长这边无法通过设置解决。

## 翻译者：参与翻译 {#translate}

站长只需要 `.mo` 文件；`.po` 文件是给翻译者编辑的源文件。翻译流程（见 typecho/languages 仓库的说明）：

1. Fork [typecho/languages](https://github.com/typecho/languages) 并克隆到本地；
2. 用 [Poedit](https://poedit.net/) 等 gettext 工具编辑对应语言的 `.po` 文件。`messages.pot` 是从 Typecho 源码中提取的全部待翻译字符串，原文是中文；新建语言时可以从 `en_US.po` 开始，再用 `messages.pot` 补齐缺少的条目；
3. 把 `msgid "lang"` 一条的译文设为这门语言自己的名称（例如 `English`、`日本語`），它就是后台下拉框中显示的名字；
4. 本地检查可以用 `msgfmt <语言代码>.po -o <语言代码>.mo` 生成 `.mo`，放进测试站点的 `usr/langs/` 查看效果；
5. 提交 Pull Request。合并后，仓库的 CI 会重新生成 `langs.zip`。

按 `msgfmt --statistics`，`en_US.po` 有 894 条已翻译、`ja_JP.po` 有 860 条，`messages.pot` 中共约 900 条字符串，未翻译的条目在界面上显示中文原文。
