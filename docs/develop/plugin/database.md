---
title: 数据库操作
sidebar_position: 7
---

# 数据库操作

Typecho 支持 MySQL、PostgreSQL 和 SQLite 三种数据库。插件通过 `Typecho\Db` 访问数据库，用链式的查询构造器拼 SQL，同一段代码在三种数据库上都能运行。

Typecho 自带的 7 张表和每个字段见[数据表结构](../reference/database-schema.md)。

## 取得数据库对象

```php
$db = \Typecho\Db::get();
```

在继承了 `\Widget\Base` 的类中（如[路由、Action 与后台面板](./routes-actions.md)中的 `Action`）可以直接用 `$this->db`，两者是同一个对象。

## 表名前缀：`table.`

安装时可以设置表前缀（默认 `typecho_`）。在查询构造器中写表名时，用 `table.` 代替前缀，Typecho 会替换成实际的前缀：

```php
$db->select()->from('table.contents');   // FROM typecho_contents
```

`table.` 在字段名中同样有效，例如 `table.contents.cid`。直接写 SQL 字符串时不会替换，要用 `$db->getPrefix()` 拼表名，见下文。

## 增删改查

下面的例子使用[留言板插件](./routes-actions.md)的 `guestbook` 表（字段 `id`、`author`、`text`、`ip`、`created`）：

```php
$db = \Typecho\Db::get();

// 插入，返回新行的主键
$id = $db->query($db->insert('table.guestbook')->rows([
    'author'  => '张三',
    'text'    => '你好',
    'ip'      => '127.0.0.1',
    'created' => time(),
]));

// 查询一行，没有结果时返回 null
$row = $db->fetchRow($db->select()->from('table.guestbook')->where('id = ?', $id));

// 查询多行：指定字段、条件、排序、分页
$rows = $db->fetchAll($db->select('id', 'author', 'created')
    ->from('table.guestbook')
    ->where('created > ?', time() - 86400)
    ->where('author <> ?', '')
    ->order('created', \Typecho\Db::SORT_DESC)
    ->page(1, 20));

// 计数
$count = $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from('table.guestbook'))->num;

// 更新，返回受影响的行数
$affected = $db->query($db->update('table.guestbook')
    ->rows(['text' => '改过的留言'])
    ->where('id = ?', $id));

// 用表达式更新：在原值上计算
$db->query($db->update('table.guestbook')
    ->expression('created', 'created + 60')
    ->where('id = ?', $id));

// 删除，返回删除的行数
$deleted = $db->query($db->delete('table.guestbook')->where('id IN ?', [$id]));
```

### 执行和取结果

| 方法 | 返回 |
|---|---|
| `$db->fetchAll($query)` | 所有行组成的数组，每行是关联数组；没有结果时是空数组 |
| `$db->fetchRow($query)` | 第一行（关联数组），没有结果时是 `null` |
| `$db->fetchObject($query)` | 第一行（`stdClass` 对象），没有结果时是 `null` |
| `$db->query($insert)` | 新行的主键 |
| `$db->query($update)` / `$db->query($delete)` | 受影响的行数 |

`fetchAll()` 和 `fetchRow()` 的第二个参数可以是一个回调，每一行先交给它处理，例如 `[$widget, 'push']` 把结果压入 Widget。

取回的值的类型取决于数据库驱动：使用 PDO 驱动时整数字段是 `int`，使用 `Mysqli`、`Pgsql` 驱动时都是字符串。比较或输出 JSON 前请自己转换类型。

## Db\Query 的链式方法

`$db->select()`、`insert()`、`update()`、`delete()` 返回一个 `Typecho\Db\Query` 对象，后面接这些方法：

| 方法 | 作用 |
|---|---|
| `select(...$fields)` | 要取的字段，不写时为 `*`；`['COUNT(id)' => 'num']` 表示 `COUNT(id) AS num` |
| `from($table)` | 表名 |
| `where($condition, ...$values)` | 条件，`?` 是占位符，值会被转义；多次调用以 `AND` 连接 |
| `orWhere($condition, ...$values)` | 同上，以 `OR` 连接 |
| `join($table, $condition, $op = Db::INNER_JOIN)` | 连接，`$op` 还可以是 `Db::LEFT_JOIN`、`Db::RIGHT_JOIN`（SQLite 3.39 起支持）。不要使用 `Db::OUTER_JOIN`：它会原样生成 `OUTER JOIN`，三种数据库都不接受这种写法 |
| `order($field, $sort = Db::SORT_ASC)` | 排序，`Db::SORT_DESC` 为倒序；多次调用依次追加 |
| `group($field)`、`having($condition, ...$values)` | 分组 |
| `limit($n)`、`offset($n)` | 取几行、跳过几行 |
| `page($page, $pageSize)` | 分页，等于 `limit($pageSize)->offset(($page - 1) * $pageSize)` |
| `rows(array $rows)` | `insert()` / `update()` 要写入的字段和值，值会被转义，`null` 写为 `NULL` |
| `expression($field, $expression)` | `update()` 中把字段设为一个表达式，不转义 |

`where()` 的值是数组时展开为 `(1,2,3)`，所以 `where('id IN ?', $ids)` 可以直接使用。需要模糊查询时自己加上 `%`：`where('title LIKE ?', '%' . $keyword . '%')`。

**不要**把变量直接拼进条件字符串，一律使用 `?` 占位符。

带连接的查询，例如取某个分类下已发布的文章：

