---
title: Request, Response and Common Utilities
sidebar_position: 6
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Request, Response and Common Utilities

This page explains how to read request parameters, how to output a response, and the most commonly used static methods of `Typecho\Common`.

## Two layers of objects

| Object | Class | How to get it |
|---|---|---|
| Global request | `Typecho\Request` | `\Typecho\Request::getInstance()` |
| Global response | `Typecho\Response` | `\Typecho\Response::getInstance()` |
| Widget request | `Typecho\Widget\Request` | `$this->request` in a widget; also available in templates |
| Widget response | `Typecho\Widget\Response` | `$this->response` in a widget |

There is only one global object of each kind per request. The widget-level objects wrap the global ones and add two things: the request parameters passed in when the widget was created (for example the values of route placeholders), and the `filter()` filters plus convenience methods such as `redirect()` and `throwJson()`. In widgets, plugins and templates you generally use the widget-level objects.

## Reading parameters

### get()

```php
public function get(string $key, $default = null, ?bool &$exists = true)
```

Looks for the first value that exists, in this order:

1. The request parameters passed in when the widget was created (route placeholders, the second argument of `alloc()`);
2. `$_GET`;
3. `$_POST`.

So when names clash, GET takes precedence over POST. Two further rules:

- An empty string is treated as non-existent, and `$default` is returned;
- When the type of the value does not match the type of `$default`, `$default` is returned: if the default is not an array, a submitted array (`a[]=1`) is ignored; if the default is an array, a submitted string is ignored. This prevents an array from being used as a string.

The third argument returns, by reference, whether the parameter exists:

```php
$page = $this->request->get('page', 1, $exists);
if (!$exists) {
    // page was not submitted
}
```

### Other read methods

| Method | Purpose |
|---|---|
| `getArray($key)` | Always returns an array: a submitted array is returned as is, a single value is wrapped in a one-element array, and an empty array is returned when the parameter does not exist |
| `from(...$keys)` | Reads several at once and returns `['key' => value]`; missing ones are `null` |
| `is($query)` | Checks whether parameters meet conditions. `is('do=delete')` requires `do` to equal `delete`; `is('cid')` only requires `cid` to exist; join several conditions with `&`, and the result is true only if all are met |
| `get('@json')` | When the request's `Content-Type` is `application/json`, returns the whole decoded request body (an array). Fields in the JSON cannot be read directly with `get('field name')` |

Magic reads on the request object (`$this->request->cid`, `isset($this->request->cid)`) are marked as deprecated in 1.3; new code should use `get()`.

### filter(): filter once

`Widget\Request::filter()` sets filters for the **next** read; the filters are cleared after the read:

```php
$cid  = $this->request->filter('int')->get('cid');            // to an integer
$slug = $this->request->filter('slug')->get('slug');          // to a slug
$text = $this->request->filter('trim', 'html')->get('text');  // trim, then htmlspecialchars
```

| Name | Equivalent to |
|---|---|
| `int`, `integer` | `intval` |
| `encode` | `urlencode` |
| `html` | `htmlspecialchars` |
| `search` | `Common::filterSearchQuery` |
| `xss` | `Common::removeXSS` |
| `url` | `Common::safeUrl` |
| `slug` | `Common::slugName` |

Strings not in the table and any callable can also be used as filters, like `trim` in the example above. When the value read is an array, the filters apply to each element.

### Request information

| Method | Returns |
|---|---|
| `isGet()`, `isPost()`, `isPut()` | The request method |
| `isAjax()` | Whether the `X-Requested-With: XMLHttpRequest` header is present |
| `isJson()` | Whether `Content-Type` is `application/json` |
| `isSecure()` | Whether the request is HTTPS: checks `HTTPS`, port 443, `X-Forwarded-Proto`/`X-Forwarded-Port` passed by a reverse proxy, and the constant `__TYPECHO_SECURE__`; see [HTTPS and reverse proxies](../../guide/operations/https-proxy.md) |
| `getIp()` | The client IP |
| `getHeader($key, $default)` | A request header, for example `getHeader('User-Agent')` |
| `getServer($name, $default)` | A value from `$_SERVER` |
| `getAgent()`, `getReferer()` | The User-Agent and the referring URL |
| `getPathInfo()` | The path used for route matching |
| `getRequestUrl()` | The full request URL |
| `makeUriByRequest($params)` | Merges query parameters (a string or an array; same-name parameters are overwritten) into the current request URL and returns the new URL |

`Typecho\Request` also has `isCli()`, which tells whether the code is running on the command line.

## Outputting a response {#response}

### Setting the status and headers

```php
$this->response->setStatus(404);
$this->response->setHeader('Cache-Control', 'no-store');
$this->response->setContentType('application/json');
```

These settings are first stored in `Typecho\Response` and are only actually sent when the output buffer is sent, so you can still call them after the page has started output. To set cookies, use `\Typecho\Cookie::set()`.

