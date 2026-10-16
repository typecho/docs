---
title: Interface Language
sidebar_position: 3
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Interface Language

Typecho's interface text is written in Simplified Chinese, and the installation package contains **only Simplified Chinese**. Other languages are provided by language packs: a language pack is a gettext `.mo` file; put it in `usr/langs/` and select it in the admin panel.

## Site owners: installing and switching languages {#install}

1. Download `langs.zip` from the [typecho/languages Release page](https://github.com/typecho/languages/releases/tag/ci). It contains the `.mo` files for all languages (generated automatically by the repository's CI, last updated on 2025-08-07). Currently available: `ceb`, `el_GR`, `en_US`, `es_ES`, `fa_IR`, `fil_PH`, `fr_FR`, `ja_JP`, `pt_BR`, `ru_RU`, `tr_TR`, `ug_CN`, `zh_TW`.
2. Create `usr/langs/` in the site directory (the installation package does not include this directory) and upload the `.mo` files you need into it, for example `usr/langs/en_US.mo`. **Do not rename the files**; the file name is the language code.
3. Go to "Settings → General" (设置 → 基本); a "Language" (语言) drop-down that was not there before appears. Its options are "简体中文" plus each language pack's own name (such as `English`, `日本語`). Select one and save.

For example, after switching to English, the admin menu becomes "Console / Overview / Preferences / Plugins / Appearance…" and the page title becomes "Basic settings"; after switching to 日本語 it becomes "コントロールパネル / 概要 / 個人設定…".

Key points:

- The language setting is stored in the `lang` option and **applies to the whole site**: every user sees the admin panel in the same language, and it cannot be set per user.
- Text in themes and plugins that is output with `_t()` and has a translation in the language pack also changes. For example, after switching to English, "最新文章", "最近回复", "搜索" and so on in the default theme's sidebar become English, while text not included in the language pack stays in Chinese. The front-end language is mainly determined by the theme.
- When installing from the command line, you can set the language directly with the environment variable `TYPECHO_LANG`; see [Command-line and unattended installation](../getting-started/cli-install.md).

### Storing language packs in another directory: `__TYPECHO_LANG_DIR__`

By default they are read from `<site directory>/usr/langs`. To put them somewhere else (for example a separately mounted directory in a container), define an absolute path in `config.inc.php`:

```php
define('__TYPECHO_LANG_DIR__', '/data/typecho-langs');
```

Both the language list in the admin panel and the actual loading read from this directory. For a description of the constant, see [Configuration file](../operations/config-file.md).

### When a language pack is missing

- If the language pack in use is deleted, no error is reported and the interface **automatically falls back to Simplified Chinese**; when you put the file back, the original language returns.
- If `usr/langs/` contains no language pack at all, the "Language" drop-down is not shown. If you then save any change in "General settings" (基本设置), `lang` is cleared, and even after you put the language packs back you have to select the language again.

## Known issue: a few messages are not translated {#known-issues}

1.3.0 changed the links in several admin messages from `http://` to `https://`, but the corresponding source strings in the existing language packs are still `http://`, so the two do not match and these sentences still appear in Chinese in other languages:

- Settings → Discussion → Comment date format: "具体写法请参考 PHP 日期格式写法."
- Settings → Reading → Post date format: "请参考 PHP 日期格式写法."
- Edit user → User group: "具体的权限分配表请参考这里."
- Editor → Custom fields: "自定义字段可以扩展你的模板功能, 使用方法参见 帮助文档"

This needs the translations to be updated in the language pack repository; site owners cannot fix it through settings.

## Translators: contributing translations {#translate}

Site owners only need the `.mo` files; the `.po` files are the source files that translators edit. The translation workflow (see the instructions in the typecho/languages repository):

1. Fork [typecho/languages](https://github.com/typecho/languages) and clone it locally;
2. Edit the `.po` file of your language with a gettext tool such as [Poedit](https://poedit.net/). `messages.pot` contains all strings to be translated, extracted from the Typecho source code, with Chinese as the source language; when creating a new language you can start from `en_US.po` and then fill in the missing entries from `messages.pot`;
3. Set the translation of the `msgid "lang"` entry to the language's own name (for example `English`, `日本語`); this is the name shown in the admin drop-down;
4. To check locally, generate a `.mo` with `msgfmt <语言代码>.po -o <语言代码>.mo` and put it in your site's `usr/langs/` to see the result;
5. Submit a Pull Request. After it is merged, the repository's CI regenerates `langs.zip`.

According to `msgfmt --statistics`, `en_US.po` has 894 translated entries and `ja_JP.po` has 860, out of about 900 strings in `messages.pot`; untranslated entries are shown in the original Chinese in the interface.
