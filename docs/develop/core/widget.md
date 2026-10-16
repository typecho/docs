---
title: Widget 组件模型
sidebar_position: 4
---

# Widget 组件模型

Typecho 的页面、数据列表、后台操作都是 Widget。本页说明 `Typecho\Widget`（`var/Typecho/Widget.php`）怎样创建和复用实例、怎样保存和遍历数据行、魔术属性和魔术方法的规则，以及 `Widget\Base` 和它的接口、trait。在模板中使用 Widget 的写法见[在模板中调用 Widget](../theme/widgets.md)。

## 一个 Widget 是什么

Widget 是继承 `Typecho\Widget` 的类，它同时扮演两种角色：

- **控制器**：创建时执行 `execute()`，读取请求参数、查询数据库或处理表单；
- **数据集**：把查询结果一行一行压入内部的栈（`$stack`），模板用 `next()` 逐行遍历，通过 `$this->title` 这样的属性读当前行（`$row`）的字段。

```php
// 取最近 5 篇文章并输出标题
\Widget\Contents\Post\Recent::alloc('pageSize=5')->to($posts);

while ($posts->next()) {
    echo $posts->title;   // 当前行的 title
}
```

## 创建实例

### widget()

```php
public static function widget(
    string $alias,
    $params = null,
    $request = null,
    $disableSandboxOrCallback = true
): Widget
```

| 参数 | 含义 |
|---|---|
| `$alias` | 类名，可以带 `@别名` 后缀；新旧写法都可以：`'\Widget\Contents\Post\Recent'`、`'Widget_Contents_Post_Recent'`、`'\Widget\Contents\Post\Recent@five'` |
| `$params` | Widget 自己的参数，查询字符串（`'pageSize=5&type=post'`）或数组，在 Widget 中通过 `$this->parameter` 读取 |
| `$request` | 附加的请求参数，查询字符串或数组。Widget 用 `$this->request->get()` 读取时，它们优先于 GET 和 POST 中的同名参数。路由器就是用这个参数把占位符的值传给处理类的 |
| `$disableSandboxOrCallback` | 默认 `true`。传 `false` 或一个回调时，以沙箱方式创建，见下文 |

创建过程：`new` 出对象（构造函数中调用 `init()`）→ 调用 `execute()` → 放入实例池 → 返回。

### alloc() 与 allocWithAlias()

日常更常用的是这两个静态方法，它们只是 `widget()` 的简写：

```php
\Widget\Contents\Post\Recent::alloc($params, $request);
// 等于 Widget::widget(\Widget\Contents\Post\Recent::class, $params, $request)

\Widget\Contents\Post\Recent::allocWithAlias('five', $params, $request);
// 等于 Widget::widget(\Widget\Contents\Post\Recent::class . '@five', $params, $request)
```

### 实例池

同一个请求中，Widget 按“类名 + 别名”只创建一次。实例池的键是把类名中的 `\` 换成 `_` 后的名字，例如 `Widget_Contents_Post_Recent`、`Widget_Contents_Post_Recent@five`。

第二次用同一个键创建时，直接返回池中的实例，**新传入的参数被忽略**，也不会再执行 `execute()`：

```php
$a = \Widget\Contents\Post\Recent::alloc('pageSize=1');
$b = \Widget\Contents\Post\Recent::alloc('pageSize=5');   // 返回 $a，pageSize 仍为 1

