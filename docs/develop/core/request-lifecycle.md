---
title: 请求生命周期
sidebar_position: 2
---

# 请求生命周期

本页按执行顺序说明一个前台请求从进入 `index.php` 到输出响应经过了哪些步骤，以及后台页面为什么走的是另一条路径。

## 总览

```text
index.php
 ├─ include config.inc.php
 │   ├─ require var/Typecho/Common.php   注册自动加载器
 │   ├─ \Typecho\Common::init()          输出缓冲 + 基础异常处理
 │   └─ new \Typecho\Db(...); Db::set()  数据库对象（尚未连接）
 ├─ \Widget\Init::alloc()                9 步初始化
 ├─ Plugin::factory('index.php')->call('begin')
 ├─ \Typecho\Router::dispatch()          匹配路由，执行 Widget
 └─ Plugin::factory('index.php')->call('end')
```

## 1. 载入配置文件

`index.php` 第一件事是 `include_once 'config.inc.php'`。文件不存在时，如果 `install.php` 还在，就跳转到安装程序，否则输出 `Missing Config File` 并退出。

配置文件依次做三件事：

1. `require_once` 载入 `var/Typecho/Common.php`。这个文件被载入时就注册了自动加载器，并定义了全局函数 `_t()`、`_e()`、`_n()`；
2. 调用 `\Typecho\Common::init()`；
3. 创建 `\Typecho\Db` 对象、添加服务器并 `Db::set($db)`。此时还没有连接数据库，第一次查询时才连接。

## 2. Common::init()

`Common::init()`（`var/Typecho/Common.php`）只做准备工作：

- 关闭 `Response` 的自动发送头信息，改为用 `ob_start()` 开启输出缓冲，在缓冲区第一次输出时由回调统一调用 `Response::sendHeaders()`。因此在页面输出开始后，Widget 仍然可以设置状态码、头信息和 Cookie；
- 设置一个最基础的异常处理函数：输出异常信息和调用栈。它只在下一步的友好错误页没有接管时起作用（例如开启了调试模式，或者异常发生在 `Widget\Init` 之前）。

## 3. Widget\Init 的 9 步

`\Widget\Init::alloc()` 创建 `Widget\Init` 并执行它的 `execute()`（`var/Widget/Init.php`），按顺序：

| 步骤 | 做什么 | 说明 |
|---|---|---|
| 1 | 设置友好的异常处理函数 | 只在没有定义 `__TYPECHO_DEBUG__` 或其值为假时设置。代码为 404 的异常交给 `Widget\ExceptionHandle`，它以 404 类型渲染当前主题（主题有 `404.php` 时使用它）；其他异常交给 `Common::error()`，显示错误页 |
| 2 | 定义 `__TYPECHO_CLASS_ALIASES__` | 旧类名别名表，见[旧类名对照表](../reference/class-aliases.md) |
| 3 | `Widget\Options::alloc()` | 第一次查询数据库，读出全部站点选项 |
| 4 | 载入语言包 | `lang` 选项不为空且不是 `zh_CN` 时，读取 `usr/langs/<lang>.mo`（或 `__TYPECHO_LANG_DIR__` 指定的目录） |
| 5 | 备份目录 | 没有定义 `__TYPECHO_BACKUP_DIR__` 时定义为 `usr/backups` |
| 6 | Cookie | 以站点根地址设置 Cookie 前缀；定义了 `__TYPECHO_COOKIE_OPTIONS__` 时设置 Cookie 选项 |
| 7 | 路由与插件 | `Router::setRoutes($options->routingTable)` 载入路由表；`Plugin::init($options->plugins)` 载入已启用插件注册的钩子 |
| 8 | 响应与时区 | 设置响应的字符集与 `Content-Type`，设置时区偏移 |
| 9 | 会话 | 站点已安装且当前用户已登录时调用 `session_start()` |

几点说明：

- 第 7 步之后插件的钩子才生效，所以插件无法介入前 6 步；
- 第 9 步对**所有**已登录用户的请求都会开启会话，包括访问前台页面；未登录的访客不会开启会话；
- 常量的用法见[配置文件](../../guide/operations/config-file.md)。

## 4. index.php:begin

```php
\Typecho\Plugin::factory('index.php')->call('begin');
```

在路由分发之前执行，没有参数。此时选项、语言、插件都已就绪，但还不知道请求会落到哪条路由上。

## 5. Router::dispatch() {#dispatch}

`Router::dispatch()`（`var/Typecho/Router.php`）：

