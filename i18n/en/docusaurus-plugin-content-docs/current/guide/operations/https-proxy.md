---
title: HTTPS, Reverse Proxies and CDNs
sidebar_position: 2
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# HTTPS, Reverse Proxies and CDNs

When HTTPS is enabled directly on the web server, you usually need no extra setup: change the site URL in "Settings → General" in the admin panel so that it starts with `https://`.

When the site sits **behind a reverse proxy, load balancer or CDN**, PHP sees the request sent by the proxy: the scheme may be `http`, `Host` may be an internal address, and the client IP is the proxy's IP. This page explains how Typecho determines this information, and the four groups of constants that go with it. All constants go in `config.inc.php` in the site root; for the full list see [config.inc.php and constants](./config-file.md).

## How Typecho builds links

Once this is clear, the problems further down are easy to understand. Typecho has two "addresses":

| Address | Source | Used for |
|---|---|---|
| **Root URL** `rootUrl` | Computed on every request from the **current request**: scheme (see the next section) + `Host` request header + installation directory | Permalinks of posts, pages, categories and so on, feeds, admin URLs, login and form submission URLs |
| **Site URL** `siteUrl` | The value saved in "Settings → General" in the admin panel (option `siteUrl`) | Static files of themes and plugins (CSS, JS, images), attachment URLs, the home page link |

In other words, **permalinks follow the request, static files follow the setting**. When the proxy does not pass the real `Host` and scheme to PHP, permalinks become internal addresses or `http://`; when the site URL setting is wrong, styles and images break.

In addition, when the current request is judged to be HTTPS, Typecho automatically replaces a site URL starting with `http://` with `https://` (`___siteUrl()` in `var/Widget/Options.php`).

## HTTPS detection and `__TYPECHO_SECURE__` {#secure}

`isSecure()` in `var/Typecho/Request.php` treats the request as HTTPS when any of the following holds:

| Condition | Usually set by |
|---|---|
| Request header `X-Forwarded-Proto` is `https` or `quic` | Reverse proxy, load balancer, CDN |
| Request header `X-Forwarded-Port` is `443` | Some load balancers |
| Server variable `HTTPS` is non-empty and not `off` | The web server, when it handles TLS itself |
| Server variable `SERVER_PORT` is `443` | Same as above |
| `__TYPECHO_SECURE__` is defined and true | You, in `config.inc.php` |

Having the proxy send `X-Forwarded-Proto` is recommended. With nginx as the front-end proxy, for example:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $remote_addr;
}
```

If the proxy cannot add request headers and the site is **only accessed over HTTPS**, tell Typecho directly that it is always HTTPS:

```php
define('__TYPECHO_SECURE__', true);
```

:::caution
When `__TYPECHO_SECURE__` is true, every request is treated as HTTPS, including requests made over `http://`. Do not set it while the site can still be reached over HTTP.
:::

## Site URL: `__TYPECHO_SITE_URL__` and `__TYPECHO_DYNAMIC_SITE_URL__` {#site-url}

| Constant | Effect |
|---|---|
| `__TYPECHO_SITE_URL__` | Uses the constant's value instead of the site URL stored in the database. The "Site URL" field in the admin "General settings" is hidden, and saving the settings no longer changes it |
| `__TYPECHO_DYNAMIC_SITE_URL__` | When true, the site URL is the root URL of the current request, that is, whatever domain the visitor uses. If `__TYPECHO_SITE_URL__` is also defined, this one wins |

```php
// Fix the site URL, e.g. when several servers share one database, or to keep it from being changed by mistake in the admin panel
define('__TYPECHO_SITE_URL__', 'https://blog.example.com');

// Or: the site is bound to several domains, and each request uses the domain it came in on
define('__TYPECHO_DYNAMIC_SITE_URL__', true);
```

Once more: these two constants only affect the **site URL**, not permalinks. If permalinks are wrong, see the next section.

## Domain and scheme of permalinks: `__TYPECHO_URL_PREFIX__` and `__TYPECHO_ROOT_URL__` {#root-url}

Permalinks use the request's root URL. The best approach is to have the proxy pass the correct `Host` and `X-Forwarded-Proto` (see `proxy_set_header` above). If that is not possible, two constants can override it:

| Constant | What it overrides | Example |
|---|---|---|
| `__TYPECHO_URL_PREFIX__` | Only the "scheme + domain" part; the installation directory is still computed from the request. Typecho also uses it when determining "the URL of the current request" | `'https://blog.example.com'` |
| `__TYPECHO_ROOT_URL__` | The whole root URL (including the installation directory) | `'https://blog.example.com/blog'` |

**Behind a proxy, prefer `__TYPECHO_URL_PREFIX__`**. The reason: when a post is opened, Typecho compares "the post's permalink" with "the URL of the current request", and if the domain or path differs, it issues a 301 redirect to the permalink (`var/Widget/Archive.php`).

- With `__TYPECHO_URL_PREFIX__`, both sides use the same prefix, so no extra redirect happens;
- With `__TYPECHO_ROOT_URL__`, if the `Host` the proxy passes to PHP is a different value, every post request is redirected with 301 to the domain of `__TYPECHO_ROOT_URL__`; that redirect goes back through the proxy and reaches PHP with the original `Host` again, forming a **redirect loop**. For example, with the root URL set to `https://blog.example.com` while PHP receives an internal address as `Host`, a request for `/archives/1/` always returns 301; after switching to `__TYPECHO_URL_PREFIX__` it returns 200.

`__TYPECHO_URL_PREFIX__` does not make Typecho consider the request HTTPS; the automatic `http` → `https` replacement of the site URL still depends on the detection in the previous section.

