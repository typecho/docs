---
title: General Settings
sidebar_position: 1
translation: machine
source_commit: c2e75722c48df83f37f164719b50ae59d28e802d
---

# General Settings

This page covers "Settings → Basic" (`admin/options-general.php`) in the admin panel, which only administrators can access. After saving, the message "Settings have been saved" appears.

## Fields at a glance

| UI label | Option name | Default | Description |
|---|---|---|---|
| Site name | `title` | Entered during installation | Shown in the page title and in the admin panel; required |
| Site URL | `siteUrl` | The URL used during installation | Prefix for the home page link and for theme and plugin static files and attachment URLs; see [below](#site-url) |
| Site description | `description` | `Your description here.` | Output as `<meta name="description">` in the page head |
| Keywords | `keywords` | `typecho,php,blog` | Output as `<meta name="keywords">`, separated by ASCII commas |
| Allow registration | `allowRegister` | Not allowed (`0`) | When allowed, visitors can register, and new users become subscribers; see [Users, roles and registration](../content/users.md#register) |
| XMLRPC interface | `allowXmlRpc` | On (`2`) | `0` off, `1` only the Pingback interface off, `2` on; see [Feeds and offline clients](../content/feeds-xmlrpc.md#xmlrpc) |
| Language | `lang` | `zh_CN` | Shown only when there is a language pack in `usr/langs`; see [Interface language](../extend/languages.md) |
| Time zone | `timezone` | GMT +8 (`28800`) | The installer offers no time zone choice; change it to your own time zone after installation. See [below](#timezone) |
| Allowed upload file types | `attachmentTypes` | `@image@` | See [below](#attachment-types) |

The site name, site description and keywords cannot contain special characters such as `<` and `>`; otherwise the message "Please do not use special characters in …" appears.

## Site URL {#site-url}

"Site URL" (`siteUrl`) is used for the home page link and for the URLs of theme and plugin static files (CSS, JS, images) and attachments. Permalinks of posts, pages, categories, feeds and so on do **not** use it; they use the root URL of the current request. If permalinks have the wrong domain or scheme, changing this field alone will not fix them (see [HTTPS, reverse proxies and CDNs: domain and scheme of permalinks](../operations/https-proxy.md#root-url)). You also need to change it when you move to a new domain or switch to HTTPS; see [Migrating from other blogs: changing the domain](../operations/migration.md#new-domain).

- A trailing `/` is removed when saving.
- Since 1.3 the input is a URL field, and you can enter internationalized domain names such as Chinese ones directly (stored in `xn--` form and converted back when displayed; this requires the PHP `intl` extension).
- When the address you use to access the admin panel differs from this setting, the message "The current address X does not match the value set above" appears below it.

### Pinning the site URL with `__TYPECHO_SITE_URL__`

Once this constant is defined in `config.inc.php`, the site URL is taken from the constant:

```php
define('__TYPECHO_SITE_URL__', 'https://example.com');
```

Once it is defined, "General settings" no longer shows the "Site URL" field, and saving other settings does not change the existing `siteUrl` in the database. This suits multi-environment deployments, or cases where you do not want it changed by mistake in the admin panel. Note that this constant only changes the site URL itself; for how links are generated with HTTPS, reverse proxies and so on, see [HTTPS, reverse proxies and CDNs](../operations/https-proxy.md#site-url).

## Time zone {#timezone}

The value of "Time zone" (`timezone`) is the offset from Greenwich Mean Time in **seconds**; for example "Chinese Standard Time, Australian Western Standard Time, Irkutsk Standard Time (GMT +8)" is stored as `28800`. It determines the times displayed in the admin panel and on the front end, and which time zone the "Publish date" is interpreted in when you write a post.

- The drop-down only has 25 **whole-hour** time zones from GMT -12 to GMT +12; there are no half-hour zones (for example India's +5:30), and daylight saving time is not handled automatically. In the Simplified Chinese interface, the +5 entry is labeled "新德里时间 巴基斯坦,马尔代夫 (GMT +5)" (New Delhi time), but its offset is +5 hours, which does not match New Delhi's +5:30.
- Changing the time zone does not change times already saved, only how they are displayed: everything in the database is stored as timestamps.

## Allowed upload file types {#attachment-types}

The four checkboxes correspond to four kinds of values in `attachmentTypes`, and can be combined:

| Option in the UI | Saved value |
|---|---|
| Image files (gif jpg jpeg png tiff bmp webp avif) | `@image@` |
| Multimedia files (mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv) | `@media@` |
| Common document files (txt doc docx xls xlsx ppt pptx zip rar pdf) | `@doc@` |
| Other formats + text box (`attachmentTypesOther`) | The extensions in the text box, separated by ASCII commas |

Extensions in "Other formats" are converted to lowercase, and executable types such as `php`, `sh` and `exe` are removed automatically. Also, although the description of "Image files" lists `avif`, 1.3.0 does not actually allow it. For the detailed rules, see [Attachments and file management](../content/attachments.md#types).

## Language {#lang}

The "Language" drop-down appears only when `usr/langs` (or the directory specified by `__TYPECHO_LANG_DIR__`) contains at least one `.mo` language pack. Its options are Simplified Chinese (the built-in language) plus the language name each language pack declares, for example `English`. Switching the language changes the whole admin panel and any theme text output through `_t()`. The setting applies **to all users**; there is no per-user language.

For installing language packs and the behavior when a language pack is missing, see [Interface language](../extend/languages.md).
