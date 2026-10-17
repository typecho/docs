---
title: HTTPS、反向代理与 CDN
sidebar_position: 2
---

# HTTPS、反向代理与 CDN

站点直接在 Web 服务器上启用 HTTPS 时，通常不需要额外设置：把后台“设置 → 基本”中的站点地址改成 `https://` 开头即可。

站点放在**反向代理、负载均衡或 CDN 后面**时，PHP 看到的是代理发来的请求：协议可能是 `http`，`Host` 可能是内网地址，客户端 IP 是代理的 IP。本页说明 Typecho 如何判断这些信息，以及对应的四组常量。常量都写在网站根目录的 `config.inc.php` 中，完整列表见[config.inc.php 与常量](./config-file.md)。

## Typecho 如何生成链接

先弄清这一点，后面的问题都容易理解。Typecho 里有两个“地址”：

| 地址 | 来源 | 用于 |
|---|---|---|
| **根地址** `rootUrl` | 每次请求时根据**当前请求**计算：协议（见下一节）+ `Host` 请求头 + 安装目录 | 文章、页面、分类等永久链接，订阅源，后台地址，登录与表单提交地址 |
| **站点地址** `siteUrl` | 后台“设置 → 基本”中保存的值（选项 `siteUrl`） | 主题和插件的静态文件（CSS、JS、图片）、附件地址、首页链接 |

也就是说，**永久链接跟着请求走，静态文件跟着设置走**。代理没有把真实的 `Host` 和协议传给 PHP 时，永久链接会变成内网地址或 `http://`；站点地址设置错误时，样式和图片会出问题。

另外，当前请求被判断为 HTTPS 时，Typecho 会自动把 `http://` 开头的站点地址换成 `https://`（`var/Widget/Options.php` 中的 `___siteUrl()`）。

## HTTPS 的判断与 `__TYPECHO_SECURE__` {#secure}

`var/Typecho/Request.php` 中的 `isSecure()` 满足下列任一条件即认为是 HTTPS：

| 条件 | 通常由谁设置 |
|---|---|
| 请求头 `X-Forwarded-Proto` 为 `https` 或 `quic` | 反向代理、负载均衡、CDN |
| 请求头 `X-Forwarded-Port` 为 `443` | 部分负载均衡 |
| 服务器变量 `HTTPS` 非空且不为 `off` | Web 服务器自己处理 TLS 时 |
| 服务器变量 `SERVER_PORT` 为 `443` | 同上 |
| 定义了 `__TYPECHO_SECURE__` 且为真 | 你在 `config.inc.php` 中设置 |

推荐让代理传 `X-Forwarded-Proto`。以 nginx 作为前端代理为例：

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $remote_addr;
}
```

代理无法添加请求头，而站点**只通过 HTTPS 访问**时，直接告诉 Typecho 总是 HTTPS：

```php
define('__TYPECHO_SECURE__', true);
```

:::caution
`__TYPECHO_SECURE__` 为真时，所有请求都被视为 HTTPS，包括通过 `http://` 访问的请求。站点仍可通过 HTTP 访问时不要这样设置。
:::

## 站点地址：`__TYPECHO_SITE_URL__` 与 `__TYPECHO_DYNAMIC_SITE_URL__` {#site-url}

| 常量 | 效果 |
|---|---|
| `__TYPECHO_SITE_URL__` | 用常量的值代替数据库中的站点地址。后台“基本设置”里的“站点地址”一项会隐藏，保存设置时也不再修改它 |
| `__TYPECHO_DYNAMIC_SITE_URL__` | 为真时，站点地址取当前请求的根地址，即用什么域名访问就用什么域名。同时定义了 `__TYPECHO_SITE_URL__` 时以后者为准 |

```php
// 固定站点地址，例如多台服务器共用一个数据库、或想防止在后台被改错
define('__TYPECHO_SITE_URL__', 'https://blog.example.com');

// 或者：同一站点绑定多个域名，各自使用访问时的域名
define('__TYPECHO_DYNAMIC_SITE_URL__', true);
```

再次提醒：这两个常量只影响**站点地址**，不影响永久链接。永久链接错误时请看下一节。

## 永久链接的域名和协议：`__TYPECHO_URL_PREFIX__` 与 `__TYPECHO_ROOT_URL__` {#root-url}

