---
title: Utils\Helper
sidebar_position: 8
translation: machine
source_commit: c4fe96854858d8912390c7dfe6c2eab21c87ac5c
---

# Utils\Helper

`\Utils\Helper` is a set of static methods for plugins: adding routes, actions, admin menus and panels, reading and writing options, scheduling async tasks and so on. The class name `Helper` used in older code still works (see [Legacy class name mapping](./class-aliases.md)).

## Routes

| Method | Description |
|---|---|
| `addRoute(string $name, string $url, string $widget, ?string $action = null, ?string $after = null): int` | Add a route. `$name` is the route name; `$url` is the path rule, for placeholders see [Default routes and actions](./routes-actions.md#placeholders); `$widget` is the class that handles the request; `$action` is the method called after the object is created; `$after` is the route to insert after; if omitted or not found, the route goes last. The route name must not duplicate an existing route |
| `removeRoute(string $name): int` | Remove a route |

Both return the number of rows affected when updating the `routingTable` option. For usage, see [Routes, actions and admin panels](../plugin/routes-actions.md#路由).

## Action

| Method | Description |
|---|---|
| `addAction(string $actionName, string $widgetName): int` | Make `/action/$actionName` handled by `$widgetName`; this class must implement `\Widget\ActionInterface`. If the name matches a core action, it replaces the core action |
| `removeAction(string $actionName): int` | Remove an action |

The return value is the number of rows affected when updating the `actionTable` option.

## Admin menus and panels

| Method | Description |
|---|---|
| `addMenu(string $menuName): int` | Add a top-level menu and return its menu index (starting at `10`), for use with `addPanel()` |
| `removeMenu(string $menuName): int` | Remove a top-level menu and return its former menu index. Returns `10` if the menu does not exist |
| `addPanel(int $index, string $fileName, string $title, string $subTitle, string $level, bool $hidden = false, string $addLink = ''): int` | Add a panel under the menu with index `$index` and return its position in that menu. `$index`: `1` Dashboard, `2` Write, `3` Manage, `4` Settings, or the return value of `addMenu()`; `$fileName`: `<plugin directory name>/<file name>`; `$title`: menu text; `$subTitle`: page title; `$level`: minimum user group; `$hidden`: do not show in the menu; `$addLink`: link of the "Add" button after the title |
| `removePanel(int $index, string $fileName): int` | Remove a panel and return its former position; returns `0` if not found |
| `url(string $fileName): string` | Full URL of the panel, i.e. `<admin URL>/extending.php?panel=$fileName` |

For usage, see [Routes, actions and admin panels](../plugin/routes-actions.md#后台面板).

## Options and plugin settings

| Method | Description |
|---|---|
| `options(): Options` | Returns `\Widget\Options::alloc()`; see [$this->options](./options.md) |
| `setOption(string $name, $value): int` | Change the value of option `$name`; arrays are saved as JSON, and the value of `options()` in the current request is updated too. **It only changes existing options**: if the option does not exist, it is not created and `0` is returned; personal options of the same name (each user's row) are changed as well. Returns the number of affected rows |
| `configPlugin($pluginName, array $settings, bool $isPersonal = false)` | Merge `$settings` into the plugin settings `plugin:<plugin name>` (or, when `$isPersonal` is `true`, into every user's `_plugin:<plugin name>`), creating them if there are none; does nothing when `$settings` is empty. See [Plugin settings](../plugin/settings.md) |
| `removePlugin(string $pluginName)` | Force-disable a plugin: tries to call its `deactivate()` (continuing even if it fails), and deletes the hooks it registered and its `plugin:<plugin name>` settings. It does not delete the personal settings `_plugin:<plugin name>` |

## Async tasks

| Method | Description |
|---|---|
| `requestService($method, ...$params)` | Schedule an async task: after the current request ends with a redirect or direct output, Typecho requests its own `/action/service`, and in that request triggers the hook named `$method` on `Widget_Service` with `$params` as arguments (JSON-encoded). See [HTTP requests and async tasks](../plugin/http-async.md) |

## Other

| Method | Description |
|---|---|
| `security(): Security` | Returns `\Widget\Security::alloc()`, used to generate and verify URLs with tokens |
| `widgetById(string $table, int $pkId): ?Widget` | Fetch one row by primary key and return the corresponding base widget: `contents` → `\Widget\Base\Contents`, `comments` → `\Widget\Base\Comments`, `metas` → `\Widget\Base\Metas`, `users` → `\Widget\Base\Users`. Other table names return `null`; if the row does not exist, an empty widget is returned (`have()` is `false`) |
| `lang(string $domain)` | Load an additional language file. It looks for the file under `var/Utils/`, so it is not suitable for themes and plugins; see [Theme and plugin internationalization](../i18n.md) |
| `replyLink(string $theId, int $coid, string $word = 'Reply', string $formId = 'respond', int $style = 2)` | Output an old-style comment "Reply" link; it outputs only when threaded comments are enabled. New themes should use `$comments->reply()`; see [Comment template](../theme/comments.md) |
| `cancelCommentReplyLink(string $word = 'Cancel', string $formId = 'respond')` | Output an old-style "Cancel reply" link; it outputs only when threaded comments are enabled |
| `threadedCommentsScript()` | Output the script needed by the two links above; it outputs only when threaded comments are enabled |

For example, to read a post in a plugin:

```php
$post = \Utils\Helper::widgetById('contents', 1);
if ($post->have()) {
    echo $post->title;
}
```
