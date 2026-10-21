---
title: Utils\Helper
sidebar_position: 8
---

# Utils\Helper

`\Utils\Helper` 是给插件用的一组静态方法：添加路由、Action、后台菜单和面板，读写选项，安排异步任务等。旧代码中的类名 `Helper` 仍然可用（见[旧类名对照表](./class-aliases.md)）。

## 路由

| 方法 | 说明 |
|---|---|
| `addRoute(string $name, string $url, string $widget, ?string $action = null, ?string $after = null): int` | 添加一条路由。`$name`：路由名；`$url`：路径规则（占位符见[默认路由与 Action](./routes-actions.md#placeholders)）；`$widget`：处理请求的类；`$action`：创建对象后调用的方法；`$after`：插在哪个路由之后，省略或找不到时放在最后。路由名不能与已有的路由重复 |
| `removeRoute(string $name): int` | 删除一条路由 |

两者的返回值是更新选项 `routingTable` 时受影响的行数。用法见[路由、Action 与后台面板](../plugin/routes-actions.md#路由)。

## Action

| 方法 | 说明 |
|---|---|
| `addAction(string $actionName, string $widgetName): int` | 让 `/action/$actionName` 由 `$widgetName` 处理，这个类必须实现 `\Widget\ActionInterface`。与核心 Action 同名时会替换核心的 Action |
| `removeAction(string $actionName): int` | 删除一个 Action |

返回值是更新选项 `actionTable` 时受影响的行数。

## 后台菜单与面板

| 方法 | 说明 |
|---|---|
| `addMenu(string $menuName): int` | 添加一个顶级菜单，返回它的菜单索引（从 `10` 开始），供 `addPanel()` 使用 |
| `removeMenu(string $menuName): int` | 删除一个顶级菜单，返回它原来的菜单索引。菜单不存在时返回 `10` |
| `addPanel(int $index, string $fileName, string $title, string $subTitle, string $level, bool $hidden = false, string $addLink = ''): int` | 在索引为 `$index` 的菜单下添加一个面板，返回它在该菜单中的位置。`$index`：`1` 控制台、`2` 撰写、`3` 管理、`4` 设置，或 `addMenu()` 的返回值；`$fileName`：`插件目录名/文件名`；`$title`：菜单文字；`$subTitle`：页面标题；`$level`：最低用户组；`$hidden`：不在菜单中显示；`$addLink`：标题后“新增”按钮的链接 |
| `removePanel(int $index, string $fileName): int` | 删除一个面板，返回它原来的位置，没找到时返回 `0` |
| `url(string $fileName): string` | 面板的完整地址，即 `后台地址/extending.php?panel=$fileName` |

用法见[路由、Action 与后台面板](../plugin/routes-actions.md#后台面板)。

## 选项与插件设置

| 方法 | 说明 |
|---|---|
| `options(): Options` | 返回 `\Widget\Options::alloc()`，见 [$this->options](./options.md) |
| `setOption(string $name, $value): int` | 修改选项 `$name` 的值，数组以 JSON 保存，同时更新当前请求中 `options()` 的值。**只修改已经存在的选项**：选项不存在时不会新建，返回 `0`；同名的个人选项（各用户的行）会被一起修改。返回受影响的行数 |
| `configPlugin($pluginName, array $settings, bool $isPersonal = false)` | 把 `$settings` 合并进插件设置 `plugin:插件名`（`$isPersonal` 为 `true` 时是所有用户的 `_plugin:插件名`），没有设置时新建；`$settings` 为空时什么也不做。见[插件配置](../plugin/settings.md) |
| `removePlugin(string $pluginName)` | 强制禁用一个插件：尽量调用它的 `deactivate()`（出错也继续），删除它注册的钩子和 `plugin:插件名` 设置。不删除个人设置 `_plugin:插件名` |

## 异步任务

| 方法 | 说明 |
|---|---|
| `requestService($method, ...$params)` | 安排一个异步任务。当前请求以跳转或直接输出结束后，Typecho 会向自己的 `/action/service` 发起一个请求，在其中触发 `Widget_Service` 上名为 `$method` 的钩子，并传入 `$params`（经过 JSON 编码）。见 [HTTP 请求与异步任务](../plugin/http-async.md) |

## 其他

| 方法 | 说明 |
|---|---|
| `security(): Security` | 返回 `\Widget\Security::alloc()`，用来生成和校验带令牌的地址 |
| `widgetById(string $table, int $pkId): ?Widget` | 按主键取一行数据，返回对应的基础 Widget：`contents` → `\Widget\Base\Contents`、`comments` → `\Widget\Base\Comments`、`metas` → `\Widget\Base\Metas`、`users` → `\Widget\Base\Users`。其他表名返回 `null`；没有这一行时返回一个空的 Widget（`have()` 为 `false`） |
| `lang(string $domain)` | 追加语言文件。它在 `var/Utils/` 下查找文件，不适合主题和插件使用，见[主题与插件国际化](../i18n.md) |
| `replyLink(string $theId, int $coid, string $word = 'Reply', string $formId = 'respond', int $style = 2)` | 输出旧式的评论“回复”链接，只在开启了评论嵌套时输出。新主题请用 `$comments->reply()`，见[评论模板](../theme/comments.md) |
| `cancelCommentReplyLink(string $word = 'Cancel', string $formId = 'respond')` | 输出旧式的“取消回复”链接，只在开启了评论嵌套时输出 |
| `threadedCommentsScript()` | 输出上面两个链接需要的脚本，只在开启了评论嵌套时输出 |

例如在插件中读取一篇文章：

```php
$post = \Utils\Helper::widgetById('contents', 1);
if ($post->have()) {
    echo $post->title;
}
```