In 1.3.0, if the status code set by `setStatus()` is not in the built-in status code table of `Typecho\Response` (for example 418), the reason phrase of the response line is empty and a PHP warning is raised.

### Methods that end the request

The following `Widget\Response` methods `exit` right after output; code after the call does not run, and the `index.php:end` hook is not triggered either:

| Method | Output |
|---|---|
| `throwJson($data)` | `json_encode($data)`, `Content-Type: application/json` |
| `throwXml($data)` | `<?xml …?><response>…</response>`; integer keys of arrays are output as `<item>`; `Content-Type: text/xml` |
| `throwContent($content, $contentType = 'text/html')` | Outputs the string as is |
| `throwFile($file, $contentType = null)` | Outputs the file contents and sets `Content-Length` |
| `throwCallback($callback, $contentType = 'text/html')` | Calls the callback to produce output |
| `redirect($url, $isPermanently = false)` | A 302 (or 301) redirect. The URL first goes through `Common::safeUrl()`; URLs such as `javascript:` become `/` |
| `goBack($suffix = null, $default = null)` | Redirects back to the referring page (`Referer`). Query parameters and the anchor in `$suffix` are merged into the referring URL; without a referrer it redirects to `$default`, and failing that to `/` |

For example, a common pattern at the end of admin form handling:

```php
\Widget\Notice::alloc()->set(_t('Settings saved'), 'success');
$this->response->goBack();
```

`Widget\Notice` stores the notice in a cookie; a script on the next admin page (`admin/common-js.php`) displays it and deletes the cookie. `goBack('?saved=1#done')` on the referring URL `…/options-general.php?x=1` gives `…/options-general.php?x=1&saved=1#done`.

`throwFinish()` is different from the methods above: it sends the existing output to the client and ends the connection (calling `fastcgi_finish_request()` under PHP-FPM), but the script keeps running; it is used for time-consuming work after the response.

A widget created in a sandbox (see [The widget component model](./widget.md#sandbox)) neither outputs anything nor `exit`s when it calls these methods; only that widget's execution is aborted.

## Common static methods of Typecho\Common

`Typecho\Common` (`var/Typecho/Common.php`) contains only static methods, which can be called from anywhere:

| Method | Purpose and example |
|---|---|
| `url($path, $prefix)` | Joins a URL: `url('archives/1/', 'https://example.com/')` → `https://example.com/archives/1/`. It only joins and does not resolve `..` |
| `safeUrl($url)` | Cleans a URL used for redirects: removes whitespace, line breaks, quotes and angle brackets; URLs whose scheme is not `http` or `https` become `/` |
| `buildUrl(array $parts)` | The inverse of `parse_url()` |
| `slugName($str, $default = '', $maxLength = 128)` | Generates a slug: `'Hello World! Café'` → `Hello-World-Café` (letters outside ASCII are kept); returns `$default` when the result is empty |
| `subStr($str, $start, $length, $trim = '...')` | Truncates by characters; `$length` includes the suffix: `subStr('Typecho is a blogging platform', 0, 9)` → `Typech...` |
| `strLen($str)` | Counts UTF-8 characters |
| `strBy($a, $b)` | Returns `$a` when it is neither `null` nor an empty string, otherwise `$b` |
| `randString($length, $specialChars = false)` | A random string |
| `hash($string, $salt = null)` / `hashValidate($from, $to)` | Generates and validates salted hashes that start with `$T$`; the login credentials in cookies are validated with them |
| `timeToken($secret)` / `timeTokenValidate($token, $secret, $timeout = 5)` | Generates and validates tokens valid for a few seconds |
| `removeXSS($html)` | Breaks dangerous attributes and schemes in HTML: `onclick` → `on<x>click`, `javascript:` → `ja<x>vasc<x>ript:` |
| `stripTags($html, $allowableTags = null)` | Removes tags, keeping only the allowed tags and attributes: `stripTags($html, '<a href="">')` leaves only `<a>` with `href` |
| `fixHtml($html)` | Closes unclosed tags: `<p><b>unclosed` → `<p><b>unclosed</b></p>` |
| `filterSearchQuery($query)` | Normalizes a search term: `foo-bar baz` → `foo bar baz` |
| `gravatarUrl($mail, $size, $rating, $default, $isSecure = true)` | The Gravatar avatar URL; `&` in the return value is already escaped as `&amp;`, so it can be output directly into an HTML attribute |
| `checkSafeHost($host)` | Returns `false` when the host name is `localhost` or resolves to a private or reserved address (including `127.0.0.1`). Use it to check a user-supplied URL before the server makes a request to it |
| `idnToUtf8($url)` | Converts an internationalized domain name in a URL from Punycode back to Unicode (new in 1.3) |
| `nativeClassName($class)` | `\Widget\Archive` → `Widget_Archive` |

In addition, `Common::VERSION` is the program version number (`'1.3.0'` in 1.3.0), and `Common::error($exception)` outputs the error page.