1. 取得请求的路径（`Request::getPathInfo()`，即站点根地址之后、`?` 之前的部分，不含 `/index.php`）；
2. 按路由表的顺序，用每条路由编译好的正则匹配路径；
3. 对第一条匹配的路由，把正则捕获的占位符组成参数数组，调用 `Widget::widget($route['widget'], null, $params)` 创建处理类。这些参数成为该 Widget 的请求参数，优先于同名的 GET/POST 参数；
4. 如果路由定义了 `action`，再调用 `$widget->{action}()`，例如 `Widget\Archive` 的 `render()`；
5. 如果创建 Widget 或执行 `action` 时抛出代码为 404 的异常，就丢弃它，继续尝试**后面**匹配的路由；
6. 所有匹配的路由都失败或没有路由匹配时，抛出代码为 404 的 `Typecho\Router\Exception`，由第 3 节第 1 步的处理函数显示 404 页面。

以 `/archives/999/`（不存在的文章）为例，依次发生：

```text
pathInfo = /archives/999/
  匹配 post       → Widget\Archive，cid=999 → 404，继续
  匹配 feedback   → Widget\Feedback，permalink=/archives，type=999 → 404，继续
  没有更多匹配    → 抛出 404
```

路由表的结构、规则语法和反向生成地址见[路由](./router.md)。

## 6. index.php:end

```php
\Typecho\Plugin::factory('index.php')->call('end');
```

只有 `dispatch()` 正常返回时才会执行。以下情况都**不会**执行 `end`：

- 显示 404 页面或错误页（异常处理函数最后调用了 `exit`）；
- Widget 调用了 `redirect()`、`goBack()`、`throwJson()` 等响应方法，这些方法输出后直接 `exit`，见 [Request、Response 与常用工具](./request-response.md#response)。大多数 `/action/*` 请求以这类方法结束，因此不执行 `end`；但 Action 正常返回时（例如用 GET 请求 `/action/upload`，只设置 403 状态后返回），`end` 照常执行。

如果需要在每个请求结束时执行某些操作，不能只依赖 `index.php:end`。

## 7. 输出

页面内容在执行过程中就被 `echo` 到输出缓冲区。脚本结束（或代码主动刷新缓冲区）时，回调先发送状态码、头信息和 Cookie，再输出内容。

## 后台页面不经过路由

后台的每个页面都是 `admin/` 下一个独立的 PHP 文件，由 Web 服务器直接执行，请求根本不经过 `index.php`，所以：

- 没有路由匹配，也不会触发 `index.php:begin` 和 `index.php:end`；
- 后台页面在第一行 `include 'common.php'`，由 `admin/common.php` 完成初始化。

`admin/common.php` 的步骤：

1. 定义常量 `__TYPECHO_ADMIN__`（`User::pass()` 用它决定未登录时跳转到登录页还是首页）；
2. 载入 `config.inc.php`，执行 `\Widget\Init::alloc()`，过程与前台相同；
3. 触发钩子 `admin/common.php:begin`；
4. 创建 `Widget\Options`、`Widget\User`、`Widget\Security`、`Widget\Menu`，赋给 `$options`、`$user`、`$security`、`$menu`，并准备 `$request`、`$response`；
5. `Widget\Menu` 在创建时找出当前页面对应的菜单项，并用该菜单项要求的角色调用 `$user->pass()`：未登录时跳转到登录页，权限不足时显示 403，见[权限模型](./permissions.md)；
6. 首次登录的用户被引导到 `welcome.php`；管理员在程序版本高于数据库中记录的版本时被引导到 `upgrade.php`，见[编写升级脚本](./upgrade-scripts.md)。

之后页面模板自己决定创建哪些 Widget、输出什么。后台的表单提交则发往 `/action/<名字>`，这部分又回到了 `index.php` 和路由（路由 `do`）。

## 自己观察一次请求

在 `index.php:begin` 和 `index.php:end` 上注册回调，可以看到一个请求有没有走完全程：

```php title="usr/plugins/RequestTrace/Plugin.php"
<?php

namespace TypechoPlugin\RequestTrace;

use Typecho\Plugin\PluginInterface;
use Typecho\Router;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 在 PHP 错误日志中记录请求经过的阶段
 *
 * @package RequestTrace
 * @version 1.0.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        \Typecho\Plugin::factory('index.php')->begin = __CLASS__ . '::begin';
        \Typecho\Plugin::factory('index.php')->end = __CLASS__ . '::end';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function begin()
    {
        error_log('begin ' . ($_SERVER['REQUEST_URI'] ?? ''));
    }

    public static function end()
    {
        error_log('end, route = ' . Router::$current);
    }
}
```

启用后访问首页、一篇文章、一个不存在的地址，再在后台点击“登出”（`/action/logout`，以跳转结束），日志中只有前两个请求同时出现 `begin` 和 `end`。`Router::$current` 是最后一次匹配到的路由名。
