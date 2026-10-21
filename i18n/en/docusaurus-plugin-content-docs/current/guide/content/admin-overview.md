---
title: Admin overview
sidebar_position: 1
translation: machine
source_commit: 5cf852b2f6ae6c6a96b12a34d8bd77f83e99bfa7
---

# Admin overview

By default the admin panel is at `site URL/admin/` (you can rename it; see [Custom admin path](../operations/admin-dir.md)). The top navigation has four menus: **Console, Write, Manage, Settings**. Every item requires a minimum role, and items above your role are hidden. Opening one of these pages directly by its URL returns "Forbidden" (HTTP 403).

## Menu map

From highest to lowest, the roles are: administrator > editor > contributor > subscriber > visitor. "Minimum role" means that this role and every role above it can use the item. For what each role can do, see the [permission table](./users.md#permission-table).

| Menu | Submenu | Page file | Minimum role | Notes |
|---|---|---|---|---|
| Console | Overview | `index.php` | subscriber | Admin home page (site overview) |
| | Profile | `profile.php` | subscriber | See [Profile](./profile.md) |
| | Plugins | `plugins.php` | administrator | Plugin management; plugin settings pages (`options-plugin.php?config=`) are not in the menu |
| | Appearance | `themes.php` | administrator | Theme management; "Edit current theme" (`theme-editor.php`) and "Theme settings" (`options-theme.php`) are not in the menu |
| | Backup | `backup.php` | administrator | See [Backup and restore](../operations/backup.md) |
| Write | Write Post | `write-post.php` | contributor | See [Writing posts](./posts.md) |
| | Create Page | `write-page.php` | editor | See [Pages](./pages.md) |
| Manage | Posts | `manage-posts.php` | contributor | Contributors only see their own posts |
| | Pages | `manage-pages.php` | editor | |
| | Comments | `manage-comments.php` | contributor | Contributors only see comments on their own posts; see [Managing and moderating comments](./comments.md) |
| | Categories | `manage-categories.php` | editor | See [Categories and tags](./categories-tags.md) |
| | Tags | `manage-tags.php` | editor | |
| | Files | `manage-medias.php` | editor | See [Attachments and file management](./attachments.md) |
| | Users | `manage-users.php` | administrator | See [Users, roles and registration](./users.md) |
| Settings | General | `options-general.php` | administrator | |
| | Comments | `options-discussion.php` | administrator | |
| | Reading | `options-reading.php` | administrator | |
| | Permalinks | `options-permalink.php` | administrator | |

The menus each role sees:

| Role | Console | Write | Manage | Settings |
|---|---|---|---|---|
| administrator | Overview, Profile, Plugins, Appearance, Backup | Write Post, Create Page | Posts, Pages, Comments, Categories, Tags, Files, Users | General, Comments, Reading, Permalinks |
| editor | Overview, Profile | Write Post, Create Page | Posts, Pages, Comments, Categories, Tags, Files | Not shown |
| contributor | Overview, Profile | Write Post | Posts, Comments | Not shown |
| subscriber | Overview, Profile | Not shown | Not shown | Not shown |

Plugins can add their own pages to these four menus (`extending.php?panel=`) and can also add new top-level menus; the minimum role of these pages is decided by the plugin and defaults to administrator.

## Pages not in the menu

The following pages are hidden normally and are only highlighted in the menu while they are open:

| Page | Minimum role | How to get there |
|---|---|---|
| Edit post `write-post.php?cid=` | contributor | Click a title in the post list |
| Edit page `write-page.php?cid=`, add child page `write-page.php?parent=` | editor | The page list |
| Edit file `media.php?cid=` | contributor | The edit icon in the editor's "Attachments" panel or in the file list |
| Add category `category.php`, edit category `category.php?mid=` | editor | The category list |
| Add user `user.php`, edit user `user.php?uid=` | administrator | The user list |
| Plugin settings, Edit current theme, Theme settings | administrator | The Plugins and Appearance pages |
| Upgrader `upgrade.php` | administrator | Only opens when the program files are newer than the database version; otherwise it redirects to the Console. See [Upgrading](../getting-started/upgrade.md) |
| Welcome `welcome.php` | subscriber | See the next section |

## Welcome page `welcome.php`

The first time an account logs in (it has never logged in before), the admin panel first redirects to the "Welcome" page, which has three shortcut links: "It is strongly recommended that you change your default password" (which goes to the password section of [Profile](./profile.md)), "Write your first post" (contributor and above) and "View my site" (administrator; other roles see "View site"). Click "Let me start right away »" to go to the Console.

Once the welcome page has been opened, the browser stores a `__typecho_first_run` cookie and the redirect does not happen again. An administrator who has logged in before is redirected straight to the Console when visiting `welcome.php`; other roles can open this page again at any time.

## Console home

"Site overview" shows "There are currently N posts, and N comments about you in N categories". The post count and the comment count only include **your own**, while the category count is site-wide. The shortcut links below it depend on the role:

- Contributor and above: "Write a new post", plus "Pending comments" and "Spam comments" with their counts when there are any;
- Administrator: also "Change appearance", "Manage plugins" and "System settings".

On the right are "Recently published posts", "Recent replies" and "Latest official posts". When a new version is available, administrators see an upgrade notice here; see [Upgrading](../getting-started/upgrade.md).

## Login and sessions

- The "Username or email" field in the login form accepts either your **username** or your **email**.
- An account can only be logged in at one place at a time: every login generates new login credentials, so any earlier login in another browser or on another device becomes invalid immediately and returns to the login page on its next action.
- Accounts in the visitor group can log in, but every admin page returns 403; see [Users, roles and registration](./users.md#visitor).
