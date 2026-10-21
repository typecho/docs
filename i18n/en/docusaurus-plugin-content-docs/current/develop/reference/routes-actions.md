---
title: Default Routes and Actions
sidebar_position: 9
translation: machine
source_commit: 342fdf14c307f4efef7d31add7e5c261f14c71b4
---

# Default Routes and Actions

The routing table determines Typecho's front-end URLs, and actions handle form submissions and Ajax requests. For how a plugin adds routes and actions, see [Routes, Actions and Admin Panels](../plugin/routes-actions.md).

## Default routes

After installation the routing table contains 25 routes, matched in the order of the table below:

| Route name | Default path | Handler class | Method | Page |
|---|---|---|---|---|
| `index` | `/` | `\Widget\Archive` | `render` | Home page |
| `archive` | `/blog/` | `\Widget\Archive` | `render` | Post list page when the home page is set to a page |
| `do` | `/action/[action:alpha]` | `\Widget\Action` | `action` | All actions (see below) |
| `post` | `/archives/[cid:digital]/` | `\Widget\Archive` | `render` | Post |
| `attachment` | `/attachment/[cid:digital]/` | `\Widget\Archive` | `render` | Attachment |
| `category` | `/category/[slug]/` | `\Widget\Archive` | `render` | Category |
| `tag` | `/tag/[slug]/` | `\Widget\Archive` | `render` | Tag |
| `author` | `/author/[uid:digital]/` | `\Widget\Archive` | `render` | Author |
| `search` | `/search/[keywords]/` | `\Widget\Archive` | `render` | Search results |
| `index_page` | `/page/[page:digital]/` | `\Widget\Archive` | `render` | Home page pagination |
| `archive_page` | `/blog/page/[page:digital]/` | `\Widget\Archive` | `render` | Post list page pagination |
| `category_page` | `/category/[slug]/[page:digital]/` | `\Widget\Archive` | `render` | Category pagination |
| `tag_page` | `/tag/[slug]/[page:digital]/` | `\Widget\Archive` | `render` | Tag pagination |
| `author_page` | `/author/[uid:digital]/[page:digital]/` | `\Widget\Archive` | `render` | Author pagination |
| `search_page` | `/search/[keywords]/[page:digital]/` | `\Widget\Archive` | `render` | Search results pagination |
| `archive_year` | `/[year:digital:4]/` | `\Widget\Archive` | `render` | Yearly archive |
| `archive_month` | `/[year:digital:4]/[month:digital:2]/` | `\Widget\Archive` | `render` | Monthly archive |
| `archive_day` | `/[year:digital:4]/[month:digital:2]/[day:digital:2]/` | `\Widget\Archive` | `render` | Daily archive |
| `archive_year_page` | `/[year:digital:4]/page/[page:digital]/` | `\Widget\Archive` | `render` | Yearly archive pagination |
| `archive_month_page` | `/[year:digital:4]/[month:digital:2]/page/[page:digital]/` | `\Widget\Archive` | `render` | Monthly archive pagination |
| `archive_day_page` | `/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/` | `\Widget\Archive` | `render` | Daily archive pagination |
| `comment_page` | `[permalink:string]/comment-page-[commentPage:digital]` | `\Widget\CommentPage` | `action` | Comment pagination |
| `feed` | `/feed[feed:string:0]` | `\Widget\Feed` | `render` | Feeds, such as `/feed/`, `/feed/atom/`, `/feed/category/tech/` |
| `feedback` | `[permalink:string]/[type:alpha]` | `\Widget\Feedback` | `action` | Submitting comments and trackbacks, such as `/archives/1/comment` |
| `page` | `/[slug].html` | `\Widget\Archive` | `render` | Page |

- You can change the paths of the four routes `post`, `page`, `category` and `category_page` under "Settings → Permalinks" (see [Permalink Settings](../../guide/settings/permalink.md));
- The `/index.php` prefix in URLs (when URL rewriting is not enabled) is not part of the route path;
- To generate the URL of a route, use `\Typecho\Router::url('routeName', ['param' => $value], $prefix)`.

### Matching rules

- Routes are tried one by one in the order of the table, and the first matching route is used;
- When the handler class throws an exception with code 404 (for example, `/archives/9999/` has no such post), matching continues with the following routes; the 404 page is shown only when none of them match;
- The trailing `/` of a path is optional: both `/category/tech` and `/category/tech/` match `category`;
- The values of the matched placeholders are passed to the handler class as request parameters; read them with `$this->request->get('name')`.

