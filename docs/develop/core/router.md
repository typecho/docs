---
title: 路由
sidebar_position: 3
---

# 路由

前台的每个地址都由路由器 `Typecho\Router`（`var/Typecho/Router.php`）决定交给哪个 Widget 处理。本页说明路由表的结构、规则的写法、怎样反向生成地址，以及 Widget 如何通过 `ParamsDelegateInterface` 为地址提供参数。默认的 25 条路由见[默认路由与 Action](../reference/routes-actions.md)，插件添加路由的完整示例见[路由、Action 与后台面板](../plugin/routes-actions.md)。

## 路由表

路由表保存在选项 `routingTable` 中，是一个以路由名为键的有序数组，每条路由有三个字段：

```php
'post' => [
    'url'    => '/archives/[cid:digital]/',   // 规则
    'widget' => '\Widget\Archive',            // 处理类
    'action' => 'render',                     // 创建后调用的方法，可以为 null
],
```

### 编译结果缓存在键 0 {#compiled-cache}

规则在使用前要由 `Typecho\Router\Parser` 编译成正则表达式。编译结果也存在同一个选项里：`Widget\Options` 第一次读取 `routingTable` 时，如果其中没有键 `0`，就编译整张表，把结果放在键 `0` 下写回数据库。之后每次请求，`Router::setRoutes()` 发现键 `0` 存在，直接使用它，不再编译。

数据库中的 `routingTable` 因此是这样的结构（节选）：

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

编译后每条路由多出三个字段：

| 字段 | 内容 |
|---|---|
| `regx` | 匹配路径用的正则 |
| `format` | 把每个占位符换成 `%s` 后的字符串，用于反向生成地址 |
| `params` | 占位符名字的列表，与正则的捕获组一一对应 |

核心中修改路由表的代码都会先删除键 `0`：`Utils\Helper::addRoute()`/`removeRoute()`、“设置 → 永久链接”保存时、升级脚本 `v1_3_0()`。下一次请求读取选项时会重新编译。直接改数据库中的 `routingTable` 时也要删掉键 `0`，否则修改不会生效。

### 顺序

