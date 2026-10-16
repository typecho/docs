---
title: Option Names
sidebar_position: 6
translation: machine
source_commit: c4fe96854858d8912390c7dfe6c2eab21c87ac5c
---

# Option Names

Typecho stores its settings in the database `options` table, one option per row: `name` (the option name), `user` (`0` for a site-wide setting, any other value for that user's personal setting) and `value` (the value, always stored as a string). In templates and plugins, read them with `$this->options->optionName`; see [$this->options](./options.md).

## Type notation

The "Type" column in the tables describes what the value means; in the database every value is a string.

| Notation | Meaning |
|---|---|
| `0/1` | A switch; `1` means on. You read it as the string `'0'` / `'1'`, which you can use directly in an `if` test |
| `int` | An integer (as a string) |
| `string` | Plain text |
| `JSON` | A JSON-encoded array. `plugins`, `routingTable`, `actionTable` and `panelTable` are already decoded into arrays when you read them through `$this->options`; the values in `theme:…` are merged in as ordinary options |

## All default options

| Option name | Type | Settings page | Default | Description |
|---|---|---|---|---|
| `theme` | string | [Appearance](../../guide/extend/themes.md) | `default` | Directory name of the current theme |
| `theme:default` | JSON | Appearance → Theme settings | `{"logoUrl":"","sidebarBlock":[…5 项]}` | Settings of the `default` theme; for other themes it is `theme:<directory name>`, created on activation and deleted on switching away |
| `timezone` | int | [General](../../guide/settings/general.md) | `28800` | Time zone, as the offset from GMT in seconds |
| `lang` | string | [General](../../guide/settings/general.md) | `zh_CN` (a command-line install can set it with `TYPECHO_LANG`) | Interface language, `zh_CN` or a language pack file name |
| `charset` | string | none | `UTF-8` | Page encoding |
| `contentType` | string | none | `text/html` | Page content type |
| `gzip` | 0/1 | none | `0` | No code reads this option |
| `generator` | string | none | `Typecho 1.3.0` | Version identifier, updated on upgrade |
| `title` | string | [General](../../guide/settings/general.md) | `Hello World` | Site name |
| `description` | string | [General](../../guide/settings/general.md) | `Your description here.` | Site description |
| `keywords` | string | [General](../../guide/settings/general.md) | `typecho,php,blog` | Keywords |
| `rewrite` | 0/1 | [Permalinks](../../guide/settings/permalink.md) | `0` | Whether to use URL rewriting |
| `frontPage` | string | [Reading](../../guide/settings/reading.md) | `recent` | Site front page: `recent`, `page:<page ID>`, `file:<file name>` |
| `frontArchive` | 0/1 | [Reading](../../guide/settings/reading.md) | `0` | Also change the path of the post list page |
| `commentsRequireMail` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Email is required |
| `commentsWhitelist` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | The commenter must already have an approved comment |
| `commentsRequireUrl` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | Website is required |
| `commentsRequireModeration` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | All comments must be moderated |
| `plugins` | JSON / serialize | [Plugins](../../guide/extend/plugins.md) | `a:0:{}` | Enabled plugins and the hooks they registered; the initial value at install time is the PHP-serialized empty array `a:0:{}`, and it is saved as JSON afterwards |
| `commentDateFormat` | string | [Discussion](../../guide/settings/discussion.md) | `F jS, Y \a\t h:i a` | Comment date format |
| `siteUrl` | string | [General](../../guide/settings/general.md) | The URL at install time | Site URL, entered during installation; no trailing `/` |
| `defaultCategory` | int | Manage → Categories | `1` | ID of the default category |
| `allowRegister` | 0/1 | [General](../../guide/settings/general.md) | `0` | Whether registration is allowed |
| `defaultAllowComment` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `1` | New posts allow comments by default |
| `defaultAllowPing` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `1` | New posts allow pings by default |
| `defaultAllowFeed` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `1` | New posts appear in feeds by default |
| `pageSize` | int | [Reading](../../guide/settings/reading.md) | `5` | Number of posts per page |
| `postsListSize` | int | [Reading](../../guide/settings/reading.md) | `10` | Post list size |
| `commentsListSize` | int | [Discussion](../../guide/settings/discussion.md) | `10` | Comment list size |
| `commentsHTMLTagAllowed` | string | [Discussion](../../guide/settings/discussion.md) | empty | Allowed HTML tags and attributes |
| `postDateFormat` | string | [Reading](../../guide/settings/reading.md) | `Y-m-d` | Post date format |
| `feedFullText` | 0/1 | [Reading](../../guide/settings/reading.md) | `1` | Output full text in feeds |
| `editorSize` | int | Editor | `350` | Editor height (pixels), saved after you resize the editor by dragging its bottom edge |
| `autoSave` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `0` | Autosave |
| `markdown` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `1` | Edit with Markdown |
| `xmlrpcMarkdown` | 0/1 | [Profile](../../guide/content/profile.md) (per user) | `0` | Use Markdown in the XML-RPC interface |
| `commentsMaxNestingLevels` | int | [Discussion](../../guide/settings/discussion.md) | `5` | Maximum reply depth, 2 to 7 |
| `commentsPostTimeout` | int | [Discussion](../../guide/settings/discussion.md) | `2592000` | Auto-close period (seconds; entered in days in the UI) |
| `commentsUrlNofollow` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Use nofollow on commenters' homepage links |
| `commentsShowUrl` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Automatically link the commenter's name to their homepage |
| `commentsMarkdown` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | Use Markdown syntax in comments |
| `commentsPageBreak` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | Enable comment pagination |
| `commentsThreaded` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Enable comment replies |
| `commentsPageSize` | int | [Discussion](../../guide/settings/discussion.md) | `20` | Comments per page |
| `commentsPageDisplay` | string | [Discussion](../../guide/settings/discussion.md) | `last` | Show the `first` / `last` page by default |
| `commentsOrder` | string | [Discussion](../../guide/settings/discussion.md) | `ASC` | `ASC` oldest first / `DESC` newest first |
| `commentsCheckReferer` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Check the comment's referring page |
| `commentsAutoClose` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | Automatically close comments |
| `commentsPostIntervalEnable` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Limit the comment interval for the same IP |
| `commentsPostInterval` | int | [Discussion](../../guide/settings/discussion.md) | `60` | Comment interval (seconds; entered in minutes in the UI) |
| `commentsShowCommentOnly` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `0` | Show comments only, not pingbacks and trackbacks |
| `commentsAvatar` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Enable Gravatar avatars |
| `commentsAvatarRating` | string | [Discussion](../../guide/settings/discussion.md) | `G` | Highest avatar rating `G` / `PG` / `R` / `X` |
| `commentsAntiSpam` | 0/1 | [Discussion](../../guide/settings/discussion.md) | `1` | Enable anti-spam protection |
| `routingTable` | JSON | [Permalinks](../../guide/settings/permalink.md), [Reading](../../guide/settings/reading.md) | Default routes (JSON) | Routing table; the permalink settings and the "post list page path" change rules in it; 25 rules at install time |
| `actionTable` | JSON | none (registered by plugins) | `[]` | Actions registered by plugins via `Helper::addAction()` |
| `panelTable` | JSON | none (registered by plugins) | `[]` | Admin panels registered by plugins via `Helper::addPanel()` |
| `attachmentTypes` | string | [General](../../guide/settings/general.md) | `@image@` | File types allowed for upload, `@image@` and so on joined with commas |
| `secret` | string | none | random | A 32-character key generated randomly at install time, used for security tokens; do not disclose it |
| `installed` | 0/1 | none | `1` | Installation-complete flag, `1` after installation |
| `allowXmlRpc` | 0/1/2 | [General](../../guide/settings/general.md) | `2` | XML-RPC interface: off / pingback only off / on |

## Personal settings

For the six options whose settings page is Profile (`markdown`, `xmlrpcMarkdown`, `autoSave`, `defaultAllowComment`, `defaultAllowPing`, `defaultAllowFeed`), the table shows the site-wide default with `user = 0`. When a user saves their profile settings, an additional row with `user = <user ID>` is written, which overrides the site-wide default when that user is logged in.

## Options that appear only after installation

The following names are not among the default options; they are created by actions of the site owner or of plugins:

| Option name | When it is created |
|---|---|
| `theme:<theme directory name>` | Created from the defaults of the theme's `themeConfig()` when you activate the theme; updated when you save the theme settings; deleted when you switch to another theme |
| `plugin:<plugin name>` | Created when you enable a plugin that has settings, updated when you save the plugin settings, deleted when you disable the plugin |
| `_plugin:<plugin name>` | The plugin's personal settings: when you enable the plugin, the defaults are saved with `user = 0`; when a user saves them in their profile, a row with `user = <user ID>` is written; all of them are deleted when you disable the plugin |
| Names defined by plugins | Plugins can write any option with `Helper::setOption()` |

The form under "Settings → Discussion" also submits three names, `commentsUniqueIpInterval`, `commentsStopWords` and `commentsIpBlackList`, but they are not default options and no code reads them.
