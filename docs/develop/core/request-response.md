---
title: Request、Response 与常用工具
sidebar_position: 6
---

# Request、Response 与常用工具

本页说明怎样读取请求参数、怎样输出响应，以及 `Typecho\Common` 中最常用的静态方法。

## 两层对象

| 对象 | 类 | 取得方式 |
|---|---|---|
| 全局请求 | `Typecho\Request` | `\Typecho\Request::getInstance()` |
| 全局响应 | `Typecho\Response` | `\Typecho\Response::getInstance()` |
| Widget 的请求 | `Typecho\Widget\Request` | Widget 中的 `$this->request`，模板中同样可用 |
| Widget 的响应 | `Typecho\Widget\Response` | Widget 中的 `$this->response` |

全局对象在一个请求中只有一个。Widget 级的对象包装了全局对象，多了两样东西：创建 Widget 时传入的请求参数（例如路由占位符的值），以及 `filter()` 过滤器和 `redirect()`、`throwJson()` 等便捷方法。在 Widget、插件和模板中一般使用 Widget 级对象。

## 读取参数

### get()

```php
public function get(string $key, $default = null, ?bool &$exists = true)
```

按以下顺序查找第一个存在的值：

1. 创建 Widget 时传入的请求参数（路由占位符、`alloc()` 的第二个参数）；
2. `$_GET`；
3. `$_POST`。

所以同名时 GET 优先于 POST。另有两条规则：

- 值为空字符串时视为不存在，返回 `$default`；
- 参数值与 `$default` 中一个是数组、另一个不是时，返回 `$default`：默认值不是数组时，提交的数组（`a[]=1`）被忽略；默认值是数组时，提交的字符串被忽略。这能防止把数组当作字符串使用。只检查是不是数组，不按默认值的类型转换或校验：`get('page', 1)` 读到的是字符串 `"2"`，需要整数时用 `filter('int')->get('page', 1)`，取值范围仍要自己检查。

第三个参数用引用返回参数是否存在：

```php
$page = $this->request->get('page', 1, $exists);
if (!$exists) {
    // 没有提交 page
}
```

### 其他读取方法

| 方法 | 作用 |
|---|---|
| `getArray($key)` | 总是返回数组：提交的是数组就返回数组，是单个值就包装成只有一个元素的数组，不存在时返回空数组 |
| `from(...$keys)` | 一次读多个，返回 `['键' => 值]`，不存在的为 `null` |
| `is($query)` | 判断参数是否满足条件。`is('do=delete')` 要求 `do` 等于 `delete`；`is('cid')` 只要求 `cid` 存在；多个条件用 `&` 连接，全部满足才为真 |
| `get('@json')` | 请求的 `Content-Type` 为 `application/json` 时，返回解码后的整个请求体（数组）。JSON 中的字段不能直接用 `get('字段名')` 读取 |

请求对象的魔术读取（`$this->request->cid`、`isset($this->request->cid)`）在 1.3 中标记为弃用，新代码请使用 `get()`。

### filter()：过滤一次

`Widget\Request::filter()` 为**下一次**读取设置过滤器，读取后过滤器即被清除：

```php
$cid  = $this->request->filter('int')->get('cid');            // 转为整数
$slug = $this->request->filter('slug')->get('slug');          // 转为缩略名
$text = $this->request->filter('trim', 'html')->get('text');  // 依次 trim、htmlspecialchars
```

| 名字 | 等于 |
|---|---|
| `int`、`integer` | `intval` |
| `encode` | `urlencode` |
| `html` | `htmlspecialchars` |
| `search` | `Common::filterSearchQuery` |
| `xss` | `Common::removeXSS` |
| `url` | `Common::safeUrl` |
| `slug` | `Common::slugName` |

不在表中的字符串和任意 callable 也可以作为过滤器，如上例中的 `trim`。读到的是数组时，过滤器作用于每个元素。

### 请求信息

| 方法 | 返回 |
|---|---|
| `isGet()`、`isPost()`、`isPut()` | 是否为 GET、POST、PUT 请求（`bool`）。请求方法的名字用 `getServer('REQUEST_METHOD')` 读取 |
| `isAjax()` | 带 `X-Requested-With: XMLHttpRequest` 头 |
| `isJson()` | `Content-Type` 为 `application/json` |
| `isSecure()` | 是否 HTTPS：检查 `HTTPS`、端口 443、反向代理传来的 `X-Forwarded-Proto`/`X-Forwarded-Port`，以及常量 `__TYPECHO_SECURE__`，见 [HTTPS 与反向代理](../../guide/operations/https-proxy.md) |
| `getIp()` | 客户端 IP |
| `getHeader($key, $default)` | 请求头，例如 `getHeader('User-Agent')` |
| `getServer($name, $default)` | `$_SERVER` 中的值 |
| `getAgent()`、`getReferer()` | User-Agent、来源地址 |
| `getPathInfo()` | 用于路由匹配的路径 |
| `getRequestUrl()` | 完整的请求地址 |
| `makeUriByRequest($params)` | 在当前请求地址上合并查询参数（字符串或数组，同名的覆盖），返回新地址 |

`Typecho\Request` 另有 `isCli()`，判断是否在命令行中运行。

## 输出响应 {#response}

### 设置状态与头信息

```php
$this->response->setStatus(404);
$this->response->setHeader('Cache-Control', 'no-store');
$this->response->setContentType('application/json');
```

