---
title: 贡献文档与翻译文档
sidebar_position: 2
---

# 贡献文档与翻译文档

本文档站的源文件在 GitHub 的 [typecho/docs](https://github.com/typecho/docs) 仓库（`main` 分支）。发现错误、想补充内容或者把页面翻译成英文，都可以向这个仓库提交 Pull Request。每个页面底部的“编辑此页”链接直接指向该页的源文件。

## 本地构建

文档站用 [Docusaurus](https://docusaurus.io/) 3.10.2 构建，需要 Node.js 20 或更高版本和 pnpm：

```bash
git clone https://github.com/typecho/docs.git
cd docs
pnpm install

pnpm start                  # 本地预览中文站，修改后自动刷新
pnpm start --locale en      # 本地预览英文站
pnpm build                  # 构建中文和英文两个语言版本，输出到 build/
```

提交前必须运行一次 `pnpm build`，要求：

- 退出码为 0；
- 输出中没有警告（没有匹配 `warn` 或 `deprecat` 的行）。

站点把三种链接检查都设为了 `throw`：失效的页面链接（`onBrokenLinks`）、失效的锚点（`onBrokenAnchors`）、失效的 Markdown 文件链接（`onBrokenMarkdownLinks`）都会让构建直接失败。所以只能链接到已经存在的页面和标题。

## 页面结构

```text
docs/
├── intro.md          介绍（站点首页，slug: /）
├── glossary.md       术语表（slug: /glossary）
├── guide/            使用指南：快速开始、撰写与管理、系统设置、外观与插件、部署与运维、常见问题与求助
└── develop/          开发文档（develop/index.md 的 slug 为 /develop）
    ├── theme/        主题开发（含 recipes/ 主题技巧）
    ├── plugin/       插件开发
    ├── reference/    参考
    ├── core/         核心开发
    └── contributing/ 参与贡献
i18n/en/docusaurus-plugin-content-docs/current/   英文译文，与 docs/ 逐文件对应
```

- 页面的地址由文件路径决定，例如 `docs/develop/core/router.md` 的地址是 `/develop/core/router`；
- 文件名一律用英文小写，单词之间用 `-` 连接；
- 每个目录有一个 `_category_.json`，`label` 是侧边栏中显示的中文分类名，`position` 决定分类的顺序；
- 侧边栏由目录自动生成，有两套：“使用指南”（`intro`、`guide/`、术语表）和“开发文档”（`develop/`）；
- 图片放在页面所在目录的 `img/` 子目录中。

## Front matter

每个页面以 front matter 开头：

```yaml
---
title: 路由
sidebar_position: 3
---
```

| 键 | 规则 |
|---|---|
| `title` | 中文标题，显示在侧边栏和浏览器标题中 |
| `sidebar_position` | 页面在所属分类中的顺序，从 1 开始 |
| `slug` | **不要写**。地址由文件路径生成；只有 `intro.md`、`glossary.md`、`develop/index.md` 等少数页面有 `slug` |

正文第一行是与 `title` 相同的一级标题。标题需要被其他页面链接时，给它加上固定的英文锚点，例如 `## 权限分配表 {#permission-table}`，这样中英文页面的锚点一致，链接不会因为翻译而失效。

## 写作规范

**只陈述产品事实。** 页面描述 Typecho 1.3 实际怎样工作：

- 不写测试状态和验证过程，例如“实测”“未验证”、测试环境、测试日期；
- 不提旧的文档站，也不提某个页面以前的版本写了什么；
- 已知问题直接写成关于产品的陈述，例如“1.3.0 中，……”。

其他约定：

- 正文用简体中文；类名、方法名、选项名、钩子名、文件路径和命令放在行内代码或代码块中，保持原样；
- 页面中出现的每个代码标识符、选项名、钩子、常量和路径，都要在 Typecho 1.3 的源码中核对过；只存在于 1.3.0 之后开发分支上的改动不写成功能；
- 术语以[术语表](/glossary)为准，新术语先加入术语表再使用；
- 页面之间用相对路径链接到 `.md` 文件，例如 `[路由](../core/router.md)`。

## 英文翻译

中文是唯一的源文本，英文版由中文翻译而来，地址在 `/en/` 下。

### 文件位置

英文译文放在 `i18n/en/docusaurus-plugin-content-docs/current/` 下与中文相同的相对路径，例如 `docs/develop/core/router.md` 的译文是 `i18n/en/docusaurus-plugin-content-docs/current/develop/core/router.md`。不能新建只有英文、没有中文源文件的页面，这样的页面不会被收录。

还没有英文译文的页面，在英文站中显示中文原文。

### 翻译流程

1. 选一个中文内容已经定稿的页面，取得它最后一次提交的哈希：

   ```bash
   git log -1 --format=%H -- docs/develop/core/router.md
   ```

2. 用仓库中的提示词模板 `scripts/translation-prompt.md` 生成英文初稿：把源文件路径、上一步的提交哈希和页面正文填入模板，交给机器翻译。模板中包含 front matter 规则、内容规则和完整的术语表；
3. 把结果保存到对应的 `i18n/en/...` 路径。机器翻译的译文在 front matter 末尾带有两个键，用于跟踪翻译状态：

   ```yaml
   translation: machine
   source_commit: <中文源文件的提交哈希>
   ```

4. 人工校对：术语、代码、链接是否正确，英文是否通顺。校对完成后删除 `translation: machine` 一行；如果中文页面在此期间有修改，同时更新 `source_commit`；
5. 运行 `pnpm build`，确认没有错误和警告。

### 译文的 front matter

Docusaurus 用译文的 front matter **整体替换**中文源文件的 front matter，不会合并。所以：

- 逐字复制源文件中的 `slug`、`sidebar_position`、`sidebar_label`、`id` 等结构性的键（如果有），一个都不能漏。漏掉 `slug: /` 会让英文首页的地址变成 `/en/intro`，构建失败；
- 只翻译 `title`、`description`、`sidebar_label` 的值。

### 译文中的链接

- 相对链接和锚点与中文保持一致，只翻译链接文字；
- 例外：目标页面还没有英文译文时，相对的 `.md` 链接在英文构建中无法解析，会让构建失败。这时改用目标页的路由地址，例如把 `./contributing/index.md` 改为 `/develop/contributing`，等目标页翻译后再改回相对链接；
- 中文标题带有 `{#id}` 时原样保留。

### 界面文字

导航栏、分类名、按钮等界面文字不在 Markdown 文件中，而在 `i18n/en/` 下的 JSON 文件里：

```bash
pnpm write-translations --locale en
```

它会更新 `i18n/en/code.json`、`i18n/en/docusaurus-plugin-content-docs/current.json`（分类名、版本标签）、`i18n/en/docusaurus-theme-classic/navbar.json` 等文件，在其中补上新增的条目，然后翻译各条目的 `message`。

### 翻译进度

```bash
node scripts/i18n-status.mjs     # 或 pnpm i18n:status
```

逐页列出英文状态，并统计校对覆盖率：

| 状态 | 含义 |
|---|---|
| `missing` | 没有英文文件 |
| `machine` | 英文文件带有 `translation: machine` |
| `reviewed` | 已校对（没有 `translation: machine`） |
| `stale` | 中文源文件在 `source_commit` 之后又有修改，或者已校对的页面没有 `source_commit` |

## 提交改动

1. Fork [typecho/docs](https://github.com/typecho/docs)，从 `main` 新建分支；
2. 修改或新增页面，运行 `pnpm build` 确认通过；
3. 向 `main` 发起 Pull Request，说明改了什么。修改了已有英文译文的中文页面时，在 PR 中说明英文是否已同步更新。
