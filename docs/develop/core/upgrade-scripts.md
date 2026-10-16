---
title: 编写升级脚本
sidebar_position: 7
---

# 编写升级脚本

新版本需要修改数据库（改表结构、迁移数据、调整选项）时，把迁移代码写成 `Utils\Upgrade` 类中的一个静态方法。本页说明升级程序如何找到并执行这些方法，以及编写时要注意什么。站长执行升级的步骤见[升级](../../guide/getting-started/upgrade.md)。

## 什么时候执行

数据库中记录的版本来自选项 `generator`（值形如 `Typecho 1.3.0`），由 `$options->version` 解析出版本号。程序的版本是常量 `\Typecho\Common::VERSION`。

管理员打开任何后台页面时，`admin/common.php` 比较两者：程序版本更高时，跳转到 `upgrade.php`（访问 `backup.php` 除外，以便先备份）。其他角色不会被跳转。`upgrade.php` 显示“检测到新版本”，点击“完成升级”向 `/action/upgrade` 提交 POST 请求，由 `Widget\Upgrade` 处理：先 `pass('administrator')`，再校验令牌，然后执行 `upgrade()`。

## 执行过程

`Widget\Upgrade::upgrade()`（`var/Widget/Upgrade.php`）：

1. 读取当前版本 `$currentVersion = $options->version`；
2. 如果低于 `Widget\Upgrade::MIN_VERSION`（1.3.0 中为 `'1.1.0'`），提示“请先升级至版本 1.1.0”并返回，不做任何修改；
3. 用反射列出 `Utils\Upgrade` 的所有方法，**按它们在类中声明的顺序**逐个处理：
   1. 从方法名 `v1_3_0` 得到版本号 `1.3.0`；
   2. `$currentVersion` 不低于这个版本时跳过；
   3. 否则以 `($db, $options)` 调用它。`$options` 是用 `Widget\Options::allocWithAlias($version)` 新建的选项对象，反映调用时数据库中的值；
   4. 方法有非空返回值时，收集起来作为升级完成后的提示；
   5. 方法抛出 `Exception` 时，把异常信息显示为错误提示，**立即停止**，后面的方法不再执行；
   6. 方法成功后，把 `generator` 更新为 `Typecho <这个版本>`；
4. 全部完成后把 `generator` 更新为 `Typecho <Common::VERSION>`，提示“升级已经完成”，有返回值时改为显示这些返回值。

`$currentVersion` 在开始时读取一次，之后不再变化，所以“是否跳过”只取决于升级前的版本。

## 写一个升级方法

在 `var/Utils/Upgrade.php` 中按版本号**从小到大**追加方法：

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
     * 1.3.1 的迁移
     */
    public static function v1_3_1(Db $db, Options $options)
    {
        $db->query($db->update('table.options')
            ->rows(['value' => '0'])
            ->where('name = ? AND user = 0', 'someOption'));

        // 可选：返回一段文字，升级完成后显示给管理员
        return _t('已重置 someOption');
    }
}
```

规则：

- 方法必须是 `public static`，名字是 `v` 加上用下划线分隔的版本号；
- 版本号与要发布的 `Common::VERSION` 对应，同时修改 `var/Typecho/Common.php` 中的 `VERSION`，否则管理员不会被引导去升级；
- **按版本从小到大声明**。执行顺序就是声明顺序：如果 `v9_9_9` 写在 `v9_0_0` 前面，会先执行 `v9_9_9`，接着 `v9_0_0` 仍会执行（它高于升级前的版本），而它拿到的 `$options->version` 已经是 `9.9.9`；
- 不要在 `Utils\Upgrade` 中放其他方法，包括私有的辅助方法。反射会列出所有方法，名字不符合 `v<版本>` 的方法会导致 PHP 警告（`Undefined array key 1`）。辅助代码放在别的类中；
- 只要方法的版本高于数据库中的版本就会执行，与 `Common::VERSION` 无关。不要提前加入下一个版本的方法；
- 表名写 `table.名字`，适配器会换成带前缀的真实表名。要兼容 MySQL、PostgreSQL、SQLite 三种数据库，尽量使用 `Db\Query` 构造查询，不写某一种数据库特有的 SQL，见[数据库操作](../plugin/database.md)。

## 示例：v1_3_0

1.3.0 唯一的升级方法 `v1_3_0()` 做了五件事，覆盖了升级脚本的几种典型写法。

**1. 增加路由**

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

修改路由表后删除键 `0`（编译缓存），下一次请求会重新编译，见[路由](./router.md#compiled-cache)。

**2. 重命名选项**：把 `commentsRequireURL` 改名为 `commentsRequireUrl`，直接更新 `options` 表的 `name` 字段。

**3. 改变数据的类型**：`parent` 不为 0 的 `post_draft`、`page_draft` 改为 1.3 新增的 `revision` 类型。

**4. 分批转换大表**：附件的 `text` 字段从 PHP 序列化改为 JSON：

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
            continue;   // 已经不是序列化格式，跳过
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

**5. 转换选项**：`plugins`、`actionTable`、`panelTable`，以及所有 `plugin:*`、`theme:*` 选项，能反序列化的都改存为 JSON。

## 分批迁移的注意事项

第 4 步是分批迁移的范例，写类似代码时注意：

- **按主键分页，不用 `offset`。** 用“主键大于上一批的最大值、按主键升序、取 N 条”的方式翻页。迁移过程中数据在变化，`offset` 翻页可能漏掉或重复处理行；
- **以“取到的行数小于每批数量”作为结束条件**，如 `while ($rowCount === 100)`；
- **每一行都先判断是否已经迁移过**，如上例只处理以 `a:` 开头的值。这样重复执行也不会出错；
- **没有事务。** 升级程序不包裹事务，方法中途抛出异常时，之前已经执行的语句不会回滚，`generator` 仍停留在上一个成功的版本。管理员再次点击“完成升级”时，整个方法从头再执行一遍。例如方法先插入一行、再抛出异常，第二次执行时会因为主键或唯一约束冲突而失败。所以每一步都要能安全地重复执行：插入前先检查是否存在，更新语句带上能排除已处理行的条件；
- **整个升级在一次 HTTP 请求中完成**，受 PHP `max_execution_time` 和内存限制。分批读取可以控制内存，但不能缩短总时间。数据量很大的迁移要尽量减少每行的查询次数；
- **失败要抛出异常**，让升级停下并把信息显示给管理员；不要吞掉异常后继续，否则 `generator` 会被更新为新版本，迁移不会再被执行。升级程序只捕获 `Exception`，PHP 的 `Error`（如 `TypeError`）会直接显示为错误页。

## 本地测试一个升级方法

在开发副本中：

1. 把 `Common::VERSION` 改为新版本号，并加入对应的 `v<版本>` 方法；
2. 以管理员身份打开后台，会被跳转到 `upgrade.php`，页面上显示“从 旧版本 升级到 新版本”；
3. 点击“完成升级”，检查数据库中的变化和 `generator` 的值。

也可以反过来，把数据库中 `generator` 改为较低的版本（例如 `Typecho 1.2.1`）来重新触发升级。重复测试前先备份数据库，以便恢复到迁移前的状态。