$c = \Widget\Contents\Post\Recent::allocWithAlias('five', 'pageSize=5');   // 新实例
```

因此同一个页面中需要同一个类的两组不同数据时，要用 `allocWithAlias()` 给其中一个起别名。`Widget\Options`、`Widget\User`、`Widget\Security` 等在一个请求中只需要一份的对象正是依靠实例池共享的。

从池中移除实例：

```php
\Typecho\Widget::destroy('Widget_Contents_Post_Recent');        // 移除一个，键要用下划线写法
\Typecho\Widget::destroy('Widget_Contents_Post_Recent@five');
\Typecho\Widget::destroy();                                     // 清空整个实例池
```

1.3.0 中，在子类上调用 `destroy()`（如 `\Widget\Contents\Post\Recent::destroy()`），以及给 `Typecho\Widget::destroy()` 传反斜杠写法的类名，都不会移除实例，因为它们拼出的键与池中的下划线键不一致。要移除时按上面的写法调用 `Typecho\Widget::destroy()`。旧名 `destory()` 是 `destroy()` 的已弃用别名。

### 沙箱 {#sandbox}

第四个参数传 `false` 或一个回调时，Widget 在沙箱中创建：

- 每次都创建新实例，**不放入**实例池；
- 请求对象只包含 `$request` 参数传入的值，看不到当前请求的 GET 和 POST；
- 响应对象处于沙箱状态：Widget 中调用 `redirect()`、`throwJson()` 等方法不会输出也不会结束脚本，只是中止这个 Widget 的执行；
- 传入回调时，Widget 执行完后以 Widget 为参数调用回调，之后才退出沙箱。

```php
// 在沙箱中取 cid 为 1 的文章，不受当前页面请求参数的影响
\Widget\Archive::alloc('type=post', 'cid=1', function ($post) {
    echo $post->title;
});
```

## 数据行

| 成员 | 作用 |
|---|---|
| `push(array $row)` | 压入一行，同时设为当前行，`length` 加一 |
| `pushAll(array $rows)` | 依次压入多行（1.3 新增） |
| `next()` | 把下一行设为当前行并返回它，`sequence` 加一；到末尾时返回 `false`，并把指针和 `sequence` 复位，所以可以再遍历一遍 |
| `have()` | 栈不为空时返回 `true` |
| `to(&$var)` | 把 Widget 本身赋给变量，返回 Widget，便于在模板中链式书写 |
| `length` | 行数 |
| `sequence` | 当前行的序号，从 1 开始 |
| `alt(...$args)` | 按 `sequence` 轮流输出参数，例如 `$posts->alt('odd', 'even')` |
| `altBy(int $current, ...$args)` | 按给定序号轮流输出（1.3 新增） |
| `parse(string $template)` | 遍历所有行，每行按模板输出 |
| `template(string $template)` | 把模板中的 `{字段}` 替换为当前行的值并返回（1.3 新增） |
| `toColumn($column)` | 取当前行的一个或多个字段（1.3 新增） |
| `toArray($column)` | 遍历所有行，返回一个或多个字段组成的数组（1.3 新增） |
| `on(bool $condition)` | 条件为真时返回 Widget 本身，否则返回一个吞掉任何方法调用的空对象，用于 `$this->on($this->request->isPost())->save()` 这类写法 |

注意 `push()` 之后当前行是最后压入的那一行，`sequence` 仍为 0；开始遍历前不要读当前行。

```php
$posts->parse('<li><a href="{permalink}">{title}</a></li>');