## Client IP: `__TYPECHO_IP_SOURCE__` {#ip-source}

The IP recorded with comments, the comment interval limit, "reject if the same IP already has a spam trackback" and similar features all use the client IP. `getIp()` (`var/Typecho/Request.php`) looks in this order:

1. The request header named by `__TYPECHO_IP_SOURCE__`, or `X-Forwarded-For` when it is not defined;
2. The request header `Client-Ip`;
3. The server variable `REMOTE_ADDR`.

The value found is split on commas and the **first** item is taken; it is checked for being a valid IP, and recorded as `unknown` if it is not.

| Scenario | Recommendation |
|---|---|
| Behind nginx or another proxy | Have the **outermost** proxy **overwrite** this header with `proxy_set_header X-Forwarded-For $remote_addr;` instead of appending with `$proxy_add_x_forwarded_for`; otherwise visitors can forge the first value themselves |
| Behind Cloudflare | `define('__TYPECHO_IP_SOURCE__', 'CF-Connecting-IP');` |
| The proxy sends `X-Real-IP` | `define('__TYPECHO_IP_SOURCE__', 'X-Real-IP');` |
| No proxy at all, visitors connect directly | Visitors can send `X-Forwarded-For` or `Client-Ip` request headers themselves, and Typecho will trust them. You can drop these two headers on the web server, and treat comment IPs as informational only |

The constant's value is the name of the request header (such as `X-Real-IP`); Typecho converts it to `HTTP_X_REAL_IP` automatically when reading it.

## Cookies: `__TYPECHO_COOKIE_OPTIONS__` {#cookie-options}

The login state is stored in cookies. Typecho's cookies have a few default behaviours (`var/Typecho/Cookie.php`):

- Cookie names carry a prefix, which is the **MD5 of the root URL**. When the root URL changes (for example the scheme changes from `http` to `https`, or the domain changes), the existing login cookie can no longer be read and you have to log in again;
- The domain (`domain`) and path (`path`) are taken from the root URL;
- By default the `Secure` and `HttpOnly` attributes are **not** set.

`__TYPECHO_COOKIE_OPTIONS__` can change the domain and add these two attributes. It is an array, and **all three keys must be present** (the source reads these three keys directly; if one is missing, PHP 8 emits an "Undefined array key" warning):

```php
define('__TYPECHO_COOKIE_OPTIONS__', [
    'domain'   => '',      // empty means the domain of the root URL; write '.example.com' to share it across subdomains
    'secure'   => true,    // sent over HTTPS only; do not enable while the site can be reached over HTTP
    'httponly' => true,    // not readable by page scripts
]);
```

1.3 does not support setting the `SameSite` attribute.

:::caution
Before enabling `httponly`, make sure the themes and plugins you use do not read Typecho's cookies in front-end JavaScript. This cannot be determined from the Typecho source; test it yourself.
:::

## Common problems

### Mixed content: the page is HTTPS, but styles or images are HTTP {#mixed-content}

Theme files and attachments use the site URL. Check each item:

1. Whether the site URL in "Settings → General" in the admin panel is `https://`. Changing it to `https://` is the most direct fix;
2. If the site URL is `http://`, Typecho only replaces it automatically when it judges the current request to be HTTPS; check whether the proxy sends `X-Forwarded-Proto`, or define `__TYPECHO_SECURE__`;
3. If `__TYPECHO_UPLOAD_URL__`, `__TYPECHO_THEME_URL__` or `__TYPECHO_PLUGIN_URL__` are defined, their values must be `https://`;
4. **Image URLs in post content** are the full URLs at the moment the attachment was inserted; they are stored in the content and do not follow the settings. After switching from HTTP to HTTPS, `http://` images in old posts need a bulk replacement; see [Migrating from other blogs](./migration.md#replace-urls).

### Redirect loops {#redirect-loop}

| Symptom | Cause and fix |
|---|---|
| Every page redirects in a loop and the browser reports "too many redirects" | Most likely the HTTPS settings of the proxy or CDN conflict with the origin: the CDN fetches from the origin over HTTP, and the origin redirects HTTP to HTTPS. Make the CDN fetch from the origin over HTTPS, or remove the forced redirect on the origin and let the CDN handle it |
| The home page works, but opening a post loops | `__TYPECHO_ROOT_URL__` is defined, but its domain or path does not match the request PHP receives, see [above](#root-url). Use `__TYPECHO_URL_PREFIX__` instead, or have the proxy pass the correct `Host` |
| The proxy maps `/blog/` to `/` on the origin | Permalinks contain `/blog/` but the path PHP receives does not, so post pages loop as well. Install the origin under the `/blog/` directory too, so the paths match |

### Back at the login page after logging in {#login-loop}

1. **Inconsistent scheme detection**: the cookie name depends on the root URL. If requests from the same visitor are sometimes judged HTTP and sometimes HTTPS (for example when only some nodes behind a load balancer send `X-Forwarded-Proto`), the cookie written at login cannot be read on the next request. Make every request carry the same headers, or define `__TYPECHO_SECURE__`.
2. **`secure` enabled but the site accessed over HTTP**: browsers do not send cookies with `Secure` over HTTP.
3. **Wrong `domain`**: when the cookie domain does not match the domain being visited, the browser rejects the cookie.
4. **The CDN caches the admin panel or login requests**: on the CDN, mark the admin directory (`/admin/` by default) and `/index.php/action/` as not cacheable.

### CDN caching notes

- Typecho does no cache control for front-end pages. If you cache HTML on the CDN, pages shown after a comment is submitted and pages seen by logged-in users may be cached too; cache only static files, or bypass the cache for requests that carry a Typecho login cookie.
- Do not cache the admin path, `/index.php/action/` or `/action/`.
