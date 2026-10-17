---
title: Writing Upgrade Scripts
sidebar_position: 7
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Writing Upgrade Scripts

When a new version needs to change the database (alter the table structure, migrate data, adjust options), write the migration code as a static method of the `Utils\Upgrade` class. This page explains how the upgrade program finds and runs these methods, and what to watch out for when you write them. For the steps a site owner follows to upgrade, see [Upgrading](../../guide/getting-started/upgrade.md).

## When it runs

The version recorded in the database comes from the option `generator` (with a value such as `Typecho 1.3.0`), and `$options->version` parses the version number out of it. The program's version is the constant `\Typecho\Common::VERSION`.

When an administrator opens any admin page, `admin/common.php` compares the two: if the program version is higher, it redirects to `upgrade.php` (except when visiting `backup.php`, so that a backup can be made first). Other roles are not redirected. `upgrade.php` shows "New version detected"; clicking "Complete upgrade" sends a POST request to `/action/upgrade`, which `Widget\Upgrade` handles: first `pass('administrator')`, then token validation, and then `upgrade()`.

## How it runs

`Widget\Upgrade::upgrade()` (`var/Widget/Upgrade.php`):

1. Reads the current version, `$currentVersion = $options->version`;
2. If it is lower than `Widget\Upgrade::MIN_VERSION` (`'1.1.0'` in 1.3.0), shows "Please upgrade to version 1.1.0 first" and returns without changing anything;
3. Uses reflection to list all methods of `Utils\Upgrade` and processes them one by one **in the order they are declared in the class**:
   1. Gets the version number `1.3.0` from the method name `v1_3_0`;
   2. Skips the method if `$currentVersion` is not lower than this version;
   3. Otherwise calls it with `($db, $options)`. `$options` is a new options object created with `Widget\Options::allocWithAlias($version)`, reflecting the values in the database at the time of the call;
   4. If the method returns a non-empty value, collects it as a message to show after the upgrade;
   5. If the method throws an `Exception`, shows the exception message as an error and **stops immediately**; the remaining methods do not run;
   6. After a method succeeds, updates `generator` to `Typecho <this version>`;
4. When everything is done, updates `generator` to `Typecho <Common::VERSION>` and shows "Upgrade complete", or the collected return values if there are any.

`$currentVersion` is read once at the start and does not change afterwards, so whether a method is skipped depends only on the version before the upgrade.

## Writing an upgrade method

Append methods to `var/Utils/Upgrade.php` in **ascending** version order:

```php title="var/Utils/Upgrade.php"
namespace Utils;

use Typecho\Db;
use Widget\Options;

class Upgrade
{
    public static function v1_3_0(Db $db, Options $options)
    {
        // ……
    }

    /**
     * Migration for 1.3.1
     */
    public static function v1_3_1(Db $db, Options $options)
    {
        $db->query($db->update('table.options')
            ->rows(['value' => '0'])
            ->where('name = ? AND user = 0', 'someOption'));

        // Optional: return a message shown to the administrator after the upgrade
        return _t('someOption has been reset');
    }
}
```

Rules:

- The method must be `public static`, and its name is `v` followed by the version number separated by underscores;
- The version number corresponds to the `Common::VERSION` being released; change `VERSION` in `var/Typecho/Common.php` at the same time, or administrators will not be directed to upgrade;
- **Declare methods in ascending version order.** The execution order is the declaration order: if `v9_9_9` is written before `v9_0_0`, `v9_9_9` runs first, then `v9_0_0` still runs (it is higher than the version before the upgrade), and the `$options->version` it sees is already `9.9.9`;
- Do not put any other methods in `Utils\Upgrade`, including private helper methods. Reflection lists every method, and a method whose name does not match `v<version>` causes a PHP warning (`Undefined array key 1`). Put helper code in another class;
- A method runs whenever its version is higher than the version in the database, regardless of `Common::VERSION`. Do not add methods for the next version ahead of time;
- Write table names as `table.<name>`; the adapter replaces them with the real, prefixed table names. To stay compatible with all three databases, MySQL, PostgreSQL and SQLite, build queries with `Db\Query` as far as possible and do not write SQL specific to one database; see [Database operations](../plugin/database.md).

## Example: v1_3_0

