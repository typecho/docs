---
title: $this->options
sidebar_position: 5
---

# $this->options

`$this->options` 是 `Widget\Options` 对象，装着站点的全部设置。模板中直接用 `$this->options`；在 `functions.php` 或插件中用 `\Widget\Options::alloc()` 取得同一个对象。

## 值从哪里来

`$this->options->xxx` 依次合并了三处的值：

1. 数据库 `options` 表中 `user = 0` 的全站设置，名字和默认值见[选项名一览](./option-names.md)；
2. 当前主题的设置（`theme:主题目录名` 中保存的 JSON），**同名时覆盖全站设置**，见[主题设置](../theme/theme-options.md)；
3. 当前登录用户的个人设置（`user = 用户 ID` 的行，例如 `markdown`、`autoSave`、`defaultAllowComment`），只在用户登录时合并，覆盖前两者。

此外还有下表中的魔术属性，它们由 Typecho 计算得出，例如各种地址。

## 读取与输出

```php
<?php echo $this->options->title; ?>   <!-- 取值 -->
<?php $this->options->title(); ?>      <!-- 直接输出，同上 -->
<?php $this->options->siteUrl('archives/'); ?>   <!-- 带参数的方法 -->
```

对任何选项名调用同名方法都会直接输出它的值（`Typecho\Config` 的行为，不做 HTML 转义）。下表中的方法有自己的参数，行为以表中为准。

## 方法

| 方法 | 说明 |
|---|---|
| `adminStaticUrl(string $type, ?string $file = null, bool $return = false)` | 获取或输出后台静态文件路径 |
| `adminUrl(?string $path = null, bool $return = false)` | 输出后台路径 |
| `commentsHTMLTagAllowed()` | 编码输出允许出现在评论中的html标签 |
| `execute()` | 执行函数 |
| `index(?string $path = null)` | 输出解析地址 |
| `personalPlugin($pluginName)` | 获取个人插件系统参数 |
| `plugin($pluginName)` | 获取插件系统参数 |
| `pluginDir(?string $plugin = null): string` | 获取插件目录 |
| `pluginUrl(?string $path = null)` | 输出插件路径 |
| `siteUrl(?string $path = null)` | 输出网站路径 |
| `themeFile(string $theme, string $file = ''): string` | 获取皮肤文件 |
| `themeUrl(?string $path = null, ?string $theme = null)` | 输出模板路径 |

- `siteUrl($path)`、`index($path)`、`themeUrl($path)`、`pluginUrl($path)` **直接输出**拼接好的地址；`themeUrl($path, $theme)` 给出第二个参数时改为**返回**另一个主题目录中的文件地址。
- `index($path)` 与 `siteUrl($path)` 的区别：没有开启地址重写时，`index` 带 `index.php`（`https://example.com/index.php/archives/1/`），生成指向页面的链接应该用它；`siteUrl` 总是站点根地址，适合静态文件。
- `adminUrl($path, $return = false)`、`adminStaticUrl($type, $file, $return = false)` 的最后一个参数为 `true` 时返回、否则输出。
- `plugin($pluginName)` 返回插件的全站设置（`Typecho\Config`），例如 `$this->options->plugin('HelloWorld')->word`；插件**没有启用或没有设置项**时抛出 `Typecho\Plugin\Exception`（“插件 X 的配置信息没有找到”），读取前可以用 `try` 包裹。`personalPlugin($pluginName)` 返回当前用户的插件个人设置。
- `themeFile($theme, $file)`、`pluginDir($plugin)` 返回服务器上的绝对路径。

## 魔术属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `actionTable` | `array` | 已注册的 Action（插件通过 `Helper::addAction()` 添加） |
| `adminUrl` | `string` | 获取后台路径 |
| `allowedAttachmentTypes` | `array` | 允许上传的文件类型 |
| `commentsFeedAtomUrl` | `string` | 评论ATOM1.0聚合 |
| `commentsFeedRssUrl` | `string` | 评论RSS1.0聚合 |
| `commentsFeedUrl` | `string` | 评论RSS2.0聚合 |
| `contentType` | `string` | 获取格式 |
| `feedAtomUrl` | `string` | ATOM1.O |
| `feedRssUrl` | `string` | RSS1.0 |
| `feedUrl` | `string` | RSS2.0 |
| `gmtTime` | `int` | 获取GMT标准时间 |
| `index` | `string` | 获取解析路径前缀 |
| `loginAction` | `string` | 获取登录提交地址 |
| `loginUrl` | `string` | 获取登录地址 |
| `logoutUrl` | `string` | 获取登出地址 |
| `missingTheme` | `?string` | 动态判断皮肤目录 |
| `originalSiteUrl` | `string` | 实际使用的站点地址：设置中保存的站点地址；定义了 `__TYPECHO_SITE_URL__` 时为该常量，开启了 `__TYPECHO_DYNAMIC_SITE_URL__` 时为 `rootUrl`。`siteUrl` 由它规范化得到，并在 HTTPS 请求中把 `http://` 换成 `https://` |
| `panelTable` | `array` | 插件注册的后台面板 |
| `pluginDir` | `string` | 插件目录的绝对路径 |
| `plugins` | `array` | 已启用插件及其注册的钩子 |
| `pluginUrl` | `string` | 获取插件路径 |
| `profileUrl` | `string` | 获取个人档案地址 |
| `registerAction` | `string` | 注册表单的提交地址，带安全令牌 |
| `registerUrl` | `string` | 获取注册地址 |
| `rootUrl` | `string` | 动态获取根目录 |
| `routingTable` | `array` | 解析后的路由表 |
| `serverTimezone` | `int` | 获取系统时区 |
| `siteDomain` | `string` | 站点地址中的主机名 |
| `siteUrl` | `string` | 站点地址（带末尾 `/`） |
| `software` | `string` | 软件名称 |
| `theme` | `string` | 当前主题目录名；目录不存在时为 `default` |
| `themeUrl` | `string` | 获取模板路径 |
| `time` | `int` | 获取时间 |
| `version` | `string` | 软件版本 |
| `xmlRpcUrl` | `string` | xmlrpc api地址 |

常用的地址类属性（开启地址重写时的典型值）：

| 属性 | 值示例 |
|---|---|
| `siteUrl` | `https://example.com/` |
| `index` | `https://example.com`（未开启重写时为 `https://example.com/index.php`） |
| `themeUrl` | `https://example.com/usr/themes/starter` |
| `adminUrl` | `https://example.com/admin/` |
| `feedUrl` / `feedRssUrl` / `feedAtomUrl` | `https://example.com/feed/`、`…/feed/rss/`、`…/feed/atom/` |
| `commentsFeedUrl` | `https://example.com/feed/comments/` |
| `xmlRpcUrl` | `https://example.com/action/xmlrpc` |
| `loginUrl` / `registerUrl` / `profileUrl` | `https://example.com/admin/login.php` 等 |
| `logoutUrl` | 带安全令牌的登出地址 |

其他：`time` 为当前时间戳，`timezone` 为站点时区与 GMT 相差的秒数，`version` 为 Typecho 版本号（如 `1.3.0`），`software` 为 `Typecho`，`theme` 为当前主题目录名（目录不存在时为 `default`，`missingTheme` 为丢失的那个目录名）。

数据库中的普通选项（`title`、`description`、`pageSize`、`commentsThreaded` 等）也都可以这样读取，完整列表见[选项名一览](./option-names.md)。`plugins`、`routingTable`、`actionTable`、`panelTable` 读到的是解码后的数组。
