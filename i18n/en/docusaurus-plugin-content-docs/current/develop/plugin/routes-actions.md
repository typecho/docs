---
title: Routes, Actions and Admin Panels
sidebar_position: 6
translation: machine
source_commit: 14d78c21098657a0c0b6bdd0a0cc8530d6f1e55e
---

# Routes, Actions and Admin Panels

Hooks can only insert code into flows that Typecho already has. When a plugin needs its own pages or has to receive form submissions, it uses three other kinds of extension:

| Kind | URL | Purpose | Add / remove |
|---|---|---|---|
| Route | Custom, such as `/guestbook/` | Front-end pages | `Helper::addRoute()` / `removeRoute()` |
| Action | `/action/<name>` | Receiving forms and Ajax requests | `Helper::addAction()` / `removeAction()` |
| Admin panel | `admin/extending.php?panel=<plugin name>/<file name>` | Admin management pages | `Helper::addPanel()` / `removePanel()`, `addMenu()` / `removeMenu()` |

All of them are stored in the database (options `routingTable`, `actionTable`, `panelTable`), so you add them once in `activate()`, and you **must** remove them in `deactivate()`; Typecho does not clean them up automatically. `Helper` refers to `\Utils\Helper`; for all its methods, see [Utils\Helper](../reference/helper.md).

This page demonstrates the three kinds with a guestbook plugin, `Guestbook`: visitors leave messages on the front-end page `/guestbook/`, and administrators view and delete them under "Manage → Messages" in the admin panel.

```text
usr/plugins/Guestbook/
├── Plugin.php   registers the route, Action and panel; creates the table
├── Action.php   front-end page and form handling
└── panel.php    admin panel
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
 * Guestbook: visitors leave messages on the front-end /guestbook/ page; view and delete them under "Manage → Messages" in the admin panel
 *
 * @package Guestbook
 * @author Your Name
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
        Helper::addPanel(3, 'Guestbook/panel.php', _t('Messages'), _t('Manage messages'), 'administrator');
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
     * Creates the table, with one version for each of the three databases
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

`install()` creates the guestbook table; for how to write it, see [Database operations](./database.md#create-table).

## Routes {#routes}

```php
Helper::addRoute('guestbook', '/guestbook/', Action::class, 'render', 'index');
```

Parameters of `addRoute($name, $url, $widget, $action = null, $after = null)`:

- `$name`: the route name, unique across the site. You use it to remove the route and to generate URLs;
- `$url`: the path rule, which can contain placeholders such as `/guestbook/[page:digital]/`. For placeholder syntax, see [Default routes and actions](../reference/routes-actions.md#placeholders). You can read the matched value with `$this->request->get('page')`;
- `$widget`: the class that handles the request, usually extending `\Widget\Base`;
- `$action`: the method called after a match. Typecho first creates an object of the class (which calls its `execute()` method), then calls this method;
- `$after`: the route after which the new route is inserted. If omitted or not found, the route goes last.

When a request comes in, Typecho matches the routes one by one in routing table order and uses the first one that matches. If the class handling it throws an exception with code 404, Typecho goes on to try the following routes. The default routes include quite a few broad rules (for example `/[slug].html` for pages and `[permalink:string]/[type:alpha]` for comment submission), so a route placed last only gets its turn after every earlier route that matches has returned 404. That is why the example above places the guestbook route after `index` (the home page), so it is checked first.

To generate the URL of a route in code:

```php
$url = \Typecho\Router::url('guestbook', null, \Widget\Options::alloc()->index);
```

The second argument is an array that fills the placeholders, for example `['page' => 2]`. The third argument is the prefix; using `index` adapts automatically to whether URL rewriting is enabled.

## Action {#action}

```php
Helper::addAction('guestbook', Action::class);
```

Once added, requests sent to `/action/guestbook` (`/index.php/action/guestbook` when URL rewriting is not enabled) are handled by the `Action` class. This class must implement `\Widget\ActionInterface`, that is, have an `action()` method, which Typecho calls after creating the object.

- An action name may contain only letters, digits, `_` and `-`;
- For Typecho's own 23 actions (such as `login`, `upload`, `plugins-edit`), see [Default routes and actions](../reference/routes-actions.md#actions). An action added by a plugin with the same name **replaces** the core action, so prefix it with your plugin name;
- One action usually handles several operations, distinguished by request parameters. `$this->on($condition)->method()` calls the method only when the condition is true; this is Typecho's own idiom.

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
     * Entry point of the guestbook route: outputs the front-end guestbook page
     */
    public function render()
    {
        $messages = $this->db->fetchAll($this->db->select()
            ->from('table.guestbook')
            ->order('created', Db::SORT_DESC)
            ->limit(50));

        $this->response->setContentType('text/html');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'
            . _t('Guestbook') . ' - ' . htmlspecialchars($this->options->title) . '</title></head><body>';
        echo '<h1>' . _t('Guestbook') . '</h1>';

        echo '<form method="post" action="' . $this->security->getIndex('/action/guestbook?do=add') . '">'
            . '<p><input name="author" placeholder="' . _t('Name') . '" required></p>'
            . '<p><textarea name="text" rows="4" placeholder="' . _t('Message') . '" required></textarea></p>'
            . '<p><button type="submit">' . _t('Submit') . '</button></p></form>';

        foreach ($messages as $message) {
            echo '<article><h3>' . htmlspecialchars($message['author']) . ' · '
                . (new Date($message['created']))->format('Y-m-d H:i') . '</h3>'
                . '<p>' . nl2br(htmlspecialchars($message['text'])) . '</p></article>';
        }

        echo '</body></html>';
    }

    /**
     * Entry point of the guestbook Action
     */
    public function action()
    {
        $this->on($this->request->is('do=add'))->add();
        $this->on($this->request->is('do=delete'))->delete();
        $this->response->redirect(Router::url('guestbook', null, $this->options->index));
    }

    /**
     * A visitor submits a message
     */
    public function add()
    {
        $this->security->protect();

        $author = trim((string) $this->request->get('author'));
        $text = trim((string) $this->request->get('text'));

        if ($author === '' || $text === '') {
            throw new Exception(_t('Name and message are both required'), 400);
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
     * The administrator deletes messages
     */
    public function delete()
    {
        $this->user->pass('administrator');
        $this->security->protect();

        $ids = $this->request->filter('int')->getArray('id');
        if (!empty($ids)) {
            $deleted = $this->db->query($this->db->delete('table.guestbook')->where('id IN ?', $ids));
            Notice::alloc()->set(_t('Deleted %d message(s)', $deleted), 'success');
        }

        $this->response->goBack();
    }
}
```

