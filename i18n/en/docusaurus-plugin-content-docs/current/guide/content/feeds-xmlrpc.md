---
title: Feeds and offline clients
sidebar_position: 11
translation: machine
source_commit: c9097eed4a7142f9eabc1bca041ef819d6a64bbd
---

# Feeds and offline clients

Typecho ships with three kinds of feeds, RSS 2.0, RSS 1.0 and Atom, plus an XML-RPC interface that offline writing clients (MetaWeblog and other protocols) use to publish and manage posts, and that is also used to receive pingbacks.

## Feed URLs {#feeds}

The table below shows the URLs with URL rewriting enabled; without it, add `/index.php` after the site URL, for example `https://example.com/index.php/feed/`.

| Content | RSS 2.0 | RSS 1.0 | Atom 1.0 |
|---|---|---|---|
| Latest posts on the site | `/feed/` | `/feed/rss/` | `/feed/atom/` |
| Latest comments on the site | `/feed/comments/` | `/feed/rss/comments/` | `/feed/atom/comments/` |
| A category | `/feed/category/<category slug>/` | `/feed/rss/category/<category slug>/` | `/feed/atom/category/<category slug>/` |
| A tag | `/feed/tag/<tag slug>/` | `/feed/rss/tag/<tag slug>/` | `/feed/atom/tag/<tag slug>/` |
| Comments on a post | `/feed/archives/<post ID>/` | `/feed/rss/archives/<post ID>/` | `/feed/atom/archives/<post ID>/` |

The pattern: put `/feed` (RSS 2.0), `/feed/rss` (RSS 1.0) or `/feed/atom` (Atom) in front of any page URL to get that page's feed; author pages and date archives work the same way. The three formats return `application/rss+xml`, `application/rdf+xml` and `application/atom+xml` respectively.

The theme's `<head>` outputs three `<link rel="alternate">` tags for the current page, so browsers and feed readers can discover them automatically.

### Which posts go into the feed

Each feed outputs the latest 10 entries, and includes only posts that meet all of the following:

- public, with the publish date reached (scheduled posts enter only once their time comes);
- "Allow in feeds" (`allowFeed`) is checked in the editor;
- no password is set.

Hidden, private and pending-review posts and drafts never appear. The one exception is the author: when logged in, opening the feed URL in the same browser shows their own private posts (they still need to allow feeds and have no password); feed readers and other visitors get the logged-out result. See [Visibility and password protection](./visibility.md) for the rules.

### Comment feed {#comment-feed}

The conditions above apply only to post feeds. The site-wide comment feed (`/feed/comments/`) outputs the 10 latest approved comments and does not check the status or the "Allow in feeds" setting of the post each comment belongs to: after a post is made private or hidden, or removed from feeds, its approved comments still appear here (only comments on password-protected posts show their content as "The content is hidden."). If you don't want those comments to be picked up by subscribers, mark them as pending or delete them.

### Full text or excerpt: `feedFullText`

"Full text in feeds" (`feedFullText`) in **Settings → Reading**:

| UI option | Value | Effect |
|---|---|---|
| Full text | `1` (default) | The feed contains the full text of posts |
| Excerpt only | `0` | When a post has the excerpt separator `<!--more-->`, only the part before the separator is output, followed by a `[...]` link to the original post; **posts without a separator are still output in full** |

So after choosing "Excerpt only", you also need to insert the excerpt separator in your posts (the editor toolbar button or <kbd>Ctrl</kbd>+<kbd>M</kbd>), see [Writing posts](./posts.md#markdown).

## XML-RPC interface {#xmlrpc}

"XMLRPC interface" (`allowXmlRpc`) in **Settings → General** has three values, and defaults to "On":

| UI option | Value | Offline clients | Receive pingbacks | Page output |
|---|---|---|---|---|
| Off | `0` | Unavailable; the interface URL returns 404 | No | No RSD, wlwmanifest or pingback links |
| Disable only the Pingback interface | `1` | Available | No; calling `pingback.ping` returns "requested method pingback.ping does not exist" | RSD and wlwmanifest links are output; no pingback link and no `X-Pingback` response header |
| On | `2` | Available | Yes | Everything is output; post pages carry the `X-Pingback` response header |

For sites that use no offline client and do not want to receive pingbacks, we recommend "Off", to reduce the entry points for scanning and abuse. Trackbacks do not go through XML-RPC and are not affected by this option; they are controlled by each post's "Allow trackbacks", see [Managing and moderating comments](./comments.md).

### Connecting an offline client

When adding a blog in the client, you usually need to fill in:

| Item | Value |
|---|---|
| Blog type / API | MetaWeblog API (you can also choose WordPress or Movable Type; Typecho implements `metaWeblog.*`, `blogger.*`, `mt.*` and most `wp.*` methods) |
| Interface URL (API URL / Endpoint) | `https://<your site>/index.php/action/xmlrpc`; with URL rewriting enabled you can also use `https://<your site>/action/xmlrpc` |
| Username, password | Your admin username and password |
| Blog ID | `1` is fine |

- The account must be at least a **contributor**; posts published by contributors through a client also go to pending review. Methods that manage pages, categories and the author list require an editor; `wp.getOptions` / `wp.setOptions` require an administrator.
- Logging in via XML-RPC does not invalidate your admin login in the browser.

:::caution In 1.3.0 auto-discovery does not work; enter the interface URL manually
The `<link rel="EditURI" href=".../action/xmlrpc?rsd">` and wlwmanifest links output in the page head do not actually work in 1.3.0: requests to `?rsd` and `?wlw` return an XML-RPC parse error, while only `?rsd=1` and `?wlw=1` return the correct RSD and manifest (because a parameter with an empty value is treated as absent). Clients that rely on "detect from the URL" may fail to recognize the blog because of this; enter the interface URL from the table above directly.
:::

:::caution In 1.3.0, editing an existing post from a client creates a new post
The 1.3.0 XML-RPC edit interface has a known issue: when you modify an existing post with `metaWeblog.editPost`, the original is not changed; instead **a new post is created**, and the original stays as it was. `wp.editPost` and `wp.editPage` use the same code and are affected the same way. The issue is fixed in the code after 1.3.0; the 1.3.0 release is still affected. With 1.3.0, edit published posts in the web admin panel. If a duplicate has already been created, first copy its changes into the original in the admin panel, check that the original is correct, and only then delete the duplicate.
:::

### Writing Markdown in a client {#markdown}

By default, content submitted by a client is saved as HTML, and a client reading a post also gets the converted HTML. If your client itself writes in Markdown, turn on **both** "Use Markdown syntax to edit and parse content" (`markdown`) and "Use Markdown syntax in the XMLRPC interface" (`xmlrpcMarkdown`) in your [Profile](./profile.md#writing). With only the latter on, the content gets no Markdown marker and the front end shows the Markdown source as plain text. With both on, content submitted with your account is saved as Markdown (with the `<!--markdown-->` marker in the content), and the client gets the Markdown source when reading. This option is set per user.

For new posts, "Allow comments / trackbacks" uses the value the client sends, and falls back to "Allow by default" in your Profile when the client sends none; "Allow in feeds" always comes from your Profile.

### PHP 8.4 and later

The XML-RPC library Typecho uses produces "Deprecated" notices on PHP 8.4 and 8.5. With `display_errors` on, these notices are printed in front of the XML response, and clients report an error because the response is not valid XML. In production, keep `display_errors = Off` (PHP's default production configuration already does this), so the notices are written only to the log.