路由按表中的顺序匹配，详见[请求生命周期](./request-lifecycle.md#dispatch)。`Helper::addRoute($name, $url, $widget, $action, $after)` 默认把新路由加到表的末尾；给出 `$after` 时插到该路由之后。规则宽泛的路由（如 `feedback` 的 `[permalink:string]/[type:alpha]`）会匹配很多地址，但处理类抛出 404 异常后路由器会继续尝试后面的路由，所以排在后面的路由仍然有机会处理请求。

## 规则语法

规则是一个路径，其中用方括号表示占位符。`Router\Parser::parse()` 先对整条规则做 `preg_quote()`，再把每个占位符替换成一个捕获组：

| 写法 | 生成的捕获组（以 `digital` 为例） | 含义 |
|---|---|---|
| `[name]` | `([^/]+)` | 一个或多个非 `/` 字符，等于 `[name:char]` |
| `[name:类型]` | `([0-9]+)` | 一个或多个该类型的字符 |
| `[name:类型:N]` | `([0-9]{N})` | 正好 N 个；`N` 为 `0` 时为 `([0-9]*)`，即零个或多个 |
| `[name:类型:最少:最多]` | `([0-9]{最少,最多})` | 最少到最多个 |

六种类型：

| 类型 | 字符类 | 能匹配的例子 |
|---|---|---|
| `string` | `.`（任意字符，包括 `/`） | `a`、`x/y/z` |
| `char` | `[^/]` | `foo`、`a.b` |
| `digital` | `[0-9]` | `2026` |
| `alpha` | `[_0-9a-zA-Z-]` | `foo-bar_1` |
| `alphaslash` | `[_0-9a-zA-Z-/]` | `foo/bar` |
| `split` | `(?:[^/]+/)` 重复后接 `[^/]+` | `x/y`、`x/y/z`；`[name:split:N]` 正好 N+1 段 |

整条规则最后的 `/` 被去掉，换成可选的 `[/]?`，并加上首尾锚定。例如：

| 规则 | `regx` | 能匹配 | 不能匹配 |
|---|---|---|---|
| `/a/[x:digital:4]/` | `\|^/a/([0-9]{4})[/]?$\|` | `/a/2026/`、`/a/2026` | `/a/20/` |
| `/a/[x:digital:2:3]/` | `\|^/a/([0-9]{2,3})[/]?$\|` | `/a/20/`、`/a/123/` | `/a/1234/` |
| `/a/[x:split]/` | `\|^/a/((?:[^/]+/)+[^/]+)[/]?$\|` | `/a/x/y/`（`x=x/y`） | `/a/x/` |
| `/[a:digital]-[b:alpha].html` | `\|^/([0-9]+)\-([_0-9a-zA-Z-]+)\.html[/]?$\|` | `/12-foo.html` | `/foo-12.html` |

注意：

- `string` 和 `alphaslash` 能匹配 `/`，而且是贪婪的，处在规则末尾时会把路径结尾的 `/` 也吞进参数。例如规则 `/a/[x:string]` 匹配 `/a/foo/` 时 `x` 为 `foo/`；默认路由 `feed` 的规则 `/feed[feed:string:0]` 匹配 `/feed/` 时，`feed` 参数为 `/`。处理参数时要考虑这种情况；
- 占位符之外的文字按原样匹配；
- 匹配是对整个路径（不含查询字符串）进行的，路径中的 `/index.php` 前缀不参与匹配。

匹配成功后，捕获的值按 `params` 的顺序组成数组，作为请求参数传给处理类。在处理类中用 `$this->request->get('cid')` 读取；同名时，路由参数优先于 GET 和 POST 参数。

## 反向生成地址：Router::url()

```php
public static function url(string $name, $value = null, ?string $prefix = null): string
```

根据路由名和参数生成地址：

- `$value` 是数组时，按 `params` 中的名字取值；
- `$value` 实现了 `ParamsDelegateInterface` 时，对每个参数调用它的 `getRouterParam($name)`；
- 缺少的参数原样保留为 `{名字}`；
- `$prefix` 用 `Common::url()` 拼在前面，前台地址一般传 `$options->index`（未启用地址重写时它带 `/index.php`）；
- 路由名不存在时返回 `#`。

```php
use Typecho\Router;
use Widget\Options;

$options = Options::alloc();

Router::url('post', ['cid' => 5]);                        // /archives/5/
Router::url('post', ['cid' => 5], $options->index);       // https://example.com/archives/5/
Router::url('category_page', ['slug' => 'tech']);         // /category/tech/{page}/
Router::url('no_such_route', []);                         // #
```

`Router::get($name)` 返回一条路由的编译结果（不存在时为 `null`），`Router::$current` 是当前请求最后一次匹配到的路由名。

## ParamsDelegateInterface {#params-delegate}

```php
namespace Typecho\Router;

interface ParamsDelegateInterface
{
    public function getRouterParam(string $key): string;
}
```

1.3 新增。实现了这个接口的对象可以直接作为 `Router::url()` 的第二个参数，由对象自己决定每个占位符的值。四种数据行的基类都实现了它：

| 类 | 支持的参数 |
|---|---|
| `Widget\Base\Contents` | `cid`、`slug`、`directory`（多级页面或分类的路径）、`category`（第一个分类的缩略名）、`year`、`month`、`day` |
| `Widget\Base\Metas` | `mid`、`slug`、`directory` |
| `Widget\Base\Users` | `uid` |
| `Widget\Base\Comments` | `permalink`（所属内容的路径）、`commentPage` |

不认识的参数返回 `{名字}`。文章、分类等的地址就是这样生成的，以 `Widget\Base\Contents` 为例：

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

所以当永久链接规则中出现 `[slug]`、`[category]`、`[year:digital:4]` 这类参数时，不需要为每种规则写拼接代码。自己的类也可以实现这个接口：

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

// 假设已经用 Helper::addRoute('release', '/release/[version]/', ...) 添加了路由
echo Router::url('release', new Release('1.3.0'), $options->index);
```

## 在代码中解析一个路径：Router::match()

```php
public static function match(string $pathInfo, $parameter = null, bool $once = true)
```

把一个路径按路由表解析成对应的 Widget（只创建 Widget，不调用路由的 `action`），找不到时返回 `false`。核心用它把评论提交、评论分页、订阅源和 Pingback 中的“文章地址”还原成 `Widget\Archive`。`$once` 为 `true`（默认）时，同一个请求中第二次调用会直接抛出 404 异常，以防止递归匹配。
