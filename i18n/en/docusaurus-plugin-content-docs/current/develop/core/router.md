---
title: Routing
sidebar_position: 3
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Routing

For every front-end URL, the router `Typecho\Router` (`var/Typecho/Router.php`) decides which widget handles it. This page explains the structure of the routing table, how rules are written, how to generate URLs from routes (reverse routing), and how widgets supply URL parameters through `ParamsDelegateInterface`. For the 25 default routes, see [Default routes and actions](../reference/routes-actions.md); for a complete example of a plugin adding a route, see [Routes, actions and admin panels](../plugin/routes-actions.md).

## Routing table

The routing table is stored in the option `routingTable`. It is an ordered array keyed by route name, and each route has three fields:

```php
'post' => [
    'url'    => '/archives/[cid:digital]/',   // rule
    'widget' => '\Widget\Archive',            // handler class
    'action' => 'render',                     // method called after creation; may be null
],
```

### Compiled result cached under key 0 {#compiled-cache}

Before rules can be used, `Typecho\Router\Parser` compiles them into regular expressions. The compiled result is stored in the same option: when `Widget\Options` first reads `routingTable` and finds no key `0` in it, it compiles the whole table, puts the result under key `0` and writes it back to the database. On every later request, `Router::setRoutes()` sees that key `0` exists and uses it directly without compiling again.

The `routingTable` in the database therefore has this structure (excerpt):

```json
{
  "0": {
    "index": {"url": "/", "widget": "\\Widget\\Archive", "action": "render",
              "regx": "|^[/]?$|", "format": "/", "params": []},
    "post":  {"url": "/archives/[cid:digital]/", "widget": "\\Widget\\Archive", "action": "render",
              "regx": "|^/archives/([0-9]+)[/]?$|", "format": "/archives/%s/", "params": ["cid"]}
  },
  "index": {"url": "/", "widget": "\\Widget\\Archive", "action": "render"},
  "post":  {"url": "/archives/[cid:digital]/", "widget": "\\Widget\\Archive", "action": "render"}
}
```

After compilation each route has three extra fields:

| Field | Contents |
|---|---|
| `regx` | The regular expression used to match the path |
| `format` | The rule with every placeholder replaced by `%s`, used to generate URLs from the route |
| `params` | The list of placeholder names, in the same order as the regular expression's capture groups |

Every piece of core code that modifies the routing table deletes key `0` first: `Utils\Helper::addRoute()`/`removeRoute()`, saving "Settings → Permalinks", and the upgrade script `v1_3_0()`. The next request recompiles the table when it reads the options. If you edit `routingTable` in the database directly, you must also delete key `0`, or your change will not take effect.

### Order