A few notes:

- By extending `\Widget\Base`, you can use `$this->db`, `$this->options`, `$this->user`, `$this->security`, as well as `$this->request` and `$this->response` directly;
- `render()` is the route's handler method and outputs a complete HTML page directly. A plugin page can also output only JSON: `$this->response->throwJson($data)`;
- **Preventing cross-site request forgery**: generate the form URL with `$this->security->getIndex('/action/guestbook?do=add')`. It appends a parameter `_` whose value is computed from the site secret, the currently logged-in user and the **current page URL**. When handling the request, call `$this->security->protect()`; it recomputes the value from the request's referring page (Referer) and compares them. If they differ, it returns straight to the referring page without running the code that follows;
- `$this->user->pass('administrator')` checks whether the current user belongs to the administrator group: a logged-in user without enough permission gets 403; a user who is not logged in is redirected (to the login page for admin pages, otherwise to the home page). See the [permission table](../../guide/content/users.md#permission-table);
- When you throw `\Typecho\Widget\Exception`, Typecho shows an error page; the second argument is the HTTP status code;
- A notice set with `Notice::alloc()->set()` is shown at the top of the next admin page (front-end themes do not show it);
- `goBack()` returns to the referring page; `redirect()` redirects to the given URL.

## Admin panels {#admin-panels}

```php
Helper::addPanel(3, 'Guestbook/panel.php', _t('Messages'), _t('Manage messages'), 'administrator');
```

Parameters of `addPanel($index, $fileName, $title, $subTitle, $level, $hidden = false, $addLink = '')`:

- `$index`: which menu to put it under: `1` Dashboard, `2` Write, `3` Manage, `4` Settings, or a value returned by `addMenu()`;
- `$fileName`: `<plugin directory name>/<file name>`; the file lives in the plugin directory;
- `$title`: the text shown in the menu; `$subTitle`: the page title;
- `$level`: the minimum user group: `subscriber`, `contributor`, `editor` or `administrator`. Users without enough permission do not see the menu item and get 403 if they access it directly;
- `$hidden`: when `true`, the panel does not appear in the menu but can still be accessed by its URL;
- `$addLink`: the link of the "Add new" button after the page title; leave it empty to hide the button.

The panel URL is `admin/extending.php?panel=Guestbook/panel.php`, which you can generate with `Helper::url('Guestbook/panel.php')`. Only files registered with `addPanel()` can be accessed this way; other files return 404.

When you need a new top-level menu, add it with `addMenu()` first, then put the panel under it:

```php
$index = Helper::addMenu(_t('Guestbook'));
Helper::addPanel($index, 'Guestbook/panel.php', _t('All messages'), _t('Manage messages'), 'administrator');
```

When removing, use the return value of `removeMenu()` as the first argument of `removePanel()`:

```php
$index = Helper::removeMenu(_t('Guestbook'));
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
                                <th><?php _e('Name'); ?></th>
                                <th><?php _e('Message'); ?></th>
                                <th><?php _e('IP'); ?></th>
                                <th><?php _e('Time'); ?></th>
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
                    <p><button type="submit" class="btn primary"><?php _e('Delete selected messages'); ?></button></p>
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

The panel file is loaded by `admin/extending.php`, so:

- Just like the built-in admin pages, files such as `header.php` and `menu.php` that it `include`s are relative to the admin directory;
- You can use the variables already defined for admin pages directly: `$options`, `$user`, `$security`, `$request`, `$response`, `$menu`;
- The check for `__TYPECHO_ADMIN__` at the top prevents anyone from accessing `/usr/plugins/Guestbook/panel.php` directly.

## Checking

After enabling `Guestbook` (the URLs below assume URL rewriting is on and Typecho is installed at the site root; without URL rewriting the address is `/index.php/guestbook/`, and in a subdirectory installation prefix it with the subdirectory):

1. Open `/guestbook/`: you see the guestbook and the form. After you submit a message you return to this page, and the message appears below the form;
2. "Messages" appears in the admin "Manage" menu. Open it to list the message you just posted; select it and delete it, and the top of the page shows "Deleted 1 message(s)";
3. Log in with a non-administrator account: there is no "Messages" in the menu, and accessing the panel URL directly returns 403;
4. After you disable the plugin, `/guestbook/` returns 404 and the "Messages" menu item disappears; the table is kept, and the messages are still there when you enable the plugin again.
