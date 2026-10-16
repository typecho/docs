---
title: Permission Model
sidebar_position: 5
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Permission Model

Typecho permissions have only one dimension: the role (user group) a user belongs to. This page explains the role values, how `Widget\User::pass()` decides, the minimum role each admin page requires, and how to check permissions in your own code. For the "role × operation" table from the site owner's point of view, see the [permission table](/guide/content/users#permission-table).

## Roles and values

Roles are defined in `Widget\User::$groups` (`var/Widget/User.php`):

```php
public array $groups = [
    'administrator' => 0,
    'editor'        => 1,
    'contributor'   => 2,
    'subscriber'    => 3,
    'visitor'       => 4
];
```

| Role | Name | Value |
|---|---|---|
| `administrator` | Administrator | 0 |
| `editor` | Editor | 1 |
| `contributor` | Contributor | 2 |
| `subscriber` | Subscriber | 3 |
| `visitor` | Visitor | 4 |

The smaller the value, the greater the permission; each role includes the ones below it. A user's role is stored in the `group` field of the `users` table. There is no separate table of "permission items", and you cannot grant an individual permission to a single user.

## User::pass()

```php
public function pass(string $group, bool $return = false): bool
```

Checks whether the current user is **at least** the role `$group`:

| Situation | `$return = true` | `$return = false` (default) |
|---|---|---|
| Logged in, and the value of the user's role ≤ the value of `$group` | Returns `true` | Returns `true` |
| Logged in, but the role is not high enough | Returns `false` | Throws a `Typecho\Widget\Exception` with code 403 ("Forbidden") |
| Not logged in | Returns `false` | Redirects: on admin pages, to the login page (with a `referer` parameter, returning after login); on the front end, to the site home page |

Key points:

- `$group` must be one of the five names in the table above; any misspelling always fails the check;
- "On admin pages" means requests that define the constant `__TYPECHO_ADMIN__`, that is, `admin/*.php`. `/action/*` requests go through `index.php` and are not admin pages, so users who are not logged in are redirected to the home page;
- The 403 exception thrown is displayed by the error page, not by the theme's 404 page;
- Whether a user is logged in is decided by `hasLogin()`: it checks `__typecho_uid` and `__typecho_authCode` in the cookies against the `users` table and caches the result for the current request.

## Minimum role required by admin pages {#admin-screens}

Admin pages do not go through the router. When each page does `include 'common.php'`, `Widget\Menu` is created; it finds the menu item for the current page and calls `$user->pass()` with the role that item requires (`var/Widget/Menu.php`). So the access permission of an admin page is the role given for it in the menu definition:

| Page | Minimum role |
|---|---|
| `login.php`, `register.php` | None (`visitor`, not checked) |
| `index.php` (overview), `profile.php` (profile settings), `welcome.php` | `subscriber` |
| `write-post.php`, `manage-posts.php`, `manage-comments.php`, `media.php?cid=` | `contributor` |
| `write-page.php`, `manage-pages.php`, `manage-categories.php`, `category.php`, `manage-tags.php`, `manage-medias.php` | `editor` |
| `plugins.php`, `options-plugin.php`, `themes.php`, `theme-editor.php`, `options-theme.php`, `backup.php`, `upgrade.php`, `manage-users.php`, `user.php`, `options-general.php`, `options-discussion.php`, `options-reading.php`, `options-permalink.php` | `administrator` |
| `extending.php?panel=…` (plugin panels) | The `$level` the plugin gives in `Helper::addPanel()` |

Users whose role is not high enough get a 403 on these pages, and visitors who are not logged in are redirected to the login page. A visitor (`visitor`) can log in, but gets a 403 even on `index.php`.

Menu items the user has no access to are not shown; this is also decided by `pass($role, true)`. Pages also make finer checks internally; for example, `manage-posts.php` shows the "All posts" (所有文章) toggle only to editors and above, and on `write-post.php` only editors and above can choose the "Visibility" (公开度).

## Actions check permissions themselves

The menu protects only admin **pages**. A form submitted to `/action/<name>` does not go through `Widget\Menu`, so the widget of every action calls `pass()` in its own `action()` or `execute()`, for example:

| Action | Check |
|---|---|
| `contents-post-edit`, `contents-attachment-edit`, `comments-edit` | `pass('contributor')` |
| `contents-page-edit`, `metas-category-edit`, `metas-tag-edit` | `pass('editor')` |
| `users-edit`, `options-*`, `plugins-edit`, `themes-edit`, `backup`, `upgrade` | `pass('administrator')` |
| `users-profile` | `pass('subscriber')` |

On top of roles there are per-content checks: `Widget\Base\Contents::allow('edit')` is true for "editors and above, or the author of this content". This is how contributors can edit their own posts but not other people's.

Actions also have to validate a token to prevent cross-site requests. This is unrelated to permissions; see [Routes, actions and admin panels](/develop/plugin/routes-actions#action).

## Checking permissions in your own code

### In a class that extends Widget\Base

`Widget\Base` has already prepared `$this->user`:

```php
namespace TypechoPlugin\Guestbook;

use Widget\ActionInterface;
use Widget\Base\Contents;

class Action extends Contents implements ActionInterface
{
    public function action()
    {
        // 编辑以下的角色得到 403，未登录的请求跳到首页
        $this->user->pass('editor');
        // 校验令牌
        $this->security->protect();

        // ……
    }
}
```

### In templates, plugin callbacks and elsewhere

```php
$user = \Widget\User::alloc();

if ($user->pass('editor', true)) {
    // 编辑及以上可见的内容
}

if ($user->hasLogin()) {
    echo $user->screenName;   // 当前用户的昵称
    echo $user->group;        // 角色名，如 contributor
    echo $user->uid;          // 用户 ID
}
```

To check "is this the author of the content", compare the content's `authorId` with `$user->uid`, or simply use the content object's `allow('edit')`.

### Admin panels of plugins

The fifth argument of `Helper::addPanel()` is the minimum role. When the panel is opened, `Widget\Menu` checks this role, so you do not need to repeat the check in the panel file:

```php
// 菜单“管理”（索引 3）下添加一个编辑及以上可见的面板
\Utils\Helper::addPanel(3, 'Guestbook/panel.php', '留言', '管理留言', 'editor');
```

For a complete example, see [Routes, actions and admin panels](/develop/plugin/routes-actions#后台面板). Which roles a plugin's personal settings are aimed at is declared by the plugin itself; see [Plugin settings](/develop/plugin/settings).