Routes are matched in table order; see [Request lifecycle](./request-lifecycle.md#dispatch) for details. `Helper::addRoute($name, $url, $widget, $action, $after)` appends the new route to the end of the table by default; when `$after` is given, it inserts the route after that route. A route with a broad rule (such as `[permalink:string]/[type:alpha]` of `feedback`) matches many URLs, but when its handler class throws a 404 exception the router goes on to try the following routes, so routes later in the table still get a chance to handle the request.

## Rule syntax

A rule is a path in which square brackets mark placeholders. `Router\Parser::parse()` first applies `preg_quote()` to the whole rule and then replaces each placeholder with a capture group:

| Syntax | Generated capture group (using `digital` as an example) | Meaning |
|---|---|---|
| `[name]` | `([^/]+)` | One or more characters other than `/`; same as `[name:char]` |
| `[name:type]` | `([0-9]+)` | One or more characters of that type |
| `[name:type:N]` | `([0-9]{N})` | Exactly N; when `N` is `0`, `([0-9]*)`, that is, zero or more |
| `[name:type:min:max]` | `([0-9]{min,max})` | Between min and max |

The six types:

| Type | Character class | Example matches |
|---|---|---|
| `string` | `.` (any character, including `/`) | `a`, `x/y/z` |
| `char` | `[^/]` | `foo`, `a.b` |
| `digital` | `[0-9]` | `2026` |
| `alpha` | `[_0-9a-zA-Z-]` | `foo-bar_1` |
| `alphaslash` | `[_0-9a-zA-Z-/]` | `foo/bar` |
| `split` | `(?:[^/]+/)` repeated, followed by `[^/]+` | `x/y`, `x/y/z`; `[name:split:N]` has exactly N+1 segments |

The trailing `/` of the whole rule is removed and replaced with an optional `[/]?`, and anchors are added at both ends. For example:

| Rule | `regx` | Matches | Does not match |
|---|---|---|---|
| `/a/[x:digital:4]/` | `\|^/a/([0-9]{4})[/]?$\|` | `/a/2026/`, `/a/2026` | `/a/20/` |
| `/a/[x:digital:2:3]/` | `\|^/a/([0-9]{2,3})[/]?$\|` | `/a/20/`, `/a/123/` | `/a/1234/` |
| `/a/[x:split]/` | `\|^/a/((?:[^/]+/)+[^/]+)[/]?$\|` | `/a/x/y/` (`x=x/y`) | `/a/x/` |
| `/[a:digital]-[b:alpha].html` | `\|^/([0-9]+)\-([_0-9a-zA-Z-]+)\.html[/]?$\|` | `/12-foo.html` | `/foo-12.html` |

Note:

- `string` and `alphaslash` can match `/` and are greedy; at the end of a rule they also swallow the trailing `/` of the path into the parameter. For example, when the rule `/a/[x:string]` matches `/a/foo/`, `x` is `foo/`; when the rule `/feed[feed:string:0]` of the default route `feed` matches `/feed/`, the `feed` parameter is `/`. Take this into account when you handle parameters;
- Text outside placeholders is matched literally;
- Matching is done against the whole path (without the query string); a `/index.php` prefix in the path does not take part in matching.

After a successful match, the captured values are put into an array in `params` order and passed to the handler class as request parameters. Read them in the handler class with `$this->request->get('cid')`; when names clash, route parameters take precedence over GET and POST parameters.

## Generating URLs from routes: Router::url()

```php
public static function url(string $name, $value = null, ?string $prefix = null): string
```

Generates a URL from a route name and parameters:

- When `$value` is an array, values are taken by the names in `params`;
- When `$value` implements `ParamsDelegateInterface`, its `getRouterParam($name)` is called for each parameter;
- Missing parameters are left as `{name}`;
- `$prefix` is prepended with `Common::url()`; for front-end URLs you usually pass `$options->index` (which includes `/index.php` when URL rewriting is not enabled);
- When the route name does not exist, `#` is returned.

```php
use Typecho\Router;
use Widget\Options;

$options = Options::alloc();

Router::url('post', ['cid' => 5]);                        // /archives/5/
Router::url('post', ['cid' => 5], $options->index);       // https://example.com/archives/5/
Router::url('category_page', ['slug' => 'tech']);         // /category/tech/{page}/
Router::url('no_such_route', []);                         // #
```

`Router::get($name)` returns the compiled result of one route (`null` if it does not exist), and `Router::$current` is the name of the last route matched in the current request.

## ParamsDelegateInterface {#params-delegate}

```php
namespace Typecho\Router;

interface ParamsDelegateInterface
{
    public function getRouterParam(string $key): string;
}
```

New in 1.3. An object that implements this interface can be passed directly as the second argument of `Router::url()`, and the object itself decides the value of each placeholder. The base classes of the four kinds of data rows all implement it:

| Class | Supported parameters |
|---|---|
| `Widget\Base\Contents` | `cid`, `slug`, `directory` (the path of a multi-level page or category), `category` (the slug of the first category), `year`, `month`, `day` |
| `Widget\Base\Metas` | `mid`, `slug`, `directory` |
| `Widget\Base\Users` | `uid` |
| `Widget\Base\Comments` | `permalink` (the path of the content it belongs to), `commentPage` |

Unknown parameters return `{name}`. This is how the URLs of posts, categories and so on are generated; taking `Widget\Base\Contents` as an example:

```php title="var/Widget/Base/Contents.php"
protected function ___path(): string
{
    return Router::url($this->type, $this);
}

protected function ___permalink(): string
{
    return Common::url($this->path, $this->options->index);
}
```

So when parameters such as `[slug]`, `[category]` or `[year:digital:4]` appear in a permalink rule, you do not need to write concatenation code for each rule. Your own classes can implement this interface too:

```php
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;

class Release implements ParamsDelegateInterface
{
    private string $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function getRouterParam(string $key): string
    {
        return $key === 'version' ? urlencode($this->version) : '{' . $key . '}';
    }
}

// Assuming the route was added with Helper::addRoute('release', '/release/[version]/', ...)
echo Router::url('release', new Release('1.3.0'), $options->index);
```

## Resolving a path in code: Router::match()

```php
public static function match(string $pathInfo, $parameter = null, bool $once = true)
```

Resolves a path against the routing table into the corresponding widget (it only creates the widget and does not call the route's `action`), and returns `false` when nothing is found. The core uses it to turn the "post URL" in comment submissions, comment pagination, feeds and pingbacks back into a `Widget\Archive`. When `$once` is `true` (the default), a second call within the same request throws a 404 exception directly, to prevent recursive matching.
