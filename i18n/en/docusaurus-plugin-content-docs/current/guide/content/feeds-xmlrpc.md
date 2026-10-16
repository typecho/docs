---
title: Feeds and offline clients
sidebar_position: 11
translation: machine
source_commit: 0dccf9f7706701fc8b129b2d5f8efd9c36c511de
---

# Feeds and offline clients

Typecho ships with three kinds of feeds, RSS 2.0, RSS 1.0 and Atom, plus an XML-RPC interface that offline writing clients (MetaWeblog and other protocols) use to publish and manage posts, and that is also used to receive pingbacks.

## Feed URLs {#feeds}

The table below shows the URLs with URL rewriting (伪静态) enabled; without it, add `/index.php` after the site URL, for example `https://example.com/index.php/feed/`.

| Content | RSS 2.0 | RSS 1.0 | Atom 1.0 |
|---|---|---|---|
| Latest posts on the site | `/feed/` | `/feed/rss/` | `/feed/atom/` |
| Latest comments on the site | `/feed/comments/` | `/feed/rss/comments/` | `/feed/atom/comments/` |
| A category | `/feed/category/分类缩略名/` | `/feed/rss/category/分类缩略名/` | `/feed/atom/category/分类缩略名/` |
| A tag | `/feed/tag/标签缩略名/` | `/feed/rss/tag/标签缩略名/` | `/feed/atom/tag/标签缩略名/` |
| Comments on a post | `/feed/archives/文章ID/` | `/feed/rss/archives/文章ID/` | `/feed/atom/archives/文章ID/` |

The pattern: put `/feed` (RSS 2.0), `/feed/rss` (RSS 1.0) or `/feed/atom` (Atom) in front of any page URL to get that page's feed; author pages and date archives work the same way. The three formats return `application/rss+xml`, `application/rdf+xml` and `application/atom+xml` respectively.

The theme's `<head>` outputs three `<link rel="alternate">` tags for the current page, so browsers and feed readers can discover them automatically.

### Which posts go into the feed

Each feed outputs the latest 10 entries, and includes only posts that meet all of the following:

- public, with the publish date reached (scheduled posts enter only once their time comes);
- "Allow in feeds" (允许在聚合中出现, `allowFeed`) is checked in the editor;
- no password is set.

Hidden, private and pending-review posts and drafts never appear. See [Visibility and password protection](./visibility.md) for the rules.

### Full text or excerpt: `feedFullText`

"Full text in feeds" (聚合全文输出, `feedFullText`) in **Settings → Reading** (设置 → 阅读):

| UI option | Value | Effect |
|---|---|---|
| Full text (全文输出) | `1` (default) | The feed contains the full text of posts |
| Excerpt only (仅输出摘要) | `0` | When a post has the excerpt separator `<!--more-->`, only the part before the separator is output, followed by a `[...]` link to the original post; **posts without a separator are still output in full** |

So after choosing "Excerpt only", you also need to insert the excerpt separator in your posts (the editor toolbar button or <kbd>Ctrl</kbd>+<kbd>M</kbd>), see [Writing posts](./posts.md#markdown).

## XML-RPC interface {#xmlrpc}

"XMLRPC interface" (XMLRPC 接口, `allowXmlRpc`) in **Settings → General** (设置 → 基本) has three values, and defaults to "On":

| UI option | Value | Offline clients | Receive pingbacks | Page output |
|---|---|---|---|---|
| Off (关闭) | `0` | Unavailable; the interface URL returns 404 | No | No RSD, wlwmanifest or pingback links |
| Disable only the Pingback interface (仅关闭 Pingback 接口) | `1` | Available | No; calling `pingback.ping` returns "requested method pingback.ping does not exist" | RSD and wlwmanifest links are output; no pingback link and no `X-Pingback` response header |
| On (打开) | `2` | Available | Yes | Everything is output; post pages carry the `X-Pingback` response header |

For sites that use no offline client and do not want to receive pingbacks, we recommend "Off", to reduce the entry points for scanning and abuse. Trackbacks do not go through XML-RPC and are not affected by this option; they are controlled by each post's "Allow trackbacks" (允许被引用), see [Managing and moderating comments](./comments.md).

### Connecting an offline client

When adding a blog in the client, you usually need to fill in:

| Item | Value |
|---|---|
| Blog type / API | MetaWeblog API (you can also choose WordPress or Movable Type; Typecho implements `metaWeblog.*`, `blogger.*`, `mt.*` and most `wp.*` methods) |
| Interface URL (API URL / Endpoint) | `https://你的站点/index.php/action/xmlrpc`; with URL rewriting enabled you can also use `https://你的站点/action/xmlrpc` |
| Username, password | Your admin username and password |
| Blog ID | `1` is fine |

- The account must be at least a **contributor**; posts published by contributors through a client also go to pending review. Methods that manage pages, categories and the author list require an editor; `wp.getOptions` / `wp.setOptions` require an administrator.
- Logging in via XML-RPC does not invalidate your admin login in the browser.
- Images uploaded by the client are also subject to "Allowed file types for upload", see [Attachments and file management](./attachments.md#types).

:::caution In 1.3.0 auto-discovery does not work; enter the interface URL manually
The `<link rel="EditURI" href=".../action/xmlrpc?rsd">` and wlwmanifest links output in the page head do not actually work in 1.3.0: requests to `?rsd` and `?wlw` return an XML-RPC parse error, while only `?rsd=1` and `?wlw=1` return the correct RSD and manifest (because a parameter with an empty value is treated as absent). Clients that rely on "detect from the URL" may fail to recognize the blog because of this; enter the interface URL from the table above directly.
:::

:::caution In 1.3.0, editing an existing post from a client creates a new post
The 1.3.0 XML-RPC edit interface has a known issue: when you modify an existing post with `metaWeblog.editPost`, the original is not changed; instead **a new post is created**, and the original stays as it was. `wp.editPost` and `wp.editPage` use the same code and are affected the same way. The issue is fixed in the code after 1.3.0; the 1.3.0 release is still affected. With 1.3.0, edit published posts in the web admin panel, or after editing in the client, delete the duplicate post in the admin panel.
:::

### Writing Markdown in a client {#markdown}

By default, content submitted by a client is saved as HTML, and a client reading a post also gets the converted HTML. If your client itself writes in Markdown, turn on "Use Markdown syntax in the XMLRPC interface" (在 XMLRPC 接口中使用 Markdown 语法, `xmlrpcMarkdown`) in your [Profile](./profile.md#writing): from then on, content submitted with your account is saved as Markdown (with the `<!--markdown-->` marker in the content), and the client gets the Markdown source when reading. This option is set per user.

For new posts, "Allow comments / trackbacks / in feeds" follows "Allow by default" in your Profile.

### PHP 8.4 and later

The XML-RPC library Typecho uses produces "Deprecated" notices on PHP 8.4 and 8.5. With `display_errors` on, these notices are printed in front of the XML response, and clients report an error because the response is not valid XML. In production, keep `display_errors = Off` (PHP's default production configuration already does this), so the notices are written only to the log.