## Placeholders {#placeholders}

In a path, `[name]` or `[name:type...]` stands for a variable segment:

| Syntax | Matches | Example |
|---|---|---|
| `[name]` | One or more characters **not including** `/`, same as `[name:char]` | `/tag/[slug]/` |
| `[name:type]` | One or more characters of the type | `/archives/[cid:digital]/` |
| `[name:type:N]` | Exactly N characters of the type; an `N` of `0` means zero or more | `/[year:digital:4]/`, `/feed[feed:string:0]` |
| `[name:type:min:max]` | Between the minimum and maximum number of characters of the type | `/[id:digital:1:6]/` |

| Type | Each character can be |
|---|---|
| `string` | Any character, including `/` |
| `char` | Any character except `/` |
| `digital` | Digits `0-9` |
| `alpha` | Letters, digits, `_`, `-` |
| `alphaslash` | Letters, digits, `_`, `-`, `/` |
| `split` | Multiple segments separated by `/`, at least two, such as `a/b`, `a/b/c`; with a count, `[name:split:N]` means exactly N+1 segments |

## Core actions {#actions}

Every action's URL is `/action/<name>`, and the `do` route hands it to `\Widget\Action` for dispatch. The table below lists the 23 actions that ship with Typecho; an action added by a plugin through `Helper::addAction()` with the same name **replaces** the core action.

| Name | Handler class | Operations handled |
|---|---|---|
| `ajax` | `\Widget\Ajax` | Admin Ajax requests: `do=checkVersion` checks for a new version, `do=feed` reads the official news, `do=editorResize` saves the editor height, `do=remoteCallback` |
| `login` | `\Widget\Login` | Admin login form |
| `logout` | `\Widget\Logout` | Log out |
| `register` | `\Widget\Register` | User registration |
| `upgrade` | `\Widget\Upgrade` | Upgrade (POST) |
| `upload` | `\Widget\Upload` | Upload attachments; `do=modify&cid=` replaces an attachment |
| `service` | `\Widget\Service` | Requests the site sends to itself: `do=async` async tasks, `do=ping` sends trackbacks |
| `xmlrpc` | `\Widget\XmlRpc` | XML-RPC interface |
| `comments-edit` | `\Widget\Comments\Edit` | Manage comments: `do=` `waiting` / `spam` / `approved` / `delete` / `delete-spam` / `get` / `edit` / `reply` |
| `contents-page-edit` | `\Widget\Contents\Page\Edit` | Pages: `do=` `publish` / `save` / `delete` / `deleteDraft` / `mark` / `sort` |
| `contents-post-edit` | `\Widget\Contents\Post\Edit` | Posts: `do=` `publish` / `save` / `delete` / `deleteDraft` / `mark` |
| `contents-attachment-edit` | `\Widget\Contents\Attachment\Edit` | Attachments: `do=` `update` / `delete` / `clear` |
| `metas-category-edit` | `\Widget\Metas\Category\Edit` | Categories: `do=` `insert` / `update` / `delete` / `merge` / `sort` / `refresh` / `default` |
| `metas-tag-edit` | `\Widget\Metas\Tag\Edit` | Tags: `do=` `insert` / `update` / `delete` / `merge` / `refresh` |
| `options-discussion` | `\Widget\Options\Discussion` | Save discussion settings |
| `options-general` | `\Widget\Options\General` | Save general settings |
| `options-permalink` | `\Widget\Options\Permalink` | Save permalink settings |
| `options-reading` | `\Widget\Options\Reading` | Save reading settings |
| `plugins-edit` | `\Widget\Plugins\Edit` | Plugins: `activate=` activates, `deactivate=` deactivates, `config=` saves settings |
| `themes-edit` | `\Widget\Themes\Edit` | Themes: `change=` switches, `edit&theme=` saves a template file, `config=` saves theme settings |
| `users-edit` | `\Widget\Users\Edit` | Users: `do=` `insert` / `update` / `delete` |
| `users-profile` | `\Widget\Users\Profile` | Profile: `do=` `profile` / `options` / `password` / `personal` (plugins' personal settings) |
| `backup` | `\Widget\Backup` | Backup: `do=export` exports, `do=import` restores |

Except for `ajax`, `service` and `xmlrpc`, every action checks the token parameter `_` in the URL through `$this->security->protect()`, so you cannot call an action through a URL you put together yourself; generate the URL with `$security->getIndex('/action/<name>')`. For the required permissions, see the [permission table](../../guide/content/users.md#permission-table).
