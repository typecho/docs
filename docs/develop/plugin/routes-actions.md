---
title: 路由、Action 与后台面板
sidebar_position: 6
---

# 路由、Action 与后台面板

钩子只能在 Typecho 已有的流程中插入代码。插件需要自己的页面或者接收表单提交时，要用另外三种扩展方式：

| 方式 | 地址 | 用途 | 添加 / 删除 |
|---|---|---|---|
| 路由 | 自定义，如 `/guestbook/` | 前台页面 | `Helper::addRoute()` / `removeRoute()` |
| Action | `/action/名字` | 接收表单、Ajax 请求 | `Helper::addAction()` / `removeAction()` |
| 后台面板 | `admin/extending.php?panel=插件名/文件名` | 后台管理页面 | `Helper::addPanel()` / `removePanel()`，`addMenu()` / `removeMenu()` |

它们都保存在数据库中（选项 `routingTable`、`actionTable`、`panelTable`），所以在 `activate()` 中添加一次即可，并且**必须**在 `deactivate()` 中删除，Typecho 不会自动清理。`Helper` 指 `\Utils\Helper`，全部方法见 [Utils\Helper](../reference/helper.md)。

本页用一个留言板插件 `Guestbook` 演示这三种方式：访客在前台 `/guestbook/` 页面留言，管理员在后台“管理 → 留言”中查看和删除。

```text
usr/plugins/Guestbook/
├── Plugin.php   注册路由、Action、面板，建数据表
├── Action.php   前台页面和表单处理
└── panel.php    后台面板
```

## Plugin.php

```php title="usr/plugins/Guestbook/Plugin.php"
<?php

namespace TypechoPlugin\Guestbook;

use Typecho\Db;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 留言板：前台 /guestbook/ 页面留言，后台“管理 → 留言”查看和删除
 *
 * @package Guestbook
 * @author 你的名字
 * @version 1.0.0
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        self::install();

        Helper::addRoute('guestbook', '/guestbook/', Action::class, 'render', 'index');
        Helper::addAction('guestbook', Action::class);
        Helper::addPanel(3, 'Guestbook/panel.php', _t('留言'), _t('管理留言'), 'administrator');
    }

    public static function deactivate()
    {
        Helper::removeRoute('guestbook');
        Helper::removeAction('guestbook');
        Helper::removePanel(3, 'Guestbook/panel.php');
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }

    /**
     * 建表，三种数据库各写一份
     */
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
}
```

