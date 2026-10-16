# Typecho Docs — Design

The site should feel like part of Typecho: quiet, warm-neutral, one accent
colour, no ornament. Everything visual lives in `src/css/custom.css`. This file
explains it.

## Provenance

The palette and type are taken from Typecho's admin UI, not invented. The
sources are in `typecho/typecho` → `admin/src/scss/` (compiled to
`admin/css/style.css`).

| Token | Value | Admin source |
|---|---|---|
| accent | `#467B96` | `.primary` button, `a` colour (`style.scss`, `_buttons.scss`) |
| accent hover / strong | `#3c6a81` | `.primary:hover` = `darken(#467B96, 6%)` |
| accent deep | `#39647a` | `.primary:active` = `darken(…, 8%)` |
| accent soft | `#508cab` | `.primary:disabled` = `lighten(…, 6%)` |
| accent tint border | `#D8E7EE` | `a.balloon-button` background |
| link underline | `#A5CADC` | `a.balloon-button:hover` background |
| dark accent | `#6DA1BB` | `$color-nav-child-focus` (`_header.scss`) |
| surface | `#F6F6F3` | `body` background |
| surface strong | `#F0F0EC` | table row borders (`_table.scss`), panel background (`style.scss`) |
| border | `#E9E9E6` | `.btn` background, tabs |
| border strong | `#D9D9D6` | inputs, tabs, buttons |
| text | `#444` | `body` colour |
| heading | `#292D33` | `$color-nav-bg` |
| dark page / surface / border | `#202328` / `#292D33` / `#383D45` | admin header: button hover / nav bg / nav border |
| warning | `#FFF9E8` bg; text darkened from `#8A6D3B` | `.notice` text (`_messages.scss`), highlighted table row (`_table.scss`) |
| tip / warning / danger | softened from `#E6EFC2`/`#264409`, `#FFF6BF`/`#8A6D3B`, `#FBE3E4`/`#8A1F11` | `.success` / `.notice` / `.error` (`_messages.scss`) |
| radius | `2px` | the dominant value: 13 of the 18 `border-radius` declarations |
| font | system stack below | `body` |

There are three deliberate deviations, all for contrast:

- **Muted text.** The admin's `#999` is 2.85:1 on white and fails WCAG AA. It
  becomes `#6B6B66` (5.36:1).
- **Link hover.** The admin uses the *lighter* `#499BC3` (3.11:1). The docs use
  the *darker* `#3c6a81` (5.88:1), the admin's own button-hover colour.
- **Links on tinted surfaces** (active sidebar item, admonitions) use
  `--tc-accent-strong`, because `#467B96` drops to about 4.1–4.3:1 there.

## Tokens

All tokens are `--tc-*` custom properties at the top of `custom.css`, with
light values on `:root` and dark values on `[data-theme='dark']`. Section 2 of
the file maps them onto Infima's `--ifm-*` variables. Change a colour by
changing the token, never the component rule.

| Group | Tokens |
|---|---|
| Accent | `--tc-accent`, `-strong`, `-deep`, `-soft`, `-tint`, `-tint-border`, `--tc-link-underline` |
| Neutrals | `--tc-bg`, `--tc-surface`, `--tc-surface-strong`, `--tc-border`, `--tc-border-strong`, `--tc-border-subtle`, `--tc-text`, `--tc-text-strong`, `--tc-text-muted` |
| Status | `--tc-{note,tip,info,warning,danger}-{fg,bg,border}` |
| Type | `--tc-font-sans`, `--tc-font-mono`, `--tc-text-{xs,sm,md,base}` = 12/13/14/16px, `--tc-leading` 1.75, `--tc-leading-tight` 1.35 |
| Space | `--tc-space-{1,2,3,4,6,8}`, a 4px grid |
| Shape | `--tc-radius` 2px, `--tc-radius-lg` 4px, `--tc-hairline` |

Type scale: h1 32px, h2 24px, h3 19px, h4 16px, all at weight 600. Body text
is 16px with line-height 1.75; CJK text needs more leading than Latin.

## Rules — what to use when

- **Colour.** The accent is for things you can act on (links, the active item,
  focus) and for nothing decorative. Everything else is neutral.