永久链接使用请求的根地址。最好的办法是让代理传入正确的 `Host` 与 `X-Forwarded-Proto`（见上文的 `proxy_set_header`）。做不到时，有两个常量可以覆盖：

| 常量 | 覆盖什么 | 示例 |
|---|---|---|
| `__TYPECHO_URL_PREFIX__` | 只覆盖“协议 + 域名”部分；安装目录仍按请求计算。Typecho 判断“当前请求的地址”时也用它 | `'https://blog.example.com'` |
| `__TYPECHO_ROOT_URL__` | 覆盖整个根地址（含安装目录）；1.3.0 中会让后台地址出错，见下文 | `'https://blog.example.com/blog'` |

**代理后面优先用 `__TYPECHO_URL_PREFIX__`**。原因是：打开文章时，Typecho 会比较“文章的永久链接”和“当前请求的地址”，域名或路径不一致就 301 跳转到永久链接（`var/Widget/Archive.php`）。

- 用 `__TYPECHO_URL_PREFIX__` 时，两边使用同一个前缀，不会多出跳转；
- 用 `__TYPECHO_ROOT_URL__` 而代理传给 PHP 的 `Host` 是另一个值时，每次打开文章都会 301 到 `__TYPECHO_ROOT_URL__` 的域名；这个跳转经过代理后又以原来的 `Host` 到达 PHP，于是形成**重定向循环**。例如根地址设为 `https://blog.example.com`，而 PHP 收到的 `Host` 是内网地址时，请求 `/archives/1/` 总是返回 301；改用 `__TYPECHO_URL_PREFIX__` 后正常返回 200。
- 在后台页面中，1.3.0 会从根地址末尾去掉后台目录（默认 `/admin`）的长度（`var/Widget/Options.php` 的 `___rootUrl()`），而 `__TYPECHO_ROOT_URL__` 本身不含后台目录，于是后台链接和表单地址被截断，例如 `https://blog.example.com` 变成 `https://blog.example.co`，后台无法正常使用。

`__TYPECHO_URL_PREFIX__` 不会让 Typecho 认为请求是 HTTPS，站点地址的 `http` → `https` 自动替换仍依赖上一节的判断。

## 客户端 IP：`__TYPECHO_IP_SOURCE__` {#ip-source}

评论记录的 IP、评论间隔限制、“同一 IP 已有垃圾引用通告则拒绝”等功能都使用客户端 IP。`getIp()`（`var/Typecho/Request.php`）的取值顺序是：

1. `__TYPECHO_IP_SOURCE__` 指定的请求头，未定义时为 `X-Forwarded-For`；
2. 请求头 `Client-Ip`；
3. 服务器变量 `REMOTE_ADDR`。

取到的值按逗号分隔后取**第一个**，并校验是否为合法 IP，不合法时记为 `unknown`。

| 场景 | 建议 |
|---|---|
| 在 nginx 等代理后面 | 让**最外层**代理用 `proxy_set_header X-Forwarded-For $remote_addr;` **覆盖**这个头，而不是用 `$proxy_add_x_forwarded_for` 追加，否则访客可以自己伪造第一个值 |
| 在 Cloudflare 后面 | `define('__TYPECHO_IP_SOURCE__', 'CF-Connecting-IP');` |
| 代理传的是 `X-Real-IP` | `define('__TYPECHO_IP_SOURCE__', 'X-Real-IP');` |
| 没有任何代理，访客直连 | 访客可以自己发送 `X-Forwarded-For` 或 `Client-Ip` 请求头，Typecho 会采信它。可以在 Web 服务器上丢弃这两个请求头，并把评论 IP 视为参考信息 |

常量的值写请求头的名字（如 `X-Real-IP`），Typecho 会自动转换成 `HTTP_X_REAL_IP` 去读取。

## Cookie：`__TYPECHO_COOKIE_OPTIONS__` {#cookie-options}

登录状态保存在 Cookie 中。Typecho 的 Cookie 有几个默认行为（`var/Typecho/Cookie.php`）：

- Cookie 名带有前缀，前缀是**根地址的 MD5**。根地址变了（例如协议从 `http` 变成 `https`，或换了域名），原来的登录 Cookie 就读不到了，需要重新登录；
- 域名（`domain`）和路径（`path`）取自根地址；
- 默认**不带** `Secure` 和 `HttpOnly` 属性。

