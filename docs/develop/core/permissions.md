---
title: 权限模型
sidebar_position: 5
---

# 权限模型

Typecho 的权限只有一个维度：用户所属的角色（用户组）。本页说明角色的数值、`Widget\User::pass()` 怎样判断、每个后台页面要求的最低角色，以及在自己的代码中怎样做权限检查。站长视角的“角色 × 操作”对照见[权限分配表](../../guide/content/users.md#permission-table)。

## 角色与数值

角色定义在 `Widget\User::$groups`（`var/Widget/User.php`）：

```php
public array $groups = [
    'administrator' => 0,
    'editor'        => 1,
    'contributor'   => 2,
    'subscriber'    => 3,
    'visitor'       => 4
];
```

| 角色 | 中文名 | 数值 |
|---|---|---|
| `administrator` | 管理员 | 0 |
| `editor` | 编辑 | 1 |
| `contributor` | 贡献者 | 2 |
| `subscriber` | 关注者 | 3 |
| `visitor` | 访问者 | 4 |

数值越小权限越大，角色逐级包含。用户的角色保存在 `users` 表的 `group` 字段。没有单独的“权限项”表，也不能给某个用户单独授予某项权限。

## User::pass()

```php
public function pass(string $group, bool $return = false): bool
```

判断当前用户是否**至少**是 `$group` 这个角色：

| 情况 | `$return = true` | `$return = false`（默认） |
|---|---|---|
| 已登录，且自己角色的数值 ≤ `$group` 的数值 | 返回 `true` | 返回 `true` |
| 已登录，但角色不够 | 返回 `false` | 抛出代码为 403 的 `Typecho\Widget\Exception`（“禁止访问”） |
| 未登录 | 返回 `false` | 跳转：后台页面中跳到登录页（带 `referer` 参数，登录后返回）；前台跳到站点首页 |

要点：

- `$group` 必须是上表中的五个名字之一，写错时一律判为不通过；
- “后台页面中”指定义了常量 `__TYPECHO_ADMIN__` 的请求，即 `admin/*.php`。`/action/*` 请求经过 `index.php`，不属于后台页面，未登录时跳到首页；
- 抛出的 403 异常由错误页显示，不是主题的 404 页面；
- 是否登录由 `hasLogin()` 判断：用 Cookie 中的 `__typecho_uid` 和 `__typecho_authCode` 与 `users` 表核对，结果在本次请求中缓存。

## 后台页面要求的最低角色 {#admin-screens}

后台页面不经过路由。每个页面 `include 'common.php'` 时创建 `Widget\Menu`，它找到当前页面对应的菜单项，并用该项要求的角色调用 `$user->pass()`（`var/Widget/Menu.php`）。所以一个后台页面的访问权限就是它在菜单定义中的角色：

| 页面 | 最低角色 |
|---|---|
| `login.php`、`register.php` | 无（`visitor`，不检查） |
| `index.php`（概要）、`profile.php`（个人设置）、`welcome.php` | `subscriber` |
| `write-post.php`、`manage-posts.php`、`manage-comments.php`、`media.php?cid=` | `contributor` |
| `write-page.php`、`manage-pages.php`、`manage-categories.php`、`category.php`、`manage-tags.php`、`manage-medias.php` | `editor` |
| `plugins.php`、`options-plugin.php`、`themes.php`、`theme-editor.php`、`options-theme.php`、`backup.php`、`upgrade.php`、`manage-users.php`、`user.php`、`options-general.php`、`options-discussion.php`、`options-reading.php`、`options-permalink.php` | `administrator` |
| `extending.php?panel=…`（插件面板） | 插件在 `Helper::addPanel()` 中给出的 `$level` |

角色不够的用户访问这些页面得到 403，未登录的访客被跳转到登录页。访问者（`visitor`）虽然可以登录，但即使访问后台首页 `index.php` 也会得到 403。

菜单中不显示自己无权访问的项目，这同样由 `pass($角色, true)` 判断。页面内部还有更细的判断，例如 `manage-posts.php` 只有编辑及以上角色才显示“所有文章”切换，`write-post.php` 只有编辑及以上才能选择“公开度”。

## Action 自己检查权限

菜单只保护后台**页面**。表单提交到 `/action/<名字>` 时不经过 `Widget\Menu`，所以每个 Action 的 Widget 都在自己的 `action()` 或 `execute()` 中调用 `pass()`，例如：

| Action | 检查 |
|---|---|
| `contents-post-edit`、`contents-attachment-edit`、`comments-edit` | `pass('contributor')` |
| `contents-page-edit`、`metas-category-edit`、`metas-tag-edit` | `pass('editor')` |
| `users-edit`、`options-*`、`plugins-edit`、`themes-edit`、`backup`、`upgrade` | `pass('administrator')` |
| `users-profile` | `pass('subscriber')` |

角色之上还有按内容的判断：`Widget\Base\Contents::allow('edit')` 在“编辑及以上角色，或者是这篇内容的作者”时为真。贡献者能编辑自己的文章、不能编辑别人的文章，就是这样实现的。

Action 还要校验令牌防止跨站请求，这与权限无关，见[路由、Action 与后台面板](../plugin/routes-actions.md#action)。

## 在自己的代码中检查权限

### 在继承 Widget\Base 的类中

`Widget\Base` 已经准备好了 `$this->user`：

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

### 在模板、插件回调等其他地方

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

判断“是不是内容的作者”时，先确认已登录，再比较两者：`(int) $content->authorId === (int) $user->uid`。判断“能不能编辑这篇内容”时用内容对象的 `allow('edit')`，它对不是作者的编辑和管理员也返回 `true`，不能用来判断作者身份。

### 插件的后台面板

`Helper::addPanel()` 的第五个参数就是最低角色，打开面板时由 `Widget\Menu` 按这个角色检查，不需要在面板文件中再写一遍：

```php
// 菜单“管理”（索引 3）下添加一个编辑及以上可见的面板
\Utils\Helper::addPanel(3, 'Guestbook/panel.php', '留言', '管理留言', 'editor');
```

完整示例见[路由、Action 与后台面板](../plugin/routes-actions.md#后台面板)。插件个人设置面向哪些角色由插件自己声明，见[插件配置](../plugin/settings.md)。
