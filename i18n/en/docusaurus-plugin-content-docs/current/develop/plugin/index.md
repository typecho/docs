---
title: Plugin Development Overview
sidebar_position: 1
translation: machine
source_commit: fc55a1004f355202effc7d999ebe984ff3ec2cf2
---

# Plugin Development Overview

A plugin is a piece of PHP code placed under `usr/plugins/`. Once you activate it in the admin panel under "Dashboard → Plugins", it can change or extend almost any behavior of Typecho without modifying Typecho's own files. The theme decides what the pages look like; plugins decide what the site can do.

## What plugins can do

| What you want to do | What to use | See |
|---|---|---|
| Insert content before or after page output, modify the body, title or excerpt | Filter hooks, such as `contentEx` | [Using hooks](./hooks.md), [Common plugin patterns](./patterns.md#content-filter) |
| Do something when a post is published, a comment is submitted, or a user logs in | Notification hooks, such as `finishPublish`, `finishComment`, `loginSucceed` | [Hook list](../reference/hooks.md) |
| Take over a Typecho feature: the editor, attachment storage, Markdown parsing, password verification | trigger hooks, such as `richEditor`, `uploadHandle` | [Common plugin patterns](./patterns.md) |
| Provide new methods and properties to templates | Dynamic hooks `call<Method>`, `___<prop>` | [Using hooks](./hooks.md#dynamic-hooks) |
| Let administrators or users configure the plugin | `config()`, `personalConfig()` | [Plugin settings](./settings.md) |
| Add front-end pages | Routes | [Routes, actions and admin panels](./routes-actions.md) |
| Receive form submissions and Ajax requests | Action | Same as above |
| Add admin pages and menus | Admin panels | Same as above |
| Store your own data | The database, your own tables | [Database operations](./database.md) |
| Call external services, run slow tasks in the background | `Typecho\Http\Client`, async tasks | [HTTP requests and async tasks](./http-async.md) |

## Hooks and extension points

Typecho has two kinds of extension points.

**Hooks** are places reserved in Typecho's code. When execution reaches one, Typecho calls the callbacks registered by plugins in turn. Typecho 1.3.0 has 124 hooks with fixed names (combinations of a handle and a hook name), plus 3 kinds of dynamic hooks whose names are built at runtime. By location, they fall into:

- **Hooks in admin templates**: `navBar` in `admin/menu.php` (the navigation bar), `option` and `advanceOption` in `admin/write-post.php` (the sidebar of the writing page), `end` in `admin/footer.php`, and so on. Use them to output content in admin pages;
- **Hooks in the entry file**: `begin` and `end` in `index.php`, called at the start and end of every front-end request;
- **Hooks in widget classes**: front-end page output (`Widget\Archive`), content and comment filtering (`Widget\Base\Contents`, `Widget\Base\Comments`), publishing and deleting posts (`Widget\Contents\Post\Edit`), submitting comments (`Widget\Feedback`), uploading attachments (`Widget\Upload`), logging in (`Widget\User`), and so on.

**Registries** are a few tables stored in the database: the route table, the action table and the admin panel table. A plugin adds entries to them when it is activated and removes them when it is deactivated.

## Reading order

1. [Your first plugin](./hello-world.md): understand the bundled HelloWorld, then write and activate a plugin of your own;
2. [Plugin structure and lifecycle](./structure.md): file layout, the header comment, what happens on activation and deactivation;
3. [Using hooks](./hooks.md): registering callbacks, handle naming, priority, the three ways hooks are triggered;
4. [Plugin settings](./settings.md): the settings form, reading settings, personal settings;
5. Read as needed: [Routes, actions and admin panels](./routes-actions.md), [Database operations](./database.md), [HTTP requests and async tasks](./http-async.md);
6. [Common plugin patterns](./patterns.md): complete examples of content filtering, comment anti-spam, attachment storage, editors and login events;
7. [Releasing a plugin](./releasing.md).

References you will often look up while writing plugins:

- [Hook list](../reference/hooks.md): the type, parameters and call site of every hook;
- [Utils\Helper](../reference/helper.md): static methods commonly used by plugins;
- [Default routes and actions](../reference/routes-actions.md), [Database schema](../reference/database-schema.md);
- [Form elements](../reference/form-elements.md), [$this->options](../reference/options.md);
- [Legacy class name mapping](../reference/class-aliases.md): use it when reading old plugin code.

To migrate a plugin from an older version, see [Migrating themes and plugins to 1.3](../migrate-to-1-3.md). To translate a plugin's interface, see [Internationalizing themes and plugins](../i18n.md).

## Development environment

While developing plugins, turn on debug mode in `config.inc.php` so that you see the full error message and call stack when something goes wrong:

```php title="config.inc.php"
define('__TYPECHO_DEBUG__', true);
```

See [Debugging and troubleshooting](../../guide/operations/debugging.md#debug). Also keep in mind:

- If you change the registration code in `activate()`, you must deactivate and then reactivate the plugin in the admin panel for the change to take effect;
- If the server has PHP's OPcache enabled, changes to files may take a few seconds to take effect.
