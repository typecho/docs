# Typecho Documentation

This repository is the source of the official Typecho documentation site, [docs.typecho.org](https://docs.typecho.org), built with Docusaurus 3.10.2. Chinese is the source language and is served at `/`; English is served at `/en/` and is translated from the Chinese. The site documents Typecho 1.3 across four layers: installation and operations, using the admin, theme development, and plugin and core development.

## Quick start

Requires Node.js >= 20 and pnpm.

| Command | What it does |
|---|---|
| `pnpm install` | Install dependencies |
| `pnpm start` | Start the dev server (Chinese by default; use `pnpm start --locale en` to preview English) |
| `pnpm build` | Build the Chinese and English static site into `build/` |
| `pnpm serve` | Serve the contents of `build/` locally |
| `pnpm i18n:status` | Report the English translation status of every page and the coverage |

## Repository layout

| Path | Contents |
|---|---|
| `docs/` | The Chinese source, and the only place content is written. `guide/` holds the user guide, `develop/` the developer docs |
| `i18n/en/docusaurus-plugin-content-docs/current/` | English translations: one file per Chinese page, at the same relative path as in `docs/` |
| `src/css/custom.css` | The site's design tokens (colors, fonts, spacing) |
| `scripts/` | Maintainer tooling; see [scripts/README.md](scripts/README.md) |
| [OUTLINE.md](OUTLINE.md) | The information architecture (page tree, directory layout) and the writing rules |

## Writing rules

When you fix or add a page:

- State product facts only. Do not write about test status or how something was verified (test environments, test dates, "not verified", and so on).
- Do not refer to the old documentation site or to earlier versions of a page.
- Write known issues as statements about the product, for example "In 1.3.0, ...".
- Follow the terminology in [docs/glossary.md](docs/glossary.md). Add a new term to the glossary before using it.
- Check every class, option, hook and path against the Typecho source before you write it.
- Name files in English kebab-case. URLs come from the file path, so leave out `slug` unless the page needs one.
- `pnpm build` must exit 0 with no warnings. Broken links and broken anchors fail the build.

## Translating

Chinese is always the source. An English page lives at `i18n/en/docusaurus-plugin-content-docs/current/<same path as in docs/>`.

| Rule | Details |
|---|---|
| Chinese first | A page that exists only under `i18n/`, with no Chinese source file, is ignored by Docusaurus |
| Front matter is replaced | A translated file **replaces** the source front matter entirely. Copy `slug`, `sidebar_position` and `id` verbatim or the build breaks; translate only the values of `title`, `description` and `sidebar_label` |
| `translation: machine` | Add this to an unreviewed machine-translated draft; delete it once a human has reviewed the page |
| `source_commit` | The sha of the last commit that touched the Chinese file: `git log -1 --format=%H -- docs/<path>` |
| Code stays as is | Keep code blocks, class names, option names and hook names unchanged; translate terms as listed in [docs/glossary.md](docs/glossary.md) |
| Links | Keep relative links and anchors the same as in the Chinese page. If the target page has no English version yet, link to its route instead (for example `/develop/contributing`) |

Use the per-page prompt in [scripts/translation-prompt.md](scripts/translation-prompt.md). Run `pnpm i18n:status` for the coverage report: each page is `missing`, `machine`, `reviewed` or `stale`, where `stale` means the Chinese page has changed since its `source_commit` and the translation needs updating.

## Where to ask

| Channel | Use it for |
|---|---|
| [typecho.org](https://typecho.org) | The Typecho website |
| [forum.typecho.org](https://forum.typecho.org) | Questions about using or developing for Typecho |
| [GitHub issues (docs)](https://github.com/typecho/docs/issues) | Errors, missing content or translation problems in the documentation |
| [GitHub issues (Typecho)](https://github.com/typecho/typecho/issues) | Bugs and feature requests for Typecho itself |
