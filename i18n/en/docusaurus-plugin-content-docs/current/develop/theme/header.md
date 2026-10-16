---
title: Header Output with header()
sidebar_position: 12
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# Header Output with header()

`$this->header()` goes inside the template's `<head>` and outputs, in one call, the metadata and scripts the page needs:

```php
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <title><?php $this->archiveTitle('', '', ' - '); ?><?php $this->options->title(); ?></title>
    <?php $this->header(); ?>
</head>
```

## What it outputs

| Key | Output | Condition |
|---|---|---|
| — | `<link rel="canonical">` | Posts and pages (cannot be suppressed) |
| `description` | `<meta name="description">` | When there is an archive description |
| `keywords` | `<meta name="keywords">` | When there are keywords |
| `generator` | `<meta name="generator" content="Typecho 1.3.0">` | |
| `template` | `<meta name="template" content="主题目录名">` (the theme directory name) | |
| `pingback` | `<link rel="pingback">` | "XMLRPC interface" is set to "On" |
| `xmlrpc` | `<link rel="EditURI">` (RSD) | "XMLRPC interface" is not set to "Off" |
| `wlw` | `<link rel="wlwmanifest">` | As above |
| `rss2` / `rss1` / `atom` | Three `<link rel="alternate">` feeds | On post pages, the post must also "allow appearing in feeds" |
| `social` | `og:type`, `og:url`, `og:title`, `og:description`, `og:site_name` and `twitter:*` tags | |
| `commentReply` | The comment reply script (`window.TypechoComment`) | Posts and pages, with "Enable comment replies" turned on |
| `antiSpam` | The anti-spam script, which adds a verification field to the comment form | Posts and pages, with "Enable anti-spam protection" turned on |

`commentReply` and `antiSpam` are required for comments to work: with `commentReply` suppressed, the "Reply" button cannot move the form below the comment; with `antiSpam` suppressed, comment submissions fail on sites that have anti-spam protection turned on.

## Suppressing or replacing items

Pass `header()` a query string that sets the keys you do not need to empty:

```php
<?php $this->header('generator=&template=&pingback=&xmlrpc=&wlw='); ?>
```

The line above removes the version number, the theme name, and the three Pingback, RSD and wlwmanifest links; everything else is output as usual. Some common uses:

| Syntax | Effect |
|---|---|
| `generator=&template=` | Do not reveal the Typecho version and theme name |
| `rss1=&atom=` | Keep only the RSS 2.0 feed |
| `social=` | Do not output Open Graph / Twitter tags (for example when the theme or a plugin outputs these tags itself) |
| `description=&keywords=` | Let the theme output the description and keywords itself |
| `commentReply=/usr/themes/你的主题/js/reply.js` | Do not output the inline reply script; load the script file at the given URL instead |

When the value of `commentReply` or `antiSpam` is `1` (the default), the built-in script is output; when it is empty, nothing is output; any other value is treated as a script URL and output as `<script src="...">`. When you replace it with your own script, you have to implement the original script's functionality yourself.

## The headerOptions filter

Before output, `header()` passes these keys and values to the `headerOptions` filter of `Widget\Archive`, which plugins can use to change them across the board. A theme can also register it in `functions.php`:

```php
\Typecho\Plugin::factory('Widget\Archive')->headerOptions = 'starterHeaderOptions';

function starterHeaderOptions($allows, $archive)
{
    $allows['generator'] = '';
    $allows['template'] = '';

    if ($archive->is('single')) {
        $allows['rss1'] = '';
        $allows['atom'] = '';
    }

    return $allows;
}
```

- The callback receives two arguments: the key-value array `$allows` and the current `Widget\Archive`; it must return the modified array.
- The filter runs after the arguments of `header()` have been merged, so it can override the settings passed in the template.
- A registration statement at the top level of `functions.php` runs every time `functions.php` is loaded for a front-end request; it only applies to the current request and does not need to be activated the way a plugin does.

## After header()

Through the `header` hook of `Widget\Archive`, plugins can append content after what `header()` outputs, such as analytics code or extra styles. Correspondingly, `$this->footer()` should be placed before `</body>` so that plugins can output content at the bottom of the page.
