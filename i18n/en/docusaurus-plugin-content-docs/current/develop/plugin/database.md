---
title: Database Operations
sidebar_position: 7
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Database Operations

Typecho supports three databases: MySQL, PostgreSQL and SQLite. Plugins access the database through `Typecho\Db` and build SQL with a chainable query builder, so the same code runs on all three databases.

For Typecho's 7 built-in tables and each of their columns, see [Database schema](../reference/database-schema.md).

## Getting the database object

```php
$db = \Typecho\Db::get();
```

In a class that extends `\Widget\Base` (such as `Action` in [Routes, actions and admin panels](./routes-actions.md)), you can use `$this->db` directly; both are the same object.

## Table prefix: `table.`

You can set a table prefix during installation (default `typecho_`). When you write a table name in the query builder, use `table.` in place of the prefix, and Typecho replaces it with the actual prefix:

```php
$db->select()->from('table.contents');   // FROM typecho_contents
```

`table.` also works in column names, for example `table.contents.cid`. It is not replaced when you write an SQL string directly; build the table name with `$db->getPrefix()` instead, as shown below.

## Create, read, update, delete

The examples below use the `guestbook` table of the [guestbook plugin](./routes-actions.md) (columns `id`, `author`, `text`, `ip`, `created`):

```php
$db = \Typecho\Db::get();

// Insert; returns the primary key of the new row
$id = $db->query($db->insert('table.guestbook')->rows([
    'author'  => 'Alice',
    'text'    => 'Hello',
    'ip'      => '127.0.0.1',
    'created' => time(),
]));

// Fetch one row; returns null when there is no result
$row = $db->fetchRow($db->select()->from('table.guestbook')->where('id = ?', $id));

// Fetch several rows: columns, conditions, order, pagination
$rows = $db->fetchAll($db->select('id', 'author', 'created')
    ->from('table.guestbook')
    ->where('created > ?', time() - 86400)
    ->where('author <> ?', '')
    ->order('created', \Typecho\Db::SORT_DESC)
    ->page(1, 20));

// Count
$count = $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from('table.guestbook'))->num;

// Update; returns the number of affected rows
$affected = $db->query($db->update('table.guestbook')
    ->rows(['text' => 'Edited message'])
    ->where('id = ?', $id));

// Update with an expression: compute from the current value
$db->query($db->update('table.guestbook')
    ->expression('created', 'created + 60')
    ->where('id = ?', $id));

// Delete; returns the number of deleted rows
$deleted = $db->query($db->delete('table.guestbook')->where('id IN ?', [$id]));
```

### Executing and fetching results

| Method | Returns |
|---|---|
| `$db->fetchAll($query)` | An array of all rows, each an associative array; an empty array when there are no results |
| `$db->fetchRow($query)` | The first row (associative array); `null` when there are no results |
| `$db->fetchObject($query)` | The first row (`stdClass` object); `null` when there are no results |
| `$db->query($insert)` | The primary key of the new row |
| `$db->query($update)` / `$db->query($delete)` | The number of affected rows |

The second argument of `fetchAll()` and `fetchRow()` can be a callback that each row is passed to first, for example `[$widget, 'push']` to push the results into a widget.

The type of the fetched values depends on the database driver: with PDO drivers, integer columns are `int`; with the `Mysqli` and `Pgsql` drivers, everything is a string. Convert the types yourself before comparing values or outputting JSON.

## Chainable methods of Db\Query

`$db->select()`, `insert()`, `update()` and `delete()` return a `Typecho\Db\Query` object, which you follow with these methods:

| Method | Purpose |
|---|---|
| `select(...$fields)` | Columns to fetch; `*` if omitted. `['COUNT(id)' => 'num']` means `COUNT(id) AS num` |
| `from($table)` | Table name |
| `where($condition, ...$values)` | Condition; `?` is a placeholder and the values are escaped. Multiple calls are joined with `AND` |
| `orWhere($condition, ...$values)` | Same as above, joined with `OR` |
| `join($table, $condition, $op = Db::INNER_JOIN)` | Join; `$op` can also be `Db::LEFT_JOIN` or `Db::RIGHT_JOIN` (supported by SQLite from 3.39). Do not use `Db::OUTER_JOIN`: it emits a bare `OUTER JOIN`, which none of the three databases accepts |
| `order($field, $sort = Db::SORT_ASC)` | Sort; `Db::SORT_DESC` for descending. Multiple calls are appended in order |
| `group($field)`, `having($condition, ...$values)` | Grouping |
| `limit($n)`, `offset($n)` | How many rows to take, how many to skip |
| `page($page, $pageSize)` | Pagination; equivalent to `limit($pageSize)->offset(($page - 1) * $pageSize)` |
| `rows(array $rows)` | Columns and values to write for `insert()` / `update()`; values are escaped and `null` is written as `NULL` |
| `expression($field, $expression)` | In `update()`, sets a column to an expression, without escaping |