`install()` 建立留言数据表，写法见[数据库操作](./database.md#create-table)。

## 路由

```php
Helper::addRoute('guestbook', '/guestbook/', Action::class, 'render', 'index');
```

`addRoute($name, $url, $widget, $action = null, $after = null)` 的参数：

- `$name`：路由名，全站唯一。删除路由、生成地址时都用它；
- `$url`：路径规则，可以带占位符，如 `/guestbook/[page:digital]/`。占位符的写法见[默认路由与 Action](../reference/routes-actions.md#placeholders)，匹配到的值可以用 `$this->request->get('page')` 读取；
- `$widget`：处理请求的类，一般继承 `\Widget\Base`；
- `$action`：匹配后调用的方法名。Typecho 先创建这个类的对象（会调用它的 `execute()` 方法），再调用这个方法；
- `$after`：把新路由插在哪个路由的后面。不写或者找不到时放在最后。

请求到来时，Typecho 按路由表的顺序逐条匹配，使用第一条匹配的路由；如果处理它的类抛出了代码为 404 的异常，就继续尝试后面的路由。默认路由中有不少匹配范围很广的规则（例如独立页面的 `/[slug].html`、评论提交的 `[permalink:string]/[type:alpha]`）。放在最后的路由，要等前面所有能匹配的路由都返回 404 后才会被尝试。所以上例把留言板路由放在 `index`（首页）之后，让它最先被检查。

在代码中生成路由的地址：

```php
$url = \Typecho\Router::url('guestbook', null, \Widget\Options::alloc()->index);
```

第二个参数是填充占位符的数组，例如 `['page' => 2]`。第三个参数是前缀，用 `index` 可以自动适应是否启用了地址重写。

## Action

```php
Helper::addAction('guestbook', Action::class);
```

添加后，向 `/action/guestbook`（未启用地址重写时是 `/index.php/action/guestbook`）发送的请求由 `Action` 类处理。这个类必须实现 `\Widget\ActionInterface`，也就是有一个 `action()` 方法，Typecho 创建对象后调用它。

- Action 名只能由字母、数字、`_`、`-` 组成；
- Typecho 自己的 23 个 Action（如 `login`、`upload`、`plugins-edit`）见[默认路由与 Action](../reference/routes-actions.md#actions)。插件添加的同名 Action 会**替换**核心的 Action，请加上插件名作前缀；
- 一个 Action 通常处理多种操作，用请求参数区分。`$this->on($条件)->方法()` 只在条件为真时调用方法，是 Typecho 自己的写法。

## Action.php

```php title="usr/plugins/Guestbook/Action.php"
<?php

namespace TypechoPlugin\Guestbook;

use Typecho\Date;
use Typecho\Db;
use Typecho\Router;
use Typecho\Widget\Exception;
use Widget\ActionInterface;
use Widget\Base;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Action extends Base implements ActionInterface
{
    /**
     * 路由 guestbook 的入口：输出前台留言页
     */
    public function render()
    {
        $messages = $this->db->fetchAll($this->db->select()
            ->from('table.guestbook')
            ->order('created', Db::SORT_DESC)
            ->limit(50));

        $this->response->setContentType('text/html');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'
            . _t('留言板') . ' - ' . htmlspecialchars($this->options->title) . '</title></head><body>';
        echo '<h1>' . _t('留言板') . '</h1>';

        echo '<form method="post" action="' . $this->security->getIndex('/action/guestbook?do=add') . '">'
            . '<p><input name="author" placeholder="' . _t('名字') . '" required></p>'
            . '<p><textarea name="text" rows="4" placeholder="' . _t('留言') . '" required></textarea></p>'
            . '<p><button type="submit">' . _t('提交') . '</button></p></form>';

        foreach ($messages as $message) {
            echo '<article><h3>' . htmlspecialchars($message['author']) . ' · '
                . (new Date($message['created']))->format('Y-m-d H:i') . '</h3>'
                . '<p>' . nl2br(htmlspecialchars($message['text'])) . '</p></article>';
        }

        echo '</body></html>';
    }

    /**
     * Action guestbook 的入口
     */
    public function action()
    {
        $this->on($this->request->is('do=add'))->add();
        $this->on($this->request->is('do=delete'))->delete();
        $this->response->redirect(Router::url('guestbook', null, $this->options->index));
    }

    /**
     * 访客提交留言
     */
    public function add()
    {
        $this->security->protect();

        $author = trim((string) $this->request->get('author'));
        $text = trim((string) $this->request->get('text'));

        if ($author === '' || $text === '') {
            throw new Exception(_t('名字和留言都不能为空'), 400);
        }

        $this->db->query($this->db->insert('table.guestbook')->rows([
            'author'  => $author,
            'text'    => $text,
            'ip'      => $this->request->getIp(),
            'created' => time(),
        ]));

        $this->response->goBack();
    }

    /**
     * 管理员删除留言
     */
    public function delete()
    {
        $this->user->pass('administrator');
        $this->security->protect();

        $ids = $this->request->filter('int')->getArray('id');
        if (!empty($ids)) {
            $deleted = $this->db->query($this->db->delete('table.guestbook')->where('id IN ?', $ids));
            Notice::alloc()->set(_t('删除了 %d 条留言', $deleted), 'success');
        }

        $this->response->goBack();
    }
}
```

几点说明：

- 继承 `\Widget\Base` 后可以直接使用 `$this->db`、`$this->options`、`$this->user`、`$this->security`，以及 `$this->request`、`$this->response`；
- `render()` 是路由的处理方法，直接输出了一个完整的 HTML 页面。插件页面也可以只输出 JSON：`$this->response->throwJson($data)`；
- **防止跨站请求伪造**：表单地址用 `$this->security->getIndex('/action/guestbook?do=add')` 生成，它会附带参数 `_`，值由站点密钥、当前登录用户和**当前页面地址**算出；处理请求时调用 `$this->security->protect()`，它用请求的来源页面（Referer）重新计算并比对，不一致时直接返回来源页面，不执行后面的代码；
- `$this->user->pass('administrator')` 检查当前用户是否属于管理员组：已登录但权限不够时返回 403，未登录时跳转（后台页面跳到登录页，其他情况跳到首页），见[权限分配表](../../guide/content/users.md#permission-table)；
- 抛出 `\Typecho\Widget\Exception` 时，Typecho 显示错误页，第二个参数是 HTTP 状态码；
- `Notice::alloc()->set()` 设置的提示显示在下一个后台页面的顶部（前台主题不显示它）；
- `goBack()` 返回来源页面，`redirect()` 跳转到指定地址。

## 后台面板

```php
Helper::addPanel(3, 'Guestbook/panel.php', _t('留言'), _t('管理留言'), 'administrator');
```

`addPanel($index, $fileName, $title, $subTitle, $level, $hidden = false, $addLink = '')` 的参数：

- `$index`：放在哪个菜单下：`1` 控制台、`2` 撰写、`3` 管理、`4` 设置，或 `addMenu()` 返回的值；
- `$fileName`：`插件目录名/文件名`，文件放在插件目录中；
- `$title`：菜单中显示的文字；`$subTitle`：页面标题；
- `$level`：最低用户组，`subscriber`、`contributor`、`editor` 或 `administrator`。权限不够的用户看不到菜单，直接访问会得到 403；
- `$hidden`：为 `true` 时不出现在菜单中，但仍然可以通过地址访问；
- `$addLink`：页面标题后面“新增”按钮的链接，留空则不显示。

面板的地址是 `admin/extending.php?panel=Guestbook/panel.php`，可以用 `Helper::url('Guestbook/panel.php')` 生成。只有通过 `addPanel()` 登记过的文件才能这样访问，其他文件返回 404。

需要一个新的顶级菜单时，先用 `addMenu()` 添加，再把面板放到它下面：

```php
$index = Helper::addMenu(_t('留言板'));
Helper::addPanel($index, 'Guestbook/panel.php', _t('全部留言'), _t('管理留言'), 'administrator');
```

删除时用 `removeMenu()` 的返回值作为 `removePanel()` 的第一个参数：

```php
$index = Helper::removeMenu(_t('留言板'));
Helper::removePanel($index, 'Guestbook/panel.php');
```

## panel.php

```php title="usr/plugins/Guestbook/panel.php"
<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}

include 'header.php';
include 'menu.php';

$db = \Typecho\Db::get();
$messages = $db->fetchAll($db->select()->from('table.guestbook')->order('created', \Typecho\Db::SORT_DESC));
?>
<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <form method="post" action="<?php $security->index('/action/guestbook?do=delete'); ?>">
                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('名字'); ?></th>
                                <th><?php _e('留言'); ?></th>
                                <th><?php _e('IP'); ?></th>
                                <th><?php _e('时间'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><input type="checkbox" name="id[]" value="<?php echo $message['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($message['author']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($message['text'])); ?></td>
                                    <td><?php echo htmlspecialchars($message['ip']); ?></td>
                                    <td><?php echo (new \Typecho\Date($message['created']))->format('Y-m-d H:i'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p><button type="submit" class="btn primary"><?php _e('删除选中的留言'); ?></button></p>
                </form>
            </div>
        </div>
    </div>
</main>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
```

面板文件由 `admin/extending.php` 载入，所以：

- 它和后台自带的页面一样，`include` 的 `header.php`、`menu.php` 等文件都相对于后台目录；
- 可以直接使用后台页面中已经定义好的变量：`$options`、`$user`、`$security`、`$request`、`$response`、`$menu`；
- 开头检查 `__TYPECHO_ADMIN__`，防止有人直接访问 `/usr/plugins/Guestbook/panel.php`。

## 检查

下面的地址以开启了地址重写、Typecho 安装在站点根目录为例。未开启地址重写时，地址是 `/index.php/guestbook/`；安装在子目录时，前面再加上子目录。启用 `Guestbook` 后：

1. 打开 `/guestbook/`，看到留言板和表单，提交一条留言后回到这个页面，留言出现在表单下方；
2. 后台“管理”菜单中出现“留言”，打开后列出刚才的留言，勾选并删除，顶部提示“删除了 1 条留言”；
3. 用非管理员帐号登录，菜单中没有“留言”，直接访问面板地址得到 403；
4. 禁用插件后，`/guestbook/` 返回 404，“留言”菜单消失；数据表保留，再次启用时留言还在。