`__TYPECHO_COOKIE_OPTIONS__` 可以修改域名并加上这两个属性。它是一个数组，**三个键都要写**（源码直接读取这三个键，缺少时 PHP 8 会给出 “Undefined array key” 警告）：

```php
define('__TYPECHO_COOKIE_OPTIONS__', [
    'domain'   => '',      // 留空表示使用根地址的域名；需要多个子域名共享时写 '.example.com'
    'secure'   => true,    // 只通过 HTTPS 发送，站点可以用 HTTP 访问时不要开启
    'httponly' => true,    // 禁止页面脚本读取
]);
```

1.3 不支持设置 `SameSite` 属性。

:::caution
开启 `httponly` 前，请确认你使用的主题和插件没有在前端 JavaScript 中读取 Typecho 的 Cookie。这一点无法从 Typecho 源码判断，请自行测试。
:::

## 常见问题

### 混合内容：页面是 HTTPS，样式或图片却是 HTTP {#mixed-content}

主题文件和附件使用站点地址。逐项检查：

1. 后台“设置 → 基本”中的站点地址是否为 `https://`。改成 `https://` 最直接；
2. 如果站点地址是 `http://`，Typecho 只在判断出当前请求是 HTTPS 时才会自动替换，检查代理是否传了 `X-Forwarded-Proto`，或定义 `__TYPECHO_SECURE__`；
3. 定义了 `__TYPECHO_UPLOAD_URL__`、`__TYPECHO_THEME_URL__`、`__TYPECHO_PLUGIN_URL__` 时，它们的值必须是 `https://`；
4. **文章正文里的图片地址**是插入附件那一刻的完整地址，保存在正文中，不会随设置变化。从 HTTP 切换到 HTTPS 后，旧文章中的 `http://` 图片需要批量替换，做法见[从其他博客迁移](./migration.md#replace-urls)。

### 重定向循环 {#redirect-loop}

| 现象 | 原因与处理 |
|---|---|
| 所有页面都循环跳转，浏览器提示“重定向次数过多” | 多半是代理或 CDN 与源站的 HTTPS 设置冲突：CDN 用 HTTP 回源，而源站又把 HTTP 跳转到 HTTPS。把 CDN 改为 HTTPS 回源，或在源站取消强制跳转、改由 CDN 负责 |
| 首页正常，打开文章时循环跳转 | 定义了 `__TYPECHO_ROOT_URL__`，但它的域名或路径与 PHP 收到的请求不一致，见[上文](#root-url)。改用 `__TYPECHO_URL_PREFIX__`，或让代理传正确的 `Host` |
| 代理把 `/blog/` 映射到源站的 `/` | 永久链接带 `/blog/`，PHP 收到的路径不带，文章页同样会循环跳转。让源站也安装在 `/blog/` 目录下，保持路径一致 |

### 登录后又回到登录页 {#login-loop}

1. **协议判断不一致**：Cookie 名取决于根地址。如果同一个访客的请求有时被判断为 HTTP、有时为 HTTPS（例如负载均衡只有部分节点传了 `X-Forwarded-Proto`），登录时写入的 Cookie 在下一个请求里就读不到。让所有请求都带上相同的头，或定义 `__TYPECHO_SECURE__`。
2. **开启了 `secure` 却用 HTTP 访问**：浏览器不会通过 HTTP 发送带 `Secure` 的 Cookie。
3. **`domain` 设置错误**：Cookie 域名与访问的域名不匹配时，浏览器会拒收。
4. **CDN 缓存了后台或登录请求**：在 CDN 上把后台目录（默认 `/admin/`）和 `/index.php/action/` 设为不缓存。

### CDN 缓存注意事项

- Typecho 对前台页面不做缓存控制。在 CDN 上缓存 HTML 时，评论提交后的页面、登录用户看到的页面也可能被缓存，请只缓存静态文件。只为带登录 Cookie 的请求绕过缓存是不够的：匿名访客提交评论后，Typecho 用一个名字以 `__typecho_unapproved_comment` 结尾的 Cookie 让评论者本人在页面上看到自己待审核的评论，这样的页面被缓存后，其他访客也会看到未审核的内容。
- 后台路径、`/index.php/action/`、`/action/` 不要缓存。