```php
$db = \Typecho\Db::get();

$posts = $db->fetchAll($db->select('table.contents.cid', 'table.contents.title')
    ->from('table.contents')
    ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
    ->where('table.relationships.mid = ?', 1)
    ->where('table.contents.type = ?', 'post')
    ->where('table.contents.status = ?', 'publish')
    ->order('table.contents.created', \Typecho\Db::SORT_DESC));
```

把查询对象转换成字符串可以看到生成的 SQL，值的位置显示为 `#param:0#` 这样的占位标记：

```php
echo $db->select()->from('table.guestbook')->where('id = ?', 1);
// SELECT *  FROM typecho_guestbook WHERE  ("id" = #param:0# )
```

## 直接执行 SQL

查询构造器无法表达的语句（如建表）可以直接把 SQL 字符串交给 `query()`。这时 `table.` 不会被替换，也没有占位符，必须自己拼前缀、自己保证安全：

```php
$db = \Typecho\Db::get();
$rows = $db->fetchAll('SELECT cid FROM ' . $db->getPrefix() . 'contents');
```

写入类的语句要把第二个参数设为 `\Typecho\Db::WRITE`：Typecho 可以把读和写分配到不同的数据库服务器上，这个参数决定使用哪一个。

## 建表时兼容三种数据库 {#create-table}

三种数据库的建表语法不同，要分别写。用 `$db->getAdapter()->getDriver()` 判断当前数据库，它返回 `mysql`、`pgsql` 或 `sqlite`（无论使用的是 PDO 还是原生驱动）。留言板插件的 `install()`：

```php
public static function install()
{
    $db = Db::get();
    $table = $db->getPrefix() . 'guestbook';

    switch ($db->getAdapter()->getDriver()) {
        case 'mysql':
            $db->query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `author` varchar(150) DEFAULT NULL,
                `text` text,
                `ip` varchar(64) DEFAULT NULL,
                `created` int(10) unsigned DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `created` (`created`)
            ) DEFAULT CHARSET=utf8mb4", Db::WRITE);
            break;
        case 'pgsql':
            $db->query("CREATE TABLE IF NOT EXISTS \"{$table}\" (
                \"id\" SERIAL PRIMARY KEY,
                \"author\" VARCHAR(150) NULL DEFAULT NULL,
                \"text\" TEXT NULL DEFAULT NULL,
                \"ip\" VARCHAR(64) NULL DEFAULT NULL,
                \"created\" INT NULL DEFAULT '0'
            )", Db::WRITE);
            $db->query("CREATE INDEX IF NOT EXISTS \"{$table}_created\" ON \"{$table}\" (\"created\")", Db::WRITE);
            break;
        case 'sqlite':
            $db->query("CREATE TABLE IF NOT EXISTS \"{$table}\" (
                \"id\" INTEGER NOT NULL PRIMARY KEY,
                \"author\" varchar(150) DEFAULT NULL,
                \"text\" text,
                \"ip\" varchar(64) DEFAULT NULL,
                \"created\" int(10) DEFAULT '0'
            )", Db::WRITE);
            $db->query("CREATE INDEX IF NOT EXISTS \"{$table}_created\" ON \"{$table}\" (\"created\")", Db::WRITE);
            break;
    }
}
```

（`Db` 是文件开头 `use Typecho\Db;` 导入的。）要点：

- **自增主键**：MySQL 用 `AUTO_INCREMENT`，PostgreSQL 用 `SERIAL PRIMARY KEY`，SQLite 用 `INTEGER NOT NULL PRIMARY KEY`。PostgreSQL 下 Typecho 在插入时自动查出主键字段并用 `RETURNING` 取回新值，所以表**必须有主键**，`insert` 才能返回新行的 ID；
- **引号**：MySQL 用反引号，PostgreSQL 和 SQLite 用双引号。这只是建表时要注意的事，之后用查询构造器读写时，它会自动给字段名加上当前数据库的引号；
- **每次只执行一条语句**：建索引要另外调用一次 `query()`；
- **`IF NOT EXISTS`**：禁用再启用插件时会再次执行 `install()`，有了它不会报错；
- **时间**：Typecho 自己的表把时间存为 Unix 时间戳整数（`created`、`modified`），插件可以照做，显示时用 `new \Typecho\Date($timestamp)` 转换成站点时区。

### 删除数据表

Typecho 不会在禁用插件时删除插件建的表。是否删除应由用户决定：直接在 `deactivate()` 中删表，用户禁用插件排查问题时会丢失全部数据。常见做法是在插件设置中提供一个“禁用时删除数据”的选项：

```php
public static function deactivate()
{
    if (\Widget\Options::alloc()->plugin('Guestbook')->dropTable) {
        $db = \Typecho\Db::get();
        $db->query('DROP TABLE IF EXISTS ' . $db->getPrefix() . 'guestbook', \Typecho\Db::WRITE);
    }
}
```

这里的 `dropTable` 是插件在 `config()` 中添加的一个设置项（例如一个 `Radio`），写法见[插件配置](./settings.md)。注意禁用插件时 Typecho 会先调用 `deactivate()`、再删除插件设置，所以在 `deactivate()` 中还能读到设置。

## 使用 Typecho 自己的表

读取文章、评论等数据时，优先用 Typecho 的 Widget（见[在模板中调用 Widget](../theme/widgets.md)），它们处理了权限、状态和各种格式。确实需要直接查询时，注意：

- `contents` 表中文章、页面、附件、草稿和修订版本混在一起，按 `type` 和 `status` 区分，取值见[数据表结构](../reference/database-schema.md#contents)；
- 直接修改 `contents`、`comments`、`metas` 表时，Typecho 维护的计数（`commentsNum`、`count`）不会自动更新。