这些设置先保存在 `Typecho\Response` 中，在输出缓冲区发送时才真正发出，所以页面已经开始输出后仍可以调用。设置 Cookie 用 `\Typecho\Cookie::set()`。

1.3.0 中，`setStatus()` 设置的状态码如果不在 `Typecho\Response` 内置的状态码表中（例如 418），响应行的原因短语为空，并产生一条 PHP 警告。

### 结束请求的方法

下面这些 `Widget\Response` 方法输出内容后直接 `exit`，调用之后的代码不会执行，`index.php:end` 钩子也不会触发：

| 方法 | 输出 |
|---|---|
| `throwJson($data)` | `json_encode($data)`，`Content-Type: application/json` |
| `throwXml($data)` | `<?xml …?><response>…</response>`，数组的整数键输出为 `<item>`，`Content-Type: text/xml` |
| `throwContent($content, $contentType = 'text/html')` | 原样输出字符串 |
| `throwFile($file, $contentType = null)` | 输出文件内容，设置 `Content-Length` |
| `throwCallback($callback, $contentType = 'text/html')` | 调用回调输出 |
| `redirect($url, $isPermanently = false)` | 302（或 301）跳转。地址先经过 `Common::safeUrl()`，`javascript:` 之类的地址会变成 `/` |
| `goBack($suffix = null, $default = null)` | 跳回来源页（`Referer`）。`$suffix` 中的查询参数和锚点合并进来源地址；没有来源时跳到 `$default`，再没有就跳到 `/` |

例如后台表单处理完后的常见写法：

```php
\Widget\Notice::alloc()->set(_t('设置已经保存'), 'success');
$this->response->goBack();
```

`Widget\Notice` 把提示存入 Cookie，下一个后台页面中的脚本（`admin/common-js.php`）显示它并删除 Cookie。`goBack('?saved=1#done')` 在来源地址 `…/options-general.php?x=1` 上得到 `…/options-general.php?x=1&saved=1#done`。

`throwFinish()` 与上面不同：它结束响应但让脚本继续执行，用于在响应之后做耗时的工作。行为取决于 PHP 的运行方式：在 PHP-FPM 下调用 `fastcgi_finish_request()`，已有输出发送给客户端；在没有 `fastcgi_finish_request()` 的环境中（如 Apache 的 mod_php、PHP 内置服务器），它丢弃当前输出缓冲区中的内容，只发送响应头和一个空的正文。

在沙箱中创建的 Widget（见 [Widget 组件模型](./widget.md#sandbox)）调用这些方法时不会输出也不会 `exit`，只是中止该 Widget 的执行。

## Typecho\Common 常用静态方法

`Typecho\Common`（`var/Typecho/Common.php`）中都是静态方法，可以在任何地方调用：

| 方法 | 作用与示例 |
|---|---|
| `url($path, $prefix)` | 拼接地址：`url('archives/1/', 'https://example.com/')` → `https://example.com/archives/1/`。只做拼接，不处理 `..` |
| `safeUrl($url)` | 清理用于跳转的地址：去掉空白、换行、引号和尖括号；协议不是 `http` 或 `https` 的地址变成 `/` |
| `buildUrl(array $parts)` | `parse_url()` 的逆操作 |
| `slugName($str, $default = '', $maxLength = 128)` | 生成缩略名：`'Hello World! 你好'` → `Hello-World-你好`；结果为空时返回 `$default` |
| `subStr($str, $start, $length, $trim = '...')` | 按字符截取，`$length` 包括后缀：`subStr('Typecho 是一个博客程序', 0, 9)` → `Typech...` |
| `strLen($str)` | 按 UTF-8 字符计数 |
| `strBy($a, $b)` | `$a` 不为 `null` 且不为空字符串时返回 `$a`，否则返回 `$b` |
| `randString($length, $specialChars = false)` | 随机字符串 |
| `hash($string, $salt = null)` / `hashValidate($from, $to)` | 生成和校验 `$T$` 开头的加盐散列，Cookie 中的登录凭据就用它校验 |
| `timeToken($secret)` / `timeTokenValidate($token, $secret, $timeout = 5)` | 生成和校验有效期为几秒的令牌 |
| `removeXSS($html)` | 破坏 HTML 中危险的属性和协议：`onclick` → `on<x>click`，`javascript:` → `ja<x>vasc<x>ript:` |
| `stripTags($html, $allowableTags = null)` | 去掉标签，只保留允许的标签与属性：`stripTags($html, '<a href="">')` 只留下带 `href` 的 `<a>` |
| `fixHtml($html)` | 补全未闭合的标签：`<p><b>unclosed` → `<p><b>unclosed</b></p>` |
| `filterSearchQuery($query)` | 把搜索词规范化：`foo-bar baz` → `foo bar baz` |
| `gravatarUrl($mail, $size, $rating, $default, $isSecure = true)` | Gravatar 头像地址，返回值中的 `&` 已转义为 `&amp;`，可直接输出到 HTML 属性中 |
| `checkSafeHost($host)` | 主机名为 `localhost` 或解析到私有、保留地址（包括 `127.0.0.1`）时返回 `false`。在服务器端请求用户提供的地址前用它检查 |
| `idnToUtf8($url)` | 把地址中的国际化域名从 Punycode 转回 Unicode（1.3 新增） |
| `nativeClassName($class)` | `\Widget\Archive` → `Widget_Archive` |

另外，`Common::VERSION` 是程序版本号（1.3.0 中为 `'1.3.0'`），`Common::error($exception)` 输出错误页。
