---
title: Migrating Themes and Plugins to 1.3
sidebar_position: 2
translation: machine
source_commit: 342fdf14c307f4efef7d31add7e5c261f14c71b4
---

# Migrating Themes and Plugins to 1.3

This page is for theme and plugin authors. It lists what to check when you migrate code written for Typecho 1.1 / 1.2 to 1.3. For the changes that matter to site owners, see [What's new and changed in 1.3](../guide/getting-started/whats-new-1-3.md).

1.3 requires PHP 7.4 or later and uses type declarations extensively internally. Most old code still runs, but new code should use the approaches recommended on this page.

## Class names: `Widget_*` → `\Widget\*::alloc()`

Old code gets a widget through a class name string:

```php
<?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=5')->parse('<li>{title}</li>'); ?>
<?php Typecho_Widget::widget('Widget_Metas_Category_List')->listCategories(); ?>
<?php $options = Typecho_Widget::widget('Widget_Options'); ?>
```

In 1.3 you use the namespaced class name plus `alloc()`:

```php
<?php \Widget\Contents\Post\Recent::alloc('pageSize=5')->parse('<li>{title}</li>'); ?>
<?php \Widget\Metas\Category\Rows::alloc()->listCategories(); ?>
<?php $options = \Widget\Options::alloc(); ?>
```

- The old code **still runs** in 1.3: `$this->widget()` and `Typecho_Widget::widget()` still exist, the autoloader maps old class names to the new classes, and renamed classes such as `Widget_Metas_Category_List` are handled by the alias table. For the full mapping, see the [legacy class name table](./reference/class-aliases.md).
- A few 1.1 classes no longer exist (such as `Typecho_Http_Client_Adapter_*` and `Typecho_Db_Adapter_Exception`); code that uses them fails with "class not found".
- The new class names start with `\`. In theme templates (which have no `namespace` declaration) you can write either `\Widget\...` or `Widget\...`; inside a plugin's namespace you must write the leading `\` or import the class with `use`.
- Like `widget()`, `alloc()` caches by class name: calling the same class a second time returns the instance from the first call. When you need several instances of the same class, use `allocWithAlias('alias', parameters)`; see [Calling widgets in templates](./theme/widgets.md).
- The plugin main class should switch to the namespaced form:

  ```php
  <?php
  namespace TypechoPlugin\HelloWorld;

  use Typecho\Plugin\PluginInterface;
  use Typecho\Widget\Helper\Form;

  class Plugin implements PluginInterface
  {
      // ...
  }
  ```

  The old `class HelloWorld_Plugin implements Typecho_Plugin_Interface` is still recognized (it is mapped automatically to `\TypechoPlugin\HelloWorld\Plugin`).

## Settings are stored as JSON

Starting with 1.3, plugin settings (`plugin:<plugin name>`), theme settings (`theme:<theme name>`), the list of enabled plugins (`plugins`), `actionTable`, `panelTable`, the routing table (`routingTable`) and attachment information (the `text` field of the attachment content) are all stored as **JSON**; previously they used PHP `serialize()`.

- Reading options through Typecho's API is not affected: `$this->options->plugin('name')`, `$this->options-><theme field>` and so on recognize both formats automatically. An attachment's `$this->attachment` is parsed only as JSON; old data is converted by the upgrade program.
- Code that **reads or writes the database directly** must change: read with `json_decode($value, true)` and write with `json_encode()`; do not call `unserialize()` / `serialize()` on these fields any more.
- When upgrading from 1.2 or earlier, the upgrade program converts existing attachment information and the options above from serialize to JSON. A plugin's personal settings (`_plugin:<plugin name>`) are not converted; reading them remains compatible with the old format.
- Arrays that a plugin writes with `Helper::setOption()` are stored as JSON.

## Triggering hooks: `Plugin::__call` → `call()` / `filter()`

When a plugin or theme provides its own hook, the old approach called a "method" directly:

```php
$result = \Typecho\Plugin::factory('MyPlugin')->beforeOutput($data);
```

In 1.3 this magic call (`Typecho\Plugin::__call()`) is marked as deprecated. Use the following instead:

```php
// Runs every callback and returns the last callback's return value
$result = \Typecho\Plugin::factory('MyPlugin')->call('beforeOutput', $data);

// Passes the value through each callback in turn and returns the final value
$data = \Typecho\Plugin::factory('MyPlugin')->filter('beforeOutput', $data);
```

- `call($component, ...$args)`: calls all callbacks registered on the hook;
- `filter($component, $value, ...$args)`: passes `$value` to each callback in turn, uses each callback's return value as the next callback's `$value`, and returns the final result;
- To find out whether any callback ran, pass a variable to `trigger()` before the call: `Plugin::factory('X')->trigger($plugged)->call('hook', ...)`; afterwards `$plugged` is `true` if a plugin took over;
- The way you **register** callbacks is unchanged: `\Typecho\Plugin::factory('Widget\Archive')->header = [Plugin::class, 'header'];`. The handle can be written as either `Widget\Archive` or `Widget_Archive`.

## Type declarations can break subclasses

1.3 adds type declarations to the properties and methods of core classes. Code that extends a core class (for example, extending `\Widget\Base\Contents` or `\Widget\Archive` to write your own widget) and redeclares a property or method that the parent class already has must match the parent's types; otherwise a fatal error occurs as soon as the class is loaded:

```text
PHP Fatal error: Type of MyContents::$options must be Widget\Options (as in class Widget\Base)
PHP Fatal error: Declaration of MyContents::___permalink() must be compatible with Widget\Base\Contents::___permalink(): string
```

What to check:

- A property redeclared in a subclass must have the same type as in the parent class (for example `protected \Widget\Options $options;`), or simply do not redeclare it;
- An overridden method must have parameter and return types that are the same as or compatible with the parent's, for example `protected function ___permalink(): string`;
- A typed property of the parent class cannot be read before it is assigned; otherwise `must not be accessed before initialization` is thrown, for example when you read the archive slug of `Widget\Archive` on the home page. See [Page context and is()](./theme/context.md).

## archive.php removed from the default theme

The 1.3 default theme (`usr/themes/default`) no longer has `archive.php`; list pages for categories, tags, search and so on are output by `index.php`. For themes derived from the default theme:

- Themes that ship their own `archive.php` are not affected; Typecho still uses it first according to the [template hierarchy](./theme/template-hierarchy.md);
- To stay in line with the new default theme, you can delete `archive.php` and use `$this->is('index')` in `index.php` to tell the home page apart from other list pages, for example to output the archive title only on pages other than the home page.

## comments() takes no type argument

Some old themes fetch only regular comments like this:

```php
<?php $this->comments('comment')->to($comments); ?>
```

`comments()` has no parameters; the `'comment'` passed in is ignored, and the returned list **includes pings** (trackbacks and pingbacks). There are two ways to show only regular comments:

- The site owner checks "Show only comments, not Pingback and Trackback" under "Settings → Comments";
- The theme filters by `$comments->type`; see [Comment templates](./theme/comments.md#pings) for how.

In addition, `$this->pings()` cannot be used in 1.3.0 (it causes a fatal error); do not use it to fetch pings separately.

## Other things to check

- Drafts: the draft of published content now has the `revision` type (with `parent` pointing to the original content); code that queries the `contents` table directly must take this type into account;
- The option `commentsRequireURL` has been renamed `commentsRequireUrl`;
- Feeds are now output by `\Widget\Feed` and comment pagination is handled by `\Widget\CommentPage`; plugins that take over these outputs need checking;
- Custom form elements: in 1.3.0 `Textarea` and `Select` must be given a title argument; see [Form elements](./reference/form-elements.md);
- Add `define('__TYPECHO_DEBUG__', true);` to `config.inc.php` and visit the front end and the admin panel page by page: uncaught exceptions and type errors are shown with detailed error information. This constant does not change PHP's error-reporting settings; to see deprecation notices, also set `error_reporting = E_ALL` and `display_errors = On` in your local development environment (or check the PHP error log).
