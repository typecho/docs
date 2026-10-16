---
title: General Settings
sidebar_position: 1
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# General Settings

"Settings → General" (设置 → 基本, `admin/options-general.php`) in the admin panel; only administrators can open it. After saving, the message "Settings have been saved" (设置已经保存) appears.

## Fields at a glance

| UI label | Option name | Default | Description |
|---|---|---|---|
| Site name (站点名称) | `title` | Entered during installation | Shown in the page title and in the admin panel; required |
| Site URL (站点地址) | `siteUrl` | The URL used during installation | The base for generating all permalinks; see [below](#site-url) |
| Site description (站点描述) | `description` | `Your description here.` | Output as `<meta name="description">` in the page head |
| Keywords (关键词) | `keywords` | `typecho,php,blog` | Output as `<meta name="keywords">`, separated by ASCII commas |
| Allow registration (是否允许注册) | `allowRegister` | Not allowed (`0`) | When allowed, visitors can register, and new users become subscribers; see [Users, roles and registration](/guide/content/users#register) |
| XMLRPC interface (XMLRPC 接口) | `allowXmlRpc` | On (`2`) | `0` off, `1` only the Pingback interface off, `2` on; see [Feeds and offline clients](/guide/content/feeds-xmlrpc#xmlrpc) |
| Language (语言) | `lang` | `zh_CN` | Shown only when there is a language pack in `usr/langs`; see [Interface language](../extend/languages.md) |
| Time zone (时区) | `timezone` | Selected during installation | See [below](#timezone) |
| Allowed upload file types (允许上传的文件类型) | `attachmentTypes` | `@image@` | See [below](#attachment-types) |

The site name, site description and keywords cannot contain special characters such as `<` and `>`; otherwise the message "Please do not use special characters in …" (请不要在……中使用特殊字符) appears.

## Site URL {#site-url}

"Site URL" (`siteUrl`) is the prefix Typecho uses when generating every link: posts, pages, categories, feeds and so on. Change it when you move to a new domain or switch to HTTPS; see [Migrating from other blogs: changing the domain](../operations/migration.md#new-domain).

- A trailing `/` is removed when saving.
- Since 1.3 the input is a URL field, and you can enter internationalized domain names such as Chinese ones directly (stored in `xn--` form and converted back when displayed; this requires the PHP `intl` extension).
- When the address you use to access the admin panel differs from this setting, the message "The current address X does not match the value set above" (当前地址 某某 与上述设定值不一致) appears below it.

### Pinning the site URL with `__TYPECHO_SITE_URL__`

Define this constant in `config.inc.php`, and the site URL is taken from the constant:

```php
define('__TYPECHO_SITE_URL__', 'https://example.com');
```

Once it is defined, "General settings" no longer shows the "Site URL" field, and saving other settings does not change the existing `siteUrl` in the database. This suits multi-environment deployments, or cases where you do not want it changed by mistake in the admin panel. Note that this constant only changes the site URL itself; for how links are generated with HTTPS, reverse proxies and so on, see [HTTPS, reverse proxies and CDNs](../operations/https-proxy.md#site-url).

## Time zone {#timezone}

The value of "Time zone" (`timezone`) is the offset from Greenwich Mean Time in **seconds**; for example "北京时间 香港,新加坡,越南 (GMT +8)" is stored as `28800`. It determines the times displayed in the admin panel and on the front end, and which time zone the "Publish date" (发布日期) is interpreted in when you write a post.

- The drop-down only has 25 **whole-hour** time zones from GMT -12 to GMT +12; there are no half-hour zones (for example India's +5:30), and daylight saving time is not handled automatically. The entry "新德里时间 巴基斯坦,马尔代夫 (GMT +5)" in the list actually has an offset of +5 hours, which does not match New Delhi's +5:30.
- Changing the time zone does not change times already saved, only how they are displayed: everything in the database is stored as timestamps.

## Allowed upload file types {#attachment-types}

The four checkboxes correspond to four kinds of values in `attachmentTypes`, and can be combined:

| Option in the UI | Saved value |
|---|---|
| Image files (图片文件) (gif jpg jpeg png tiff bmp webp avif) | `@image@` |
| Multimedia files (多媒体文件) (mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv) | `@media@` |
| Common document files (常用档案文件) (txt doc docx xls xlsx ppt pptx zip rar pdf) | `@doc@` |
| Other formats (其他格式) + text box (`attachmentTypesOther`) | The extensions in the text box, separated by ASCII commas |

Extensions in "Other formats" are converted to lowercase, and executable types such as `php`, `sh` and `exe` are removed automatically. Also, although the description of "Image files" lists `avif`, 1.3.0 does not actually allow it. For the detailed rules, see [Attachments and file management](/guide/content/attachments#types).

## Language {#lang}

The "Language" drop-down appears only when `usr/langs` (or the directory specified by `__TYPECHO_LANG_DIR__`) contains at least one `.mo` language pack. Its options are "简体中文" plus the language name each language pack declares, for example `English` and `日本語`. After switching, the whole admin panel and the text in themes that uses `_t()` change, **for all users**; there is no per-user language.

For installing language packs and the behavior when a language pack is missing, see [Interface language](../extend/languages.md).
