# Typecho 文档

> **English summary.** This repository is the source of the official Typecho documentation at
> [docs.typecho.org](https://docs.typecho.org), built with Docusaurus 3.10.2. It documents Typecho 1.3.
> Chinese is the source language and is served at `/`; the English documentation lives at `/en/`
> and is translated from the Chinese. Content is written in `docs/` first; translations go under `i18n/en/`.
> Run `pnpm install && pnpm start` (Node.js >= 20) to preview; see “翻译” below before translating a page.

本仓库是 Typecho 官方文档站 [docs.typecho.org](https://docs.typecho.org) 的源文件，使用 Docusaurus 3.10.2 构建。中文是源语言，位于 `/`；英文位于 `/en/`，由中文翻译而来。文档覆盖 Typecho 1.3 的四个层次：安装与运维、后台使用、主题开发、插件与核心开发。

## 快速开始

需要 Node.js >= 20 和 pnpm。

| 命令 | 作用 |
|---|---|
| `pnpm install` | 安装依赖 |
| `pnpm start` | 启动本地开发服务器（默认中文；预览英文用 `pnpm start --locale en`） |
| `pnpm build` | 构建中英文静态站点到 `build/` |
| `pnpm serve` | 在本地预览 `build/` 的构建结果 |
| `pnpm i18n:status` | 输出每页英文翻译状态与覆盖率 |

## 目录结构

| 路径 | 内容 |
|---|---|
| `docs/` | 中文源文件，也是唯一写作内容的地方。`guide/` 为使用指南，`develop/` 为开发文档 |
| `i18n/en/docusaurus-plugin-content-docs/current/` | 英文译文，每个中文页对应一个文件，相对路径与 `docs/` 中相同 |
| `src/css/custom.css` | 站点设计 token（颜色、字体、间距） |
| `scripts/` | 维护者工具，见 [scripts/README.md](scripts/README.md) |
| [OUTLINE.md](OUTLINE.md) | 信息架构（页面树、目录布局）与写作规则 |

## 写作规则

修改或新增页面时：

- 只陈述产品事实。不写测试状态或验证过程（“实测”“未验证”、测试环境、测试日期等）。
- 不提旧文档站，也不提页面的旧版本。
- 已知问题直接写成关于产品的陈述，例如“1.3.0 中……”。
- 术语以 [docs/glossary.md](docs/glossary.md) 为准；新术语先加入术语表再使用。
- 写下任何类名、选项、钩子或路径之前，先在 Typecho 源码中核对。
- 文件名用英文 kebab-case；URL 由路径生成，一般不写 `slug`。
- `pnpm build` 必须退出码为 0 且没有警告；断链和断锚点会直接导致构建失败。

## 翻译

中文永远是源。英文页放在 `i18n/en/docusaurus-plugin-content-docs/current/<与 docs/ 相同的路径>`。

| 规则 | 说明 |
|---|---|
| 先有中文 | 只存在于 `i18n/` 下、没有对应中文源文件的页面会被 Docusaurus 忽略 |
| Front matter 整体替换 | 译文的 front matter 会**整体替换**源文件的 front matter：`slug`、`sidebar_position`、`id` 必须逐字复制，否则构建会失败；只翻译 `title`、`description`、`sidebar_label` 的值 |
| `translation: machine` | 机器翻译、尚未人工校对的初稿加上此键；人工校对完成后删除 |
| `source_commit` | 最后一次修改该中文文件的提交 sha：`git log -1 --format=%H -- docs/<path>` |
| 代码不翻译 | 代码块、类名、选项名、钩子名原样保留；术语按 [docs/glossary.md](docs/glossary.md) |
| 链接 | 相对链接和锚点与中文保持一致；目标页还没有英文版时，改用路由路径（如 `/develop/contributing`） |

逐页翻译的提示词模板见 [scripts/translation-prompt.md](scripts/translation-prompt.md)。运行 `pnpm i18n:status` 查看每页状态（`missing` / `machine` / `reviewed` / `stale`）和覆盖率；`stale` 表示中文在 `source_commit` 之后又有改动，需要同步译文。

## 部署

本仓库目前没有配置部署。`pnpm build` 会在 `build/` 生成静态站点，可以用任何静态托管服务提供。

## 求助与反馈

| 渠道 | 用途 |
|---|---|
| [typecho.org](https://typecho.org) | Typecho 官网 |
| [forum.typecho.org](https://forum.typecho.org) | 使用与开发问题讨论 |
| [GitHub issues（文档）](https://github.com/typecho/docs/issues) | 文档错误、缺失内容、翻译问题 |
| [GitHub issues（Typecho）](https://github.com/typecho/typecho/issues) | Typecho 本身的 bug 与功能建议 |
