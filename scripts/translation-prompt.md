# Translation prompt: one Chinese doc page → English

Prompt template for translating a single page of the Typecho docs from Chinese
(the source of truth, `docs/**`) into English
(`i18n/en/docusaurus-plugin-content-docs/current/<same relative path>`).
It applies to human translators and to models alike; see OUTLINE.md §5.

## How to use

1. Pick a page under `docs/` whose Chinese content is final.
2. Get its source commit:
   `git log -1 --format=%H -- docs/<path>`
3. Fill in `{{SOURCE_PATH}}`, `{{SOURCE_COMMIT}}` and `{{SOURCE_CONTENT}}` in the
   prompt below and give it to the translator.
4. Write the result to
   `i18n/en/docusaurus-plugin-content-docs/current/<path>` (same file name and
   extension, `.md` stays `.md`, `.mdx` stays `.mdx`).
5. Run `pnpm build` (must exit 0 with no warnings) and
   `node scripts/i18n-status.mjs` (the page must show as `machine`).
6. After a human has reviewed the page, delete the `translation: machine` line
   and update `source_commit` if the Chinese file changed in the meantime.

If the Chinese page uses a new term that is not in the glossary, add the term to
`docs/glossary.md` first (in its own change), then translate.

---

## Prompt

You are translating one page of the Typecho documentation from Simplified
Chinese into English. Typecho is a PHP blogging application. The output is a
Docusaurus Markdown/MDX file. Return only the translated file, nothing else.

Source file: `{{SOURCE_PATH}}`
Source commit: `{{SOURCE_COMMIT}}`

### Front matter rules (the build breaks if you get these wrong)

The translated file REPLACES the source front matter entirely; Docusaurus does
not merge the two. Therefore:

1. Copy every front matter key of the source verbatim, in the same order. In
   particular `slug`, `sidebar_position`, `id` and any other structural key
   must be byte-for-byte identical. A missing `slug: /` turns the English home
   page into `/en/intro` and the build fails under `onBrokenLinks: 'throw'`.
2. Translate only the values of `title`, `description` and `sidebar_label`
   (if present). Keep the keys.
3. Append two keys at the end of the front matter:
   ```yaml
   translation: machine
   source_commit: {{SOURCE_COMMIT}}
   ```
4. If the source has no front matter, create one containing only these two keys.

### Content rules

1. Never translate or alter code: fenced code blocks, inline code, class names
   (`Widget\Archive`), option names (`commentsRequireUrl`), hook names
   (`Widget_Archive:handle`), function and method names, file paths
   (`usr/plugins/`), URLs, configuration keys, CLI commands and their output.
   Comments inside code blocks also stay as they are.
2. Keep all Markdown/MDX structure: headings levels, lists, tables, admonitions
   (`:::tip` etc.), JSX/MDX components and imports, images and their paths.
3. Keep relative links and their targets exactly (`../glossary.md`,
   `#anchor`) and translate only the link text, with one exception: a relative
   `.md`/`.mdx` link whose target has NO English file yet under
   `i18n/en/docusaurus-plugin-content-docs/current/` cannot be resolved in the
   English build (Docusaurus only looks in the localized directory) and fails
   with `onBrokenMarkdownLinks: 'throw'`. Rewrite such a link as the target's
   route path, e.g. `./contributing/index.md` becomes `/develop/contributing`
   (the slug or the path without `index.md`/extension; anchors stay). Mention
   every rewritten link to the reviewer; switch it back to the relative form
   once the target page is translated.
4. If the source sets a custom heading id (`## 标题 {#some-id}`), keep the
   `{#some-id}` unchanged. If another page links to an auto-generated anchor
   of a heading on this page, add an explicit `{#english-id}` so the anchor is
   stable, and tell the reviewer.
5. Use the glossary below for every term it covers. Do not invent alternative
   translations. Terms marked "not translated" stay in English as written.
6. UI labels of the Typecho admin panel: use the English label of the English
   admin UI when you know it; otherwise translate literally and keep the
   Chinese label in parentheses on first mention.
7. Write plain, direct technical English. Do not add, remove or reorder
   content; do not add notes of your own. Prefer "you" over passive voice.
8. Do not create pages that have no Chinese source.

### Glossary (docs/glossary.md, the only source of term translations)

| 中文 | English | 说明 |
|---|---|---|
| 文章 | post | `contents.type = post` |
| 独立页面 | page | 后台菜单“独立页面”；可成树 |
| 附件 / 文件 | attachment | 后台菜单叫“文件” |
| 草稿 | draft | `post_draft` / `page_draft` |
| 修订版本 | revision | 1.3 新类型 |
| 分类 / 标签 | category / tag | 统称 metas，不译作“元数据” |
| 评论 / 引用通告 / Pingback | comment / trackback / pingback | Pingback 不译 |
| 控制台 | dashboard | 后台首页 |
| 永久链接 | permalink | |
| 地址重写（伪静态） | URL rewriting | 首次出现时括注“伪静态” |
| 主题 / 模板 | theme / template | 模板 = 主题中的单个 PHP 文件 |
| 自定义字段 | custom field | |
| 插件 | plugin | |
| 钩子 / 句柄 | hook / handle | 句柄 = `factory()` 的参数 |
| Widget | widget | 不译，保持英文 |
| Action | action | `/action/<name>`，不译 |
| 路由 | route | |
| 面板 | panel | `addPanel` |
| 管理员 / 编辑 / 贡献者 / 关注者 / 访问者 | administrator / editor / contributor / subscriber / visitor | 角色 |
| 站点地址 | site URL | `siteUrl` |
| 语言包 | language pack | `usr/langs` |

### Source content

````markdown
{{SOURCE_CONTENT}}
````
