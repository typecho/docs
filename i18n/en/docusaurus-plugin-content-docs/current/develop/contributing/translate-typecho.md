---
title: Translating the Typecho Interface
sidebar_position: 3
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Translating the Typecho Interface

Typecho's interface text is written in Simplified Chinese in the source code; other languages are provided by language packs. This page is for translators. It explains the structure of the language pack repository, how to translate and update with Poedit, and how to submit your work. For how site owners install and switch language packs, see [Interface language](../../guide/extend/languages.md).

## The typecho/languages repository

All language packs are in the GitHub repository [typecho/languages](https://github.com/typecho/languages) (`master` branch):

| File | Contents |
|---|---|
| `messages.pot` | The translation template: every string to translate, extracted from the Typecho source code; `msgid` is the Chinese source text and `msgstr` is empty |
| `<language code>.po` | The translations for each language, for example `en_US.po`, `ja_JP.po`, `zh_TW.po` |

There are currently 13 languages: `ceb`, `el_GR`, `en_US`, `es_ES`, `fa_IR`, `fil_PH`, `fr_FR`, `ja_JP`, `pt_BR`, `ru_RU`, `tr_TR`, `ug_CN` and `zh_TW`. The repository does not store `.mo` files (they are in `.gitignore`); CI generates them.

The repository has two workflows:

- **Build mo files** (`ci.yml`): on every push to `master`, compiles each `.po` into a `.mo` of the same name with `msgfmt`, packs them into `langs.zip`, and uploads it to the release tagged `ci`. This is the file site owners download;
- **Auto-update messages.pot** (`update.yml`): checks out the latest code of `typecho/typecho`, regenerates `messages.pot` with its `tools/build_pot.php`, and commits it. It is triggered automatically when Typecho publishes a new release, and can also be run manually.

For the rules by which `messages.pot` is generated, see [Development environment and front-end build](../core/build.md#pot).

## Translating with Poedit

[Poedit](https://poedit.net/) is a free gettext translation editor; you can also use other tools that support `.po`.

### Updating an existing language

1. Fork typecho/languages and clone it locally;
2. Open the `.po` file you want to change in Poedit, for example `ja_JP.po`;
3. Use the menu Translation → Update from POT File, choose `messages.pot` from the repository, and update the translation from the template. After the update:
   - New strings are added as untranslated entries;
   - Strings whose source text changed are marked fuzzy (shown as Needs Work in Poedit), with the old translation kept for reference;
   - Strings that no longer exist in the source code become obsolete entries and no longer take part in translation;
4. Translate the untranslated entries; check the fuzzy entries one by one, fix the translation and remove the fuzzy mark. Fuzzy entries are not compiled into the `.mo`, and the interface still shows the Chinese source text for them;
5. Save.

Without Poedit, you can do the same merge with the gettext command-line tools:

```bash
msgmerge --update ja_JP.po messages.pot   # 用模板更新 .po，原文有改动的条目标记为 fuzzy
msgfmt --check --statistics -o /dev/null ja_JP.po   # 检查格式并统计已翻译、模糊、未翻译的条数
```

### Adding a new language

1. Start from a copy of `en_US.po` and rename it to the code of the new language, for example `de_DE.po`; you can also create a new translation from `messages.pot` in Poedit. Starting from English has the advantage that you can translate with the English translation alongside;
2. Set the file's language in Poedit (Translation → Properties), then update it from `messages.pot` as described above to fill in the strings missing from `en_US.po`;
3. Find the entry `msgid "lang"` and set its translation to the name of the language **written in that language itself**, for example `Deutsch`. This is what the language drop-down in the admin panel's "Settings → General" (设置 → 基本) shows.

```po
msgid "lang"
msgstr "Deutsch"
```

Note: the number and order of placeholders such as `%s` and `%d` must match the source text, and HTML tags must be kept as they are; translate only the text inside them.

### Checking on a site

```bash
msgfmt -o de_DE.mo de_DE.po
```

Put the generated `de_DE.mo` into `usr/langs/` of a Typecho site (create the directory if it does not exist), choose the language in "Settings → General", and go through the admin panel and the default theme page by page. Poedit can also generate the `.mo` automatically when it saves.

## Submitting a pull request

Commit the changed `.po` files (without any `.mo`) to your fork, then open a pull request against the `master` branch of `typecho/languages`, describing the language and what you changed. After the merge, CI regenerates `langs.zip`.

## Known issue: mismatched http and https links {#http-https}

1.3.0 changed `http://` to `https://` in some interface messages that contain links, and changed the wording of some of them as well. gettext matches on the full text of `msgid`, while these entries in the existing `.po` files still have the old source text, so the two do not match and these messages are shown as the Chinese source text in every language:

| Location | Source text in 1.3.0 (excerpt) |
|---|---|
| Footer of every admin page | `由 <a href="https://typecho.org">%s</a> 强力驱动, 版本 %s` |
| Editor → Custom fields | `…使用方法参见 <a href="https://docs.typecho.org/help/custom-fields">帮助文档</a>` |
| Settings → Comments → Comment date format | `具体写法请参考 <a href="https://www.php.net/manual/zh/function.date.php">PHP 日期格式写法</a>.` |
| Settings → Comments | `对评论者个人主页链接使用 <a href="https://en.wikipedia.org/wiki/Nofollow">nofollow 属性</a>` |
| Settings → Comments | `启用 <a href="https://gravatar.com">Gravatar</a> 头像服务, 最高显示评级为 %s 的头像` |
| Settings → Reading → Post date format | `请参考 <a href="https://www.php.net/manual/zh/function.date.php">PHP 日期格式写法</a>.` |
| Edit user → User group | `具体的权限分配表请<a href="https://docs.typecho.org/develop/acl">参考这里</a>.` |
| Edit user, Profile → Homepage URL | `此用户的个人主页地址, 请用 <code>https://</code> 开头.` |
| Profile → Writing settings | Two descriptions containing `<a href="https://daringfireball.net/projects/markdown/">Markdown</a>` |
| Installer | `Typecho 基于 <a href="https://www.gnu.org/copyleft/gpl.html">GPL</a> 协议发布…` |
| Default theme footer | `由 <a href="https://typecho.org">Typecho</a> 强力驱动` |
| `classic-22` theme footer | `Powered by <a href="https://typecho.org">Typecho</a>` |

The fix: update the `.po` file from the current `messages.pot` as described above. These entries appear as fuzzy entries whose translations are still the old versions with `http://` links. Change the links in the translations to `https://` to match the source text (and update the wording where it changed), remove the fuzzy mark, and then submit a pull request.
