# scripts/

Maintainer tools for this documentation site. Nothing here is published on the site.

| Script | What it does | Command |
|---|---|---|
| `api-dump.php` | Prints the public API of one or more Typecho classes as Markdown tables: public methods (signature, declaring class, first doc-comment line, `@deprecated`) and `___xxx` magic properties. `--own` keeps only members declared in the class itself; `--magic-only` / `--methods-only` restrict the output. | `php scripts/api-dump.php <typecho-root> 'Widget\Archive' --own` |
| `hook-dump.php` | Tokenizes `index.php`, `admin/*.php` and `var/**.php` of a Typecho checkout, finds every `X::pluginHandle()` / `Typecho\Plugin::factory(...)` call and follows the chain to `call()` / `filter()` to list every (handle, hook) pair, plus dynamic hook families. `--summary` prints only the counts. | `php scripts/hook-dump.php <typecho-root> > /tmp/hooks.md` |
| `i18n-status.mjs` | Lists every page under `docs/` with its English status (`missing` / `machine` / `reviewed` / `stale`) and the reviewed coverage. See OUTLINE.md §5.5. | `node scripts/i18n-status.mjs` (or `pnpm i18n:status`) |
| `translation-prompt.md` | Prompt template for translating one Chinese page into English: front matter rules, content rules and the full glossary. Fill in `{{SOURCE_PATH}}`, `{{SOURCE_COMMIT}}` (`git log -1 --format=%H -- docs/<path>`) and `{{SOURCE_CONTENT}}`. | — (instructions at the top of the file) |

`<typecho-root>` is a Typecho checkout at the documented version (the reference pages were built from `v1.3.0`; identifiers were checked against `v1.3.0-10-g56f4c6f`). Both PHP scripts only read the source.

## Regenerating the reference pages (docs/develop/reference/)

Script output gives the raw tables; the grouping, the 说明 column where the doc comment is empty, and all prose are written by hand. When regenerating, diff the script output against the page and carry over only real API changes.

| Page | Source of the tables | Generated vs. hand-written |
|---|---|---|
| `archive.md` | `php scripts/api-dump.php <root> 'Widget\Archive' --own` over `var/Widget/Archive.php`: public methods declared in the class (signature, first doc-comment line, `@deprecated`) and `___` magic properties. | Tables generated; empty descriptions filled in by hand. |
| `class-aliases.md` | Mapping rules from the `spl_autoload_register()` callback in `var/Typecho/Common.php`; explicit alias table from `__TYPECHO_CLASS_ALIASES__` in `var/Widget/Init.php`. The “旧类名一览” list: all 122 class files under `var/Widget` and `var/Typecho` of Typecho 1.1 (`v1.1-17.10.30-release`) converted to underscore names and resolved one by one with the 1.3.0 autoloader. | Alias table read from source; old-name list from the autoloader resolution run (no script in this directory); prose by hand. |
| `database-schema.md` | Columns and types from `install/Mysql.sql`, `install/Pgsql.sql`, `install/SQLite.sql`. Value ranges from the widgets that write the tables: `var/Widget/Contents/EditTrait.php`, `var/Widget/Contents/Post/Edit.php`, `var/Widget/Upload.php`, `var/Widget/Feedback.php`, `var/Widget/Comments/Edit.php`, `var/Widget/Metas/*/Edit.php`, `var/Widget/User.php`. | Read from source by hand (no script). |
| `form-elements.md` | Element list from `var/Typecho/Widget/Helper/Form/Element/`. Method tables: `php scripts/api-dump.php <root> 'Typecho\Widget\Helper\Form\Element' --own` and `php scripts/api-dump.php <root> 'Typecho\Validate' --own`. HTML output of each element obtained by constructing it and calling `render()`. | Method tables generated; HTML samples from running `render()`; prose by hand. |
| `helper.md` | `php scripts/api-dump.php <root> 'Utils\Helper' --own` over `var/Utils/Helper.php` (20 public static methods). | Signatures generated; grouping and descriptions by hand. |
| `hooks.md` | `php scripts/hook-dump.php <root>` over `index.php`, `admin/*.php`, `var/`. At 1.3.0: 124 call sites, 124 (handle, hook) pairs (21 file handles, 103 widget-class hooks) plus 3 dynamic hook families. Check counts with `--summary`. | Tables generated; the 说明 texts by hand. |
| `list-widgets.md` | `php scripts/api-dump.php <root> '<class>' --own` per class (own members only). Parameters and defaults from `setDefault()` in each class's `execute()` / `initParameter()`; `listCategories()` / `listPages()` options from their `setDefault()` and `var/Widget/Base/TreeViewTrait.php`. | Method/magic tables generated; parameter tables and prose by hand. |
| `option-names.md` | `install_get_default_options()` in `install.php` (61 options written to `options` at install, with `installed` set to `1`). Owning settings screen from the code that saves each option in `var/Widget/Options/*.php` and `var/Widget/Users/Profile.php`. | Read from source by hand. |
| `options.md` | `php scripts/api-dump.php <root> 'Widget\Options' --own` over `var/Widget/Options.php`. | Tables generated; empty descriptions filled in by hand. |
| `routes-actions.md` | Routes from `install_get_default_routers()` in `install.php` (written to option `routingTable`); placeholders from `var/Typecho/Router/Parser.php`; actions from `$map` in `var/Widget/Action.php`; operations per action from each class's `action()`. | Read from source by hand. |
| `row-widgets.md` | `php scripts/api-dump.php <root> '<class>' --own` for `Typecho\Widget`, `Widget\Base\Contents`, `Widget\Base\Comments`, `Widget\Base\Metas`, `Widget\Base\Users`, `Widget\Comments\Archive` (`var/Typecho/Widget.php`, `var/Widget/Base/{Contents,Comments,Metas,Users}.php`, `var/Widget/Comments/Archive.php`). Field tables from the `CREATE TABLE` statements in `install/SQLite.sql` plus the fields each class's `filter()` adds. | Method/magic tables generated; field tables and empty descriptions by hand. |
