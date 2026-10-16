---
title: The Widget Component Model
sidebar_position: 4
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# The Widget Component Model

In Typecho, pages, data lists and admin operations are all widgets. This page explains how `Typecho\Widget` (`var/Typecho/Widget.php`) creates and reuses instances, how it stores and iterates over data rows, the rules for magic properties and magic methods, and `Widget\Base` with its interfaces and traits. For how to use widgets in templates, see [Calling widgets in templates](/develop/theme/widgets).

## What a widget is

A widget is a class that extends `Typecho\Widget`. It plays two roles at once:

- **Controller**: when created, it runs `execute()`, which reads request parameters, queries the database or processes a form;
- **Data set**: it pushes query results row by row onto an internal stack (`$stack`); templates iterate over the rows with `next()` and read fields of the current row (`$row`) through properties such as `$this->title`.

```php
// 取最近 5 篇文章并输出标题
\Widget\Contents\Post\Recent::alloc('pageSize=5')->to($posts);

while ($posts->next()) {
    echo $posts->title;   // 当前行的 title
}
```

## Creating instances

### widget()

```php
public static function widget(
    string $alias,
    $params = null,
    $request = null,
    $disableSandboxOrCallback = true
): Widget
```

| Parameter | Meaning |
|---|---|
| `$alias` | The class name, optionally with an `@alias` suffix; both old and new forms work: `'\Widget\Contents\Post\Recent'`, `'Widget_Contents_Post_Recent'`, `'\Widget\Contents\Post\Recent@five'` |
| `$params` | The widget's own parameters, as a query string (`'pageSize=5&type=post'`) or an array; read in the widget through `$this->parameter` |
| `$request` | Additional request parameters, as a query string or an array. When the widget reads them with `$this->request->get()`, they take precedence over GET and POST parameters of the same name. This is the parameter the router uses to pass placeholder values to the handler class |
| `$disableSandboxOrCallback` | Defaults to `true`. Passing `false` or a callback creates the widget in a sandbox; see below |

The creation process: `new` the object (the constructor calls `init()`) → call `execute()` → put it into the instance pool → return it.

### alloc() and allocWithAlias()

In everyday code these two static methods are more common. They are just shorthand for `widget()`:

```php
\Widget\Contents\Post\Recent::alloc($params, $request);
// 等于 Widget::widget(\Widget\Contents\Post\Recent::class, $params, $request)

\Widget\Contents\Post\Recent::allocWithAlias('five', $params, $request);
// 等于 Widget::widget(\Widget\Contents\Post\Recent::class . '@five', $params, $request)
```

### The instance pool

Within one request, a widget is created only once per "class name + alias". The key in the instance pool is the class name with `\` replaced by `_`, for example `Widget_Contents_Post_Recent` and `Widget_Contents_Post_Recent@five`.

When you create a widget with the same key a second time, the instance in the pool is returned directly; **the newly passed parameters are ignored** and `execute()` does not run again:

```php
$a = \Widget\Contents\Post\Recent::alloc('pageSize=1');
$b = \Widget\Contents\Post\Recent::alloc('pageSize=5');   // 返回 $a，pageSize 仍为 1

