---
title: Admin overview
sidebar_position: 1
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Admin overview

By default the admin panel is at `site URL/admin/` (you can rename it; see [Custom admin path](../operations/admin-dir.md)). The top navigation has four menus: **Dashboard (控制台), Write (撰写), Manage (管理), Settings (设置)**. Every item has a minimum role, and menu items your role is not allowed to use are not shown; if you open such a page by typing its address directly, you get "Forbidden" (禁止访问, HTTP 403).

## Menu map

From highest to lowest, the roles are: administrator > editor > contributor > subscriber > visitor. "Minimum role" means that this role and every role above it can use the item. For what each role can do, see the [permission table](./users.md#permission-table).

| Menu | Submenu | Page file | Minimum role | Notes |
|---|---|---|---|---|
| Dashboard | Overview (概要) | `index.php` | subscriber | Admin home page (site overview) |
| | Profile (个人设置) | `profile.php` | subscriber | See [Profile](./profile.md) |
| | Plugins (插件) | `plugins.php` | administrator | Plugin management; plugin settings pages (`options-plugin.php?config=`) are not in the menu |
| | Appearance (外观) | `themes.php` | administrator | Theme management; "Edit current theme" (编辑当前外观, `theme-editor.php`) and "Theme settings" (设置外观, `options-theme.php`) are not in the menu |
| | Backup (备份) | `backup.php` | administrator | See [Backup and restore](../operations/backup.md) |
| Write | Write Post (撰写文章) | `write-post.php` | contributor | See [Writing posts](./posts.md) |
| | Create Page (创建页面) | `write-page.php` | editor | See [Pages](./pages.md) |
| Manage | Posts (文章) | `manage-posts.php` | contributor | Contributors only see their own posts |
| | Pages (独立页面) | `manage-pages.php` | editor | |
| | Comments (评论) | `manage-comments.php` | contributor | Contributors only see comments on their own posts; see [Managing and moderating comments](./comments.md) |
| | Categories (分类) | `manage-categories.php` | editor | See [Categories and tags](./categories-tags.md) |
| | Tags (标签) | `manage-tags.php` | editor | |
| | Files (文件) | `manage-medias.php` | editor | See [Attachments and file management](./attachments.md) |
| | Users (用户) | `manage-users.php` | administrator | See [Users, roles and registration](./users.md) |
| Settings | General (基本) | `options-general.php` | administrator | |
| | Comments (评论) | `options-discussion.php` | administrator | |
| | Reading (阅读) | `options-reading.php` | administrator | |
| | Permalinks (永久链接) | `options-permalink.php` | administrator | |

The menus each role sees:

| Role | Dashboard | Write | Manage | Settings |
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
| Edit file `media.php?cid=` | contributor | The edit icon in the editor's "Attachments" (附件) panel or in the file list |
| Add category `category.php`, edit category `category.php?mid=` | editor | The category list |
| Add user `user.php`, edit user `user.php?uid=` | administrator | The user list |
| Plugin settings, Edit current theme, Theme settings | administrator | The Plugins and Appearance pages |
| Upgrader `upgrade.php` | administrator | Only opens when the program files are newer than the database version; otherwise it redirects to the dashboard. See [Upgrading](../getting-started/upgrade.md) |
| Welcome `welcome.php` | subscriber | See the next section |

## Welcome page `welcome.php`

The first time an account logs in (it has never logged in before), the admin panel first redirects to the "Welcome" (欢迎使用) page, which has three shortcut links: "It is strongly recommended that you change your default password" (强烈建议更改你的默认密码, which goes to the password section of [Profile](./profile.md)), "Write your first post" (撰写第一篇日志, contributor and above) and "View my site" (查看我的站点, administrator; other roles see "View site" (查看网站)). Click "Let me start right away »" (让我直接开始使用吧 ») to go to the dashboard.

Once the welcome page has been opened, the browser stores a `__typecho_first_run` cookie and the redirect does not happen again. An administrator who has logged in before and visits `welcome.php` is sent straight back to the dashboard; other roles can open this page again at any time.

## Dashboard home

"Site overview" (网站概要) shows "There are currently N posts, and N comments about you in N categories" (目前有 N 篇文章, 并有 N 条关于你的评论在 N 个分类中). The post count and the comment count only include **your own**, while the category count is site-wide. The shortcut links below it depend on the role:

- contributor and above: write a new post; when there are pending or spam comments, "Pending comments" (待审核评论) and "Spam comments" (垃圾评论) with their counts;
- administrator: additionally change theme, manage plugins, and system settings.

On the right are "Recently published posts" (最近发布的文章), "Recent replies" (最近得到的回复) and "Latest official posts" (官方最新日志). When a new version is available, administrators see an upgrade notice here; see [Upgrading](../getting-started/upgrade.md).

## Login and sessions

- In the login form you can enter a **username or email** ("Username or email", 用户名或邮箱).
- An account can only be logged in at one place at a time: every login generates new login credentials, so any earlier login in another browser or on another device becomes invalid immediately and returns to the login page on its next action.
- Accounts in the visitor group can log in, but every admin page returns 403; see [Users, roles and registration](./users.md#visitor).