When a `where()` value is an array, it expands to `(1,2,3)`, so you can use `where('id IN ?', $ids)` directly. For fuzzy matching, add the `%` yourself: `where('title LIKE ?', '%' . $keyword . '%')`.

**Never** concatenate variables directly into the condition string; always use the `?` placeholder.

A query with a join, for example fetching the published posts in a category:

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

Converting a query object to a string shows the generated SQL, with the values shown as markers like `#param:0#`:

```php
echo $db->select()->from('table.guestbook')->where('id = ?', 1);
// SELECT *  FROM typecho_guestbook WHERE  ("id" = #param:0# )
```

## Executing SQL directly

For statements the query builder cannot express (such as creating a table), you can pass an SQL string directly to `query()`. In this case `table.` is not replaced and there are no placeholders, so you must add the prefix yourself and make sure the SQL is safe yourself:

```php
$db = \Typecho\Db::get();
$rows = $db->fetchAll('SELECT cid FROM ' . $db->getPrefix() . 'contents');
```

For write statements, set the second argument to `\Typecho\Db::WRITE`: Typecho can send reads and writes to different database servers, and this argument decides which one is used.

## Creating tables compatible with all three databases {#create-table}

The three databases have different table creation syntax, so you have to write each separately. Use `$db->getAdapter()->getDriver()` to determine the current database; it returns `mysql`, `pgsql` or `sqlite` (whether a PDO or native driver is used). The guestbook plugin's `install()`:

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

(`Db` is imported with `use Typecho\Db;` at the top of the file.) Key points:

- **Auto-increment primary key**: MySQL uses `AUTO_INCREMENT`, PostgreSQL uses `SERIAL PRIMARY KEY`, SQLite uses `INTEGER NOT NULL PRIMARY KEY`. On PostgreSQL, Typecho looks up the primary key column on insert and fetches the new value with `RETURNING`, so the table **must have a primary key** for `insert` to return the new row's ID;
- **Quotes**: MySQL uses backticks; PostgreSQL and SQLite use double quotes. This only matters when creating tables; when you later read and write with the query builder, it quotes column names automatically for the current database;
- **One statement at a time**: creating an index needs a separate `query()` call;
- **`IF NOT EXISTS`**: `install()` runs again when the plugin is deactivated and reactivated; with this clause it does not fail;
- **Time**: Typecho's own tables store times as Unix timestamp integers (`created`, `modified`). Plugins can do the same; when displaying, convert to the site's time zone with `new \Typecho\Date($timestamp)`.

### Dropping tables

Typecho does not drop the tables a plugin created when the plugin is deactivated. Whether to drop them should be the user's decision: if you drop the table directly in `deactivate()`, users who deactivate the plugin while troubleshooting lose all their data. A common approach is to offer a "delete data when disabled" option in the plugin settings:

```php
public static function deactivate()
{
    if (\Widget\Options::alloc()->plugin('Guestbook')->dropTable) {
        $db = \Typecho\Db::get();
        $db->query('DROP TABLE IF EXISTS ' . $db->getPrefix() . 'guestbook', \Typecho\Db::WRITE);
    }
}
```

Here `dropTable` is a setting the plugin adds in `config()` (for example a `Radio`); see [Plugin settings](./settings.md) for how to write it. Note that when a plugin is disabled, Typecho calls `deactivate()` first and deletes the plugin settings afterwards, so you can still read the settings in `deactivate()`.

## Using Typecho's own tables

To read posts, comments and other data, prefer Typecho's widgets (see [Calling widgets in templates](../theme/widgets.md)); they handle permissions, statuses and various formats. When you really need to query directly, note:

- The `contents` table mixes posts, pages, attachments, drafts and revisions, distinguished by `type` and `status`; for the values, see [Database schema](../reference/database-schema.md#contents);
- When you modify the `contents`, `comments` or `metas` tables directly, the counters Typecho maintains (`commentsNum`, `count`) are not updated automatically.