- **Separation.** Use a 1px hairline (`--tc-hairline`). Use `--tc-surface` for
  a recessed area (code, table head, footer). Do not use both a border and a
  shadow.
- **Shadow.** Only on floating layers: dropdowns and search results
  (`--ifm-global-shadow-md`). Never on content, the navbar, cards or code.
- **Radius.** 2px on controls and small things (inline code, badges, buttons,
  sidebar items). 4px on containers (code blocks, admonitions, tables,
  pagination, search box).
- **Text sizes.**
  - 16px: body.
  - 14px: sidebar, tables, code.
  - 13px: TOC, breadcrumbs, metadata, admonition titles.
  - 12px: badges.
  - Avoid anything else.
- **Links in prose** are always underlined: a thin `--tc-link-underline` line
  at 0.22em offset that turns to the full link colour on hover. Colour alone is
  never the only cue.
- **Active states use one signal per place:**
  - Sidebar: tint plus accent text.
  - TOC: accent text plus a 2px left bar.
  - Navbar: accent text plus a 2px bottom bar.
  - Code highlight: tint plus a 2px left bar.
- **h2 starts a section**, with a hairline above it and 2.5rem of space. The
  first h2 after the title has no line.
- **Admonitions.** Tinted background, 1px tinted border and a 3px left bar in
  the title colour. The title is 13px and weight 600, never uppercase.
- **Translation banner** (`[data-i18n-banner]`, from
  `src/theme/DocItem/Content`). It is the same family as the admonitions but
  one size smaller: a single line with a status dot and an inline link. It is
  metadata, not content.
- **Focus.** `:focus-visible` gives a 2px accent outline with a 2px offset
  everywhere. Do not remove outlines.
- **Code themes** are set in `docusaurus.config.js`: Prism `github` (light)
  and `oneDark` (dark, with its text shadow removed). Their backgrounds are
  replaced with `--tc-surface` (`#F6F6F3` / `#292D33`).

## Do not

- **Webfonts.** No `@font-face` and no Google Fonts. The stack is the admin's:
  `-apple-system, BlinkMacSystemFont, Helvetica Neue, PingFang SC, Microsoft YaHei, Source Han Sans SC, Noto Sans CJK SC, WenQuanYi Micro Hei, sans-serif`,
  plus `Menlo, Monaco, Consolas, "Courier New", monospace` for code.
- **Heavy shadows, gradients, glass or blur.**
- **Animation.** Colour and border transitions of 120ms at most are allowed.
  Under `prefers-reduced-motion: reduce` every transition and animation is
  switched off.
- **New hues.** Add a token derived from the admin palette first, and record
  its provenance here.
- **UI libraries or CSS dependencies.**
- **Forking theme components.** Wrap them, as `DocItem/Content` does. Selectors
  on hashed CSS-module classes use `[class*='…']`, and only where Docusaurus
  exposes no stable `theme-*` class.
- **Layout changes** to the two-sidebar structure or the doc column.

## Measured contrast (WCAG 2.1)

These values are computed from the rendered pages in headless Chrome.

| | Light | Dark |
|---|---|---|
| Body text | `#444` / `#fff` **9.74** | `#D4D4CF` / `#202328` **10.60** |
| Headings | `#292D33` / `#fff` 13.84 | `#ECECE8` / `#202328` 13.31 |
| Muted text (TOC, breadcrumbs, edit link) | `#6B6B66` / `#fff` **5.36** | `#9A9A94` / `#202328` **5.57** |
| Links | `#467B96` / `#fff` **4.63** | `#6DA1BB` / `#202328` **5.60** |
| Active sidebar item | `#3c6a81` / `#EDF2F5` 5.21 | `#8FB8CC` / `#263440` 6.01 |
| Link in admonition / banner | `#3c6a81` / `#FFF9E8` 5.59 | `#8FB8CC` / `#2B2820` 6.93 |
| Banner text | `#7A5D2A` / `#FFF9E8` 5.83 | `#DCC48C` / `#2B2820` 8.63 |
| Inline code | `#444` / `#F0F0EC` 8.52 | `#D4D4CF` / `#30353C` 8.30 |