`v1_3_0()`, the only upgrade method in 1.3.0, does five things, which cover several typical patterns of upgrade scripts.

**1. Adding routes**

```php
$routingTable = $options->routingTable;

$routingTable['comment_page'] = [
    'url'    => '[permalink:string]/comment-page-[commentPage:digital]',
    'widget' => '\Widget\CommentPage',
    'action' => 'action'
];

$routingTable['feed'] = [
    'url'    => '/feed[feed:string:0]',
    'widget' => '\Widget\Feed',
    'action' => 'render'
];

unset($routingTable[0]);

$db->query($db->update('table.options')
    ->rows(['value' => json_encode($routingTable)])
    ->where('name = ?', 'routingTable'));
```

After modifying the routing table, delete key `0` (the compiled cache); the next request recompiles it; see [Routing](./router.md#compiled-cache).

**2. Renaming an option**: renames `commentsRequireURL` to `commentsRequireUrl` by updating the `name` field of the `options` table directly.

**3. Changing the type of data**: `post_draft` and `page_draft` rows whose `parent` is not 0 are changed to the `revision` type added in 1.3.

**4. Converting a large table in batches**: the `text` field of attachments is changed from PHP serialization to JSON:

```php
$lastId = 0;
do {
    $rows = $db->fetchAll(
        $db->select('cid', 'text')->from('table.contents')
            ->where('cid > ?', $lastId)
            ->where('type = ?', 'attachment')
            ->order('cid', Db::SORT_ASC)
            ->limit(100)
    );

    $rowCount = count($rows);
    if ($rowCount > 0) {
        $lastId = $rows[$rowCount - 1]['cid'];
    }

    foreach ($rows as $row) {
        if (strpos($row['text'], 'a:') !== 0) {
            continue;   // no longer serialized; skip
        }

        $value = @unserialize($row['text']);
        if ($value !== false) {
            $db->query($db->update('table.contents')
                ->rows(['text' => json_encode($value)])
                ->where('cid = ?', $row['cid']));
        }
    }
} while ($rowCount === 100);
```

**5. Converting options**: `plugins`, `actionTable`, `panelTable`, and all `plugin:*` and `theme:*` options are stored as JSON wherever they can be unserialized.

## Notes on batch migration

Step 4 is the model for batch migration. When you write similar code:

- **Page by primary key, not by `offset`.** Page by "primary key greater than the largest value of the previous batch, in ascending primary key order, take N rows". Data changes during migration, and paging with `offset` can skip rows or process them twice;
- **Use "fewer rows fetched than the batch size" as the end condition**, as in `while ($rowCount === 100)`;
- **Check for every row whether it has already been migrated**, as the example above only processes values that start with `a:`. That way running it again does no harm;
- **There is no transaction.** The upgrade program does not wrap anything in a transaction. If a method throws an exception partway through, the statements already executed are not rolled back, and `generator` stays at the last successful version. When the administrator clicks "Complete upgrade" again, the whole method runs again from the start. For example, if a method inserts a row and then throws an exception, the second run fails because of a primary key or unique constraint conflict. So every step must be safe to repeat: check for existence before inserting, and give update statements a condition that excludes rows already processed;
- **The whole upgrade happens in a single HTTP request** and is subject to PHP's `max_execution_time` and memory limits. Reading in batches keeps memory under control but does not shorten the total time. For migrations of very large data sets, keep the number of queries per row as low as possible;
- **Throw an exception on failure**, so that the upgrade stops and the message is shown to the administrator. Do not swallow the exception and carry on, or `generator` will be updated to the new version and the migration will never run again. The upgrade program only catches `Exception`; a PHP `Error` (such as `TypeError`) is shown directly as an error page.

## Testing an upgrade method locally

In a development copy:

1. Change `Common::VERSION` to the new version number and add the corresponding `v<version>` method;
2. Open the admin panel as an administrator; you are redirected to `upgrade.php`, and the page shows "Upgrade from old version to new version";
3. Click "Complete upgrade" and check the changes in the database and the value of `generator`.

You can also do it the other way round: change `generator` in the database to a lower version (for example `Typecho 1.2.1`) to trigger the upgrade again. Back up the database before repeating a test so that you can restore the state before the migration.
