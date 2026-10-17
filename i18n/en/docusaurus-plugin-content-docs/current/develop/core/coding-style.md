---
title: Coding Style
sidebar_position: 9
translation: machine
source_commit: 1d967a0c62b5a260a8146b63efb3c9323aa91cc3
---

# Coding Style

This page is the set of rules to follow when you submit code to the main Typecho repository. The `CODING_STYLE.md` that the "Coding Style Guide" link in the main repository's `CONTRIBUTING.md` points to does not exist in the repository; this page is the authoritative coding style. Where the text below sets no explicit rule, it describes the consistent practice of the existing code.

## PHP version: 7.4 and later

Typecho 1.3 supports PHP 7.4 and later. CI syntax-checks every PHP file with PHP 7.4, 8.0, 8.1 and 8.2, and a change cannot be merged if any one version reports an error (see [Contributing code](../contributing/index.md#ci)). Therefore:

**You can use** (supported since PHP 7.4):

- Typed properties: `protected array $stack = [];`
- Parameter and return type declarations, nullable types: `function get(string $key, ?string $default = null): ?string`
- Arrow functions `fn($x) => $x * 2`, null coalescing assignment `??=`, array unpacking `...$args`

**You cannot use** (requires PHP 8.0 or later):

- Union types `int|string`, `mixed`, the `static` return type, `never`
- `match` expressions, the nullsafe operator `?->`, named arguments
- Constructor property promotion, `readonly`, enums, the first-class callable syntax `strlen(...)`
- Functions added in PHP 8 such as `str_contains()` (these only fail at runtime and a syntax check cannot catch them, so take extra care)

Parameters with a default of `null` must be written explicitly as nullable types: write `?string $status = null`, not `string $status = null`. The latter produces a deprecation notice starting with PHP 8.4.

The existing code does not use `declare(strict_types=1)`.

## No Composer

Typecho is released as a zip package that users extract into the web directory and run; `composer install` is never executed. So:

- Do not add `composer.json` and do not introduce third-party dependencies;
- Third-party code you need (such as the Markdown parser HyperDown or `PasswordHash`) goes directly into the appropriate namespace under `var/`;
- Autoloading is done by the loader in `var/Typecho/Common.php`.

## Namespaces and files

- Code goes into the four top-level namespaces under `var/`: framework code in `Typecho\`, application widgets in `Widget\`, framework-independent utilities in `Utils\`, and XML-RPC in `IXR\`. For their responsibilities, see [Directory structure and namespaces](./architecture.md). Do not add new top-level namespaces;
- One class (or interface, or trait) per file, with the file path matching the namespace: `\Widget\Contents\Post\Edit` is in `var/Widget/Contents/Post/Edit.php`;
- Class, interface and trait names use UpperCamelCase, methods and properties use lowerCamelCase; interfaces end in `Interface` and traits end in `Trait`;
- Files start with `<?php`, followed by the `namespace` and `use` statements. Most files under `var/Widget/` have a direct-access guard after the `use` statements:

```php
<?php

namespace Widget;

use Typecho\Cookie;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Login widget
 */
class Login extends Users implements ActionInterface
{
    public function action()
    {
        // ……
    }
}
```

- Import classes with `use` and write short names in the code; do not write full `\Widget\...` paths in the code (templates and `admin/*.php` excepted).

## Formatting

The `.editorconfig` in the repository root specifies: indent with 4 spaces (2 spaces for `.yml` and `.scss`), and PHP files end with a newline. Other habits of the existing code:

- The opening brace of classes and methods goes on its own line; the opening brace of control structures such as `if` and `foreach` goes on the same line as the condition;
- Arrays use the short syntax `[]`;
- Public methods have PHPDoc comments describing the parameters, the return value and any exceptions that may be thrown; comments may be written in Chinese.

## Type declarations

New code declares types for properties, parameters and return values. When you modify an existing class, be careful: adding types to a class that themes or plugins may extend causes a fatal error for incompatible same-name declarations in subclasses: once a parent property has a type, the subclass's property of the same name must declare the same type; once a parent method has a return type, the subclass's method of the same name must declare a compatible return type. A subclass method parameter without a type is still compatible, because PHP allows subclasses to widen parameter types (which is exactly why some old plugins need changes for 1.3; see [Migrating themes and plugins to 1.3](../migrate-to-1-3.md)). For public classes that are often extended (`Widget\Archive`, `Widget\Base\*` and so on), assess the impact on existing plugins before changing a signature.

## Keeping legacy class names working {#aliases}

Published themes and plugins make heavy use of legacy class names written with underscores (`Typecho_Db`, `Widget_Abstract_Contents`, `Helper`), and they must keep working:

- The autoloader maps `Typecho_Foo_Bar` to `\Typecho\Foo\Bar` and `Widget_Foo` to `\Widget\Foo`, so classes that follow the mechanical rule need no extra handling;
- Classes whose old and new names cannot be mapped mechanically are listed in `__TYPECHO_CLASS_ALIASES__` in `var/Widget/Init.php`. **When you move or rename a class in this table, update its entry in the table**; when you move a class that follows the mechanical rule elsewhere, add a new entry for its old name;
- Plugin hook handles are also determined by the legacy class names: the hook handle in `Widget\Base\Contents` is `Widget_Abstract_Contents`. If you rename or move a class that provides hooks without keeping an alias, all hooks that plugins registered on the old handle stop working; see [Using hooks](../plugin/hooks.md);
- When you add a public class that plugins may use directly, consider whether it needs an alias.

For the mapping, see the [legacy class name table](../reference/class-aliases.md).

## Deprecate instead of deleting

Do not delete or rename public methods and properties that plugins and themes depend on. Keep the old name first, mark it `@deprecated`, and have it call the new implementation, for example:

```php
/**
 * @deprecated alias for destroy
 */
public static function destory(string $alias)
{
    self::destroy($alias);
}
```

After `@deprecated`, state the version from which it is deprecated (such as `@deprecated ^1.3.0`) or the replacement method.

## admin/*.php are templates

The PHP files under `admin/` are page templates, not classes:

- Do not define classes or functions in them; page logic goes into widgets under `var/Widget/`, and the template only calls them and outputs;
- Every page starts with `include 'common.php';` and can use `$options`, `$user`, `$security`, `$menu`, `$request` and `$response` prepared by `common.php` directly;
- Shared fragments (`header.php`, `menu.php`, `copyright.php`, `common-js.php`, `footer.php` and so on) are `include`d in the same order as in the existing pages;
- The access permission of an admin page is determined by the menu definition in `Widget\Menu`; when you add an admin page, give its minimum role in the menu definition; see [Permission model](./permissions.md#admin-screens);
- Forms are submitted to `/action/<name>` and handled by a widget that implements `Widget\ActionInterface`; generate the URL with `$security->getIndex()` or `$security->getTokenUrl()` so that it carries the token.

## Other conventions

- **Interface text**: wrap all user-facing text in `_t()`/`_e()`/`_n()`, write the source text in Simplified Chinese, and write it as a string literal (text from variables or concatenation is not extracted into the translation template); see [Development environment and front-end build](./build.md#pot);
- **Database**: access it through `Typecho\Db` and `Db\Query`, write table names as `table.<name>`, and stay compatible with MySQL, PostgreSQL and SQLite at the same time. When you change the table structure, change all three `.sql` files under `install/` and add an upgrade method to `Utils\Upgrade`; see [Writing upgrade scripts](./upgrade-scripts.md);
- **Front-end assets**: change the source files under `admin/src/`, then run the build script, and commit the source files together with the build output; do not change only the build output;
- **The usr/ directory**: the repository tracks only `usr/plugins/HelloWorld`, `usr/themes/default` and `usr/themes/classic-22`; do not commit other plugins, themes or uploaded files;
- **Configuration file**: `config.inc.php` contains local database information and is in `.gitignore`; do not commit it.