$c = \Widget\Contents\Post\Recent::allocWithAlias('five', 'pageSize=5');   // 新实例
```

So when one page needs two different sets of data from the same class, use `allocWithAlias()` to give one of them an alias. Objects that only need one copy per request, such as `Widget\Options`, `Widget\User` and `Widget\Security`, are shared precisely through the instance pool.

Removing instances from the pool:

```php
\Typecho\Widget::destroy('Widget_Contents_Post_Recent');        // 移除一个，键要用下划线写法
\Typecho\Widget::destroy('Widget_Contents_Post_Recent@five');
\Typecho\Widget::destroy();                                     // 清空整个实例池
```

In 1.3.0, calling `destroy()` on a subclass (such as `\Widget\Contents\Post\Recent::destroy()`), or passing a class name written with backslashes to `Typecho\Widget::destroy()`, does not remove the instance, because the key they build does not match the underscore key in the pool. To remove an instance, call `Typecho\Widget::destroy()` as shown above. The old name `destory()` is a deprecated alias of `destroy()`.

### Sandbox {#sandbox}

When the fourth argument is `false` or a callback, the widget is created in a sandbox:

- A new instance is created every time and is **not put** into the instance pool;
- The request object contains only the values passed in the `$request` argument and cannot see the GET and POST of the current request;
- The response object is in sandbox mode: calling methods such as `redirect()` or `throwJson()` in the widget neither outputs anything nor ends the script; it only aborts this widget's execution;
- When a callback is passed, it is called with the widget as its argument after the widget has run, and only then is the sandbox exited.

```php
// 在沙箱中取 cid 为 1 的文章，不受当前页面请求参数的影响
\Widget\Archive::alloc('type=post', 'cid=1', function ($post) {
    echo $post->title;
});
```

## Data rows

| Member | Purpose |
|---|---|
| `push(array $row)` | Pushes a row and makes it the current row; `length` increases by one |
| `pushAll(array $rows)` | Pushes several rows in turn (new in 1.3) |
| `next()` | Makes the next row the current row and returns it; `sequence` increases by one. At the end it returns `false` and resets the pointer and `sequence`, so you can iterate again |
| `have()` | Returns `true` when the stack is not empty |
| `to(&$var)` | Assigns the widget itself to the variable and returns the widget, for chaining in templates |
| `length` | Number of rows |
| `sequence` | Sequence number of the current row, starting at 1 |
| `alt(...$args)` | Outputs the arguments in rotation according to `sequence`, for example `$posts->alt('odd', 'even')` |
| `altBy(int $current, ...$args)` | Outputs in rotation according to a given sequence number (new in 1.3) |
| `parse(string $template)` | Iterates over all rows and outputs each row using the template |
| `template(string $template)` | Replaces `{field}` in the template with the current row's values and returns the result (new in 1.3) |
| `toColumn($column)` | Gets one or more fields of the current row (new in 1.3) |
| `toArray($column)` | Iterates over all rows and returns an array of one or more fields (new in 1.3) |
| `on(bool $condition)` | Returns the widget itself when the condition is true, otherwise an empty object that swallows any method call; used in code such as `$this->on($this->request->isPost())->save()` |

Note that after `push()` the current row is the last row pushed and `sequence` is still 0; do not read the current row before you start iterating.

```php
$posts->parse('<li><a href="{permalink}">{title}</a></li>');

