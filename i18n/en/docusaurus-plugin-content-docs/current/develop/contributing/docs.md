---
title: Contributing to and Translating the Documentation
sidebar_position: 2
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Contributing to and Translating the Documentation

The source files of this documentation site are in the GitHub repository [typecho/docs](https://github.com/typecho/docs) (`main` branch). Whether you find a mistake, want to add content or want to translate a page into English, you can submit a pull request to this repository. The "Edit this page" link at the bottom of every page points directly to that page's source file.

## Building locally

The site is built with [Docusaurus](https://docusaurus.io/) 3.10.2 and needs Node.js 20 or later and pnpm:

```bash
git clone https://github.com/typecho/docs.git
cd docs
pnpm install

pnpm start                  # preview the Chinese site locally; reloads on changes
pnpm start --locale en      # preview the English site locally
pnpm build                  # build both the Chinese and English versions into build/
```

You must run `pnpm build` once before submitting, and it must:

- exit with code 0;
- produce no warnings in its output (no lines matching `warn` or `deprecat`).

The site sets all three link checks to `throw`: broken page links (`onBrokenLinks`), broken anchors (`onBrokenAnchors`) and broken Markdown file links (`onBrokenMarkdownLinks`) all make the build fail immediately. So you can only link to pages and headings that already exist.

## Page structure

```text
docs/
├── intro.md          introduction (site home page, slug: /)
├── glossary.md       glossary (slug: /glossary)
├── guide/            Guide: Getting Started, Writing & Managing, Settings, Themes & Plugins, Deployment & Operations, FAQ & Help
└── develop/          developer docs (develop/index.md has the slug /develop)
    ├── theme/        theme development (with recipes/, theme recipes)
    ├── plugin/       plugin development
    ├── reference/    reference
    ├── core/         core development
    └── contributing/ contributing
i18n/en/docusaurus-plugin-content-docs/current/   English translations, one file for each file in docs/
```

- A page's URL is determined by its file path; for example, the URL of `docs/develop/core/router.md` is `/develop/core/router`;
- File names are always lowercase English, with words joined by `-`;
- Every directory has a `_category_.json`; `label` is the Chinese category name shown in the sidebar, and `position` determines the order of categories;
- The sidebars are generated automatically from the directories, and there are two of them: "Guide" (`intro`, `guide/`, the glossary) and "Development" (`develop/`);
- Images go in an `img/` subdirectory of the page's directory.

## Front matter

Every page starts with front matter:

```yaml
---
title: Routing
sidebar_position: 3
---
```

| Key | Rule |
|---|---|
| `title` | The Chinese title, shown in the sidebar and the browser title |
| `sidebar_position` | The page's position within its category, starting at 1 |
| `slug` | **Do not set it.** The URL is generated from the file path; only a few pages, such as `intro.md`, `glossary.md` and `develop/index.md`, have a `slug` |

The first line of the body is a level-1 heading identical to `title`. When a heading needs to be linked from other pages, give it a fixed English anchor, for example `## Permission table {#permission-table}`, so that the Chinese and English pages have the same anchor and links do not break because of translation.

## Writing rules

**State only product facts.** A page describes how Typecho 1.3 actually works:

- Do not write about test status or the verification process, such as "tested", "not verified", test environments or test dates;
- Do not mention the old documentation site, or what an earlier version of a page said;
- Write known issues directly as statements about the product, for example "In 1.3.0, …".

Other conventions:

- The body text is in Simplified Chinese; class names, method names, option names, hook names, file paths and commands go in inline code or code blocks and are kept as they are;
- Every code identifier, option name, hook, constant and path that appears on a page must have been checked against the Typecho 1.3 source code; changes that exist only on the development branch after 1.3.0 are not written up as features;
- Terms follow the [glossary](/glossary); add a new term to the glossary before using it;
- Link between pages with relative paths to `.md` files, for example `[Routing](../core/router.md)`.

## English translation

Chinese is the only source text; the English version is translated from it and lives under `/en/`.

### File location

English translations go under `i18n/en/docusaurus-plugin-content-docs/current/` at the same relative path as the Chinese file; for example, the translation of `docs/develop/core/router.md` is `i18n/en/docusaurus-plugin-content-docs/current/develop/core/router.md`. You cannot create a page that exists only in English without a Chinese source file; such a page is not included.

A page that has no English translation yet shows the Chinese original on the English site, with a "This page has not been translated yet" notice at the top; the link in the notice points to this page.

### Translation workflow

1. Pick a page whose Chinese content is final and get the hash of its last commit:

   ```bash
   git log -1 --format=%H -- docs/develop/core/router.md
   ```

2. Generate an English first draft with the prompt template `scripts/translation-prompt.md` in the repository: fill in the source file path, the commit hash from the previous step and the page body, and hand it to machine translation. The template contains the front matter rules, the content rules and the full glossary;
3. Save the result at the corresponding `i18n/en/...` path. A machine translation carries two keys at the end of its front matter, and the page shows a "Machine translated, not yet reviewed" notice at the top:

   ```yaml
   translation: machine
   source_commit: <commit hash of the Chinese source file>
   ```

4. Human review: check that terms, code and links are correct and that the English reads well. When the review is done, delete the `translation: machine` line; if the Chinese page has changed in the meantime, update `source_commit` as well;
5. Run `pnpm build` and confirm that there are no errors or warnings.

### Front matter of translations

Docusaurus **replaces** the front matter of the Chinese source file **entirely** with the translation's front matter; it does not merge them. Therefore:

- Copy structural keys such as `slug`, `sidebar_position`, `sidebar_label` and `id` (if present) from the source file verbatim, without leaving any out. Leaving out `slug: /` turns the URL of the English home page into `/en/intro`, and the build fails;
- Translate only the values of `title`, `description` and `sidebar_label`.

### Links in translations

- Keep relative links and anchors the same as in Chinese; translate only the link text;
- Exception: when the target page has no English translation yet, a relative `.md` link cannot be resolved in the English build and makes the build fail. In that case use the target page's route path instead, for example change `./contributing/index.md` to `/develop/contributing`, and switch it back to the relative link once the target page has been translated;
- When a Chinese heading has `{#id}`, keep it as is.

### Interface text

Interface text such as the navbar, category names and buttons is not in Markdown files but in JSON files under `i18n/en/`:

```bash
pnpm write-translations --locale en
```

This updates `i18n/en/code.json`, `i18n/en/docusaurus-plugin-content-docs/current.json` (category names, version label), `i18n/en/docusaurus-theme-classic/navbar.json` and other files, adding new entries to them; then translate the `message` of each entry.

### Translation progress

```bash
node scripts/i18n-status.mjs     # or pnpm i18n:status
```

This lists the English status of every page and calculates the review coverage:

| Status | Meaning |
|---|---|
| `missing` | There is no English file |
| `machine` | The English file has `translation: machine` |
| `reviewed` | Reviewed (no `translation: machine`) |
| `stale` | The Chinese source file has changed since `source_commit`, or a reviewed page has no `source_commit` |

## Submitting changes

1. Fork [typecho/docs](https://github.com/typecho/docs) and create a branch from `main`;
2. Change or add pages, and run `pnpm build` to confirm it passes;
3. Open a pull request against `main` describing what you changed. When you change a Chinese page that already has an English translation, state in the PR whether the English has been updated too.