$titles = $posts->toArray('title');                 // ['标题 1', '标题 2', ...]
$pairs  = $posts->toArray(['cid', 'title']);        // [['cid' => 1, 'title' => '...'], ...]
```

`Widget\Base\Contents`、`Comments`、`Metas`、`Users` 重写了 `push()`：压入前先调用 `filter()` 补全派生字段（并触发对应句柄的 `filter` 钩子）。

## 魔术属性：___prop

读取 `$widget->name` 时，`__get()` 按以下顺序查找：

1. 当前行中已缓存的 `#name`；
2. Widget 的 `___name()` 方法（三个下划线）。调用结果缓存到当前行的 `#name`，同一行再次读取时不再调用；
3. 当前行的 `name` 字段；
4. 插件通过钩子 `___name` 提供的值（见[动态钩子](../plugin/hooks.md#动态钩子)）；
5. 都没有时返回 `null`。

缓存保存在当前行上，`next()` 切换到下一行后，`___name()` 会对新行重新计算。

写入 `$widget->name = $value` 时，如果有 `___name()` 方法（或已有缓存），值写入缓存 `#name`，即覆盖计算结果；否则写入当前行的 `name` 字段。`isset($widget->name)` 对三种来源都返回 `true`。

`Typecho\Widget` 自带 `___sequence()`、`___length()`、`___request()`、`___response()`、`___parameter()`，所以 `$this->request`、`$this->parameter` 在模板中也能读到。

## 魔术方法：__call

调用一个不存在的方法 `$widget->name(...)` 时：

1. 先触发钩子 `call<Name>`（如 `callTitle`），插件可以借此给 Widget 添加方法；
2. 没有插件处理时，`echo $widget->name`。

这就是模板中 `$this->title()` 能直接输出标题的原因：`Widget\Archive` 没有 `title()` 方法，`__call` 输出了 `title` 属性。

## Widget\Base

业务 Widget 继承抽象类 `Widget\Base`（`var/Widget/Base.php`），它在 `init()` 中准备常用的依赖：

| 属性 | 对象 |
|---|---|
| `$this->db` | `Typecho\Db::get()` |
| `$this->options` | `Widget\Options::alloc()` |
| `$this->user` | `Widget\User::alloc()` |
| `$this->security` | `Widget\Security::alloc()` |

子类可以重写 `initComponents(int &$components)` 少准备一些。参数是 `INIT_DB`、`INIT_USER`、`INIT_SECURITY`、`INIT_OPTIONS` 的按位组合，默认 `INIT_ALL`；`INIT_NONE` 表示都不要，这时连 `$this->db` 也不设置。只要不是 `INIT_NONE`，`$this->db` 总会被设置。

之后 `init()` 调用 `initParameter(Config $parameter)`，子类可以在这里给 `$this->parameter` 设置默认值，或根据参数做准备。`Widget\Options` 同时用了这两个方法，因为它不能在创建时依赖它自己：

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

## 基类与接口

数据行的基类在 `var/Widget/Base/`：

| 类 | 对应的表 |
|---|---|
| `Widget\Base\Contents` | `contents`（文章、页面、附件、草稿、修订版本） |
| `Widget\Base\Comments` | `comments` |
| `Widget\Base\Metas` | `metas`（分类、标签） |
| `Widget\Base\Users` | `users` |
| `Widget\Base\Options` | `options` |

它们实现的接口：

| 接口 | 方法 | 实现者 | 作用 |
|---|---|---|---|
| `Widget\Base\QueryInterface` | `select(...$fields)`、`size(Query)`、`insert(array)`、`update(array, Query)`、`delete(Query)` | 全部五个 | 对本表的标准查询与增删改，`select()` 返回已经 `from` 了本表的查询对象 |
| `Widget\Base\RowFilterInterface` | `filter(array $row): array` | Contents、Comments、Metas、Users | 1.3 新增。`push()` 前补全派生字段 |
| `Widget\Base\PrimaryKeyInterface` | `getPrimaryKey(): string` | Contents、Comments、Metas、Users | 1.3 新增。返回主键名（`cid`、`coid`、`mid`、`uid`），供树形 trait 使用 |
| `Typecho\Router\ParamsDelegateInterface` | `getRouterParam(string $key): string` | Contents、Comments、Metas、Users | 1.3 新增。为 `Router::url()` 提供参数，见[路由](./router.md#params-delegate) |
| `Widget\ActionInterface` | `action()` | 所有能通过 `/action/<名字>` 调用的 Widget | `Widget\Action` 只分发给实现了它的类 |

旧代码中的 `Widget_Abstract_Contents` 等名字是这些基类的别名，见[旧类名对照表](../reference/class-aliases.md)。

## 1.3 新增的 trait

1.3 把多个 Widget 共用的逻辑拆成了 trait：

| trait | 使用者 | 提供 |
|---|---|---|
| `Widget\Base\TreeTrait` | `Contents\Page\Admin`、`Contents\From`、`Metas\Category\Admin`、`Metas\Category\Related`、`Metas\From`，以及通过 `TreeViewTrait` 间接使用的两个 `Rows` | 父子树：`getAllParents()`、`getAllChildIds()`、`getChildIds()`、`getRows()`、`getRow()`、`hasParent()`、`levelsAlt()`，魔术属性 `directory`、`children`；使用者要实现 `initTreeRows()` |
| `Widget\Base\TreeViewTrait` | `Contents\Page\Rows`、`Metas\Category\Rows` | 按树形输出列表，即 `listPages()`、`listCategories()` 的实现 |
| `Widget\Metas\Category\InitTreeRowsTrait` | 分类相关的四个 Widget | 为 `TreeTrait` 读出全部分类 |
| `Widget\Contents\EditTrait` | `Contents\Post\Edit`、`Contents\Page\Edit` | 自定义字段读写、分类与标签关联、发布与保存草稿等编辑逻辑 |
| `Widget\Contents\PrepareEditTrait` | `Contents\Post\Edit`、`Contents\Page\Edit`、`Contents\Attachment\Edit` | 载入要编辑的内容（含草稿）、后台菜单标题、`allow()` 权限判断 |
| `Widget\Contents\AdminTrait` | `Contents\Post\Admin`、`Contents\Page\Admin`、`Contents\Attachment\Admin` | 后台列表的分页、搜索、计数，魔术属性 `revision` |
| `Widget\Metas\EditTrait` | `Metas\Category\Edit`、`Metas\Tag\Edit` | 排序、合并、按状态重新计数 |
| `Widget\Users\EditTrait` | `Users\Edit`、`Users\Profile`、`Register` | 用户名、邮箱、昵称是否已存在 |
| `Widget\Options\EditTrait` | `Options\General`、`Options\Discussion` | 把复选框的提交值转换为开关选项 |
| `Typecho\Widget\Helper\Form\Element\TextInputTrait` | 表单元素 `Text`、`Password`、`Url`、`Hidden` | `<input>` 文本类元素的公共实现 |