$titles = $posts->toArray('title');                 // ['标题 1', '标题 2', ...]
$pairs  = $posts->toArray(['cid', 'title']);        // [['cid' => 1, 'title' => '...'], ...]
```

`Widget\Base\Contents`, `Comments`, `Metas` and `Users` override `push()`: before pushing, they call `filter()` to fill in derived fields (and trigger the `filter` hook of the corresponding handle).

## Magic properties: ___prop

When you read `$widget->name`, `__get()` looks in this order:

1. The cached `#name` in the current row;
2. The widget's `___name()` method (three underscores). The result is cached in `#name` of the current row, and later reads on the same row do not call it again;
3. The `name` field of the current row;
4. A value provided by a plugin through the hook `___name` (see [Dynamic hooks](/develop/plugin/hooks#动态钩子));
5. If none of these exists, `null` is returned.

The cache lives on the current row; after `next()` moves to the next row, `___name()` is computed again for the new row.

When you write `$widget->name = $value`, if there is a `___name()` method (or a cached value already exists), the value is written to the cache `#name`, overriding the computed result; otherwise it is written to the `name` field of the current row. `isset($widget->name)` returns `true` for all three sources.

`Typecho\Widget` has its own `___sequence()`, `___length()`, `___request()`, `___response()` and `___parameter()`, which is why `$this->request` and `$this->parameter` can also be read in templates.

## Magic methods: __call

When you call a method that does not exist, `$widget->name(...)`:

1. The hook `call<Name>` (such as `callTitle`) is triggered first; plugins can use it to add methods to widgets;
2. If no plugin handles it, `echo $widget->name` is done.

This is why `$this->title()` in a template outputs the title directly: `Widget\Archive` has no `title()` method, and `__call` outputs the `title` property.

## Widget\Base

Application widgets extend the abstract class `Widget\Base` (`var/Widget/Base.php`), which prepares common dependencies in `init()`:

| Property | Object |
|---|---|
| `$this->db` | `Typecho\Db::get()` |
| `$this->options` | `Widget\Options::alloc()` |
| `$this->user` | `Widget\User::alloc()` |
| `$this->security` | `Widget\Security::alloc()` |

Subclasses can override `initComponents(int &$components)` to prepare fewer of them. The argument is a bitwise combination of `INIT_DB`, `INIT_USER`, `INIT_SECURITY` and `INIT_OPTIONS`, defaulting to `INIT_ALL`; `INIT_NONE` means none of them, in which case not even `$this->db` is set. As long as it is not `INIT_NONE`, `$this->db` is always set.

After that, `init()` calls `initParameter(Config $parameter)`, where subclasses can set default values on `$this->parameter` or prepare things based on the parameters. `Widget\Options` uses both methods, because it cannot depend on itself while it is being created:

```php title="var/Widget/Options.php"
protected function initComponents(int &$components)
{
    $components = self::INIT_NONE;
}

protected function initParameter(Config $parameter)
{
    if (!$parameter->isEmpty()) {
        $this->row = $this->parameter->toArray();
    } else {
        $this->db = Db::get();
    }
}
```

## Base classes and interfaces

The base classes for data rows are in `var/Widget/Base/`:

| Class | Table |
|---|---|
| `Widget\Base\Contents` | `contents` (posts, pages, attachments, drafts, revisions) |
| `Widget\Base\Comments` | `comments` |
| `Widget\Base\Metas` | `metas` (categories, tags) |
| `Widget\Base\Users` | `users` |
| `Widget\Base\Options` | `options` |

The interfaces they implement:

| Interface | Methods | Implemented by | Purpose |
|---|---|---|---|
| `Widget\Base\QueryInterface` | `select(...$fields)`, `size(Query)`, `insert(array)`, `update(array, Query)`, `delete(Query)` | All five | Standard queries, inserts, updates and deletes on the class's own table; `select()` returns a query object that has already done `from` on that table |
| `Widget\Base\RowFilterInterface` | `filter(array $row): array` | Contents, Comments, Metas, Users | New in 1.3. Fills in derived fields before `push()` |
| `Widget\Base\PrimaryKeyInterface` | `getPrimaryKey(): string` | Contents, Comments, Metas, Users | New in 1.3. Returns the primary key name (`cid`, `coid`, `mid`, `uid`), used by the tree trait |
| `Typecho\Router\ParamsDelegateInterface` | `getRouterParam(string $key): string` | Contents, Comments, Metas, Users | New in 1.3. Supplies parameters to `Router::url()`; see [Routing](./router.md#params-delegate) |
| `Widget\ActionInterface` | `action()` | Every widget that can be called through `/action/<name>` | `Widget\Action` only dispatches to classes that implement it |

Names in old code such as `Widget_Abstract_Contents` are aliases of these base classes; see the [legacy class name table](/develop/reference/class-aliases).

## Traits added in 1.3

1.3 splits logic shared by several widgets into traits:

| Trait | Used by | Provides |
|---|---|---|
| `Widget\Base\TreeTrait` | `Contents\Page\Admin`, `Contents\From`, `Metas\Category\Admin`, `Metas\Category\Related`, `Metas\From`, and the two `Rows` classes that use it indirectly through `TreeViewTrait` | Parent-child trees: `getAllParents()`, `getAllChildIds()`, `getChildIds()`, `getRows()`, `getRow()`, `hasParent()`, `levelsAlt()`, and the magic properties `directory` and `children`; users must implement `initTreeRows()` |
| `Widget\Base\TreeViewTrait` | `Contents\Page\Rows`, `Metas\Category\Rows` | Outputs lists as a tree; the implementation of `listPages()` and `listCategories()` |
| `Widget\Metas\Category\InitTreeRowsTrait` | The four category-related widgets | Reads all categories for `TreeTrait` |
| `Widget\Contents\EditTrait` | `Contents\Post\Edit`, `Contents\Page\Edit` | Editing logic such as reading and writing custom fields, linking categories and tags, publishing and saving drafts |
| `Widget\Contents\PrepareEditTrait` | `Contents\Post\Edit`, `Contents\Page\Edit`, `Contents\Attachment\Edit` | Loads the content to edit (including drafts), the admin menu title, and the `allow()` permission check |
| `Widget\Contents\AdminTrait` | `Contents\Post\Admin`, `Contents\Page\Admin`, `Contents\Attachment\Admin` | Pagination, search and counts for admin lists, and the magic property `revision` |
| `Widget\Metas\EditTrait` | `Metas\Category\Edit`, `Metas\Tag\Edit` | Sorting, merging, and recounting by status |
| `Widget\Users\EditTrait` | `Users\Edit`, `Users\Profile`, `Register` | Whether a username, email or nickname already exists |
| `Widget\Options\EditTrait` | `Options\General`, `Options\Discussion` | Converts submitted checkbox values into on/off options |
| `Typecho\Widget\Helper\Form\Element\TextInputTrait` | The form elements `Text`, `Password`, `Url`, `Hidden` | The shared implementation of `<input>` text elements |
