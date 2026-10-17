---
title: 编码规范
sidebar_position: 9
---

# 编码规范

本页是向 Typecho 主仓库提交代码时应遵守的规范。主仓库 `CONTRIBUTING.md` 中“Coding Style Guide”链接指向的 `CODING_STYLE.md` 在仓库中并不存在，编码规范以本页为准。下文中没有明文规定的地方，按现有代码的一贯写法描述。

## PHP 版本：7.4 起

Typecho 1.3 支持 PHP 7.4 及以上版本。CI 用 PHP 7.4、8.0、8.1、8.2 对每个 PHP 文件做语法检查，只要有一个版本报错就不能合并（见[贡献代码](../contributing/index.md#ci)）。因此：

**可以使用**（PHP 7.4 已支持）：

- 类型化属性：`protected array $stack = [];`
- 参数和返回值的类型声明、可空类型：`function get(string $key, ?string $default = null): ?string`
- 箭头函数 `fn($x) => $x * 2`、空合并赋值 `??=`、数组展开 `...$args`

**不能使用**（需要 PHP 8.0 或更高）：

- 联合类型 `int|string`、`mixed`、`static` 返回类型、`never`
- `match` 表达式、空安全运算符 `?->`、命名参数
- 构造函数属性提升、`readonly`、枚举、first-class callable 语法 `strlen(...)`
- `str_contains()` 等 PHP 8 新增的函数（只在运行时报错，语法检查发现不了，要格外注意）

有默认值 `null` 的参数要显式写成可空类型：写 `?string $status = null`，不要写 `string $status = null`。后一种写法在 PHP 8.4 起会产生弃用提示。

现有代码没有使用 `declare(strict_types=1)`。

## 不使用 Composer

Typecho 以 zip 包发布，用户解压到网站目录即可运行，不会执行 `composer install`。所以：

- 不要添加 `composer.json`，不要引入第三方依赖；
- 需要的第三方代码（如 Markdown 解析器 HyperDown、`PasswordHash`）直接放进 `var/` 中相应的命名空间；
- 自动加载由 `var/Typecho/Common.php` 中的加载器完成。

## 命名空间与文件

- 代码放在 `var/` 下的四个顶层命名空间中：框架代码放 `Typecho\`，业务 Widget 放 `Widget\`，与框架无关的工具放 `Utils\`，XML-RPC 放 `IXR\`。各自的职责见[目录结构与命名空间](./architecture.md)。不要新增顶层命名空间；
- 一个文件一个类（或接口、trait），文件路径与命名空间一致：`\Widget\Contents\Post\Edit` 在 `var/Widget/Contents/Post/Edit.php`；
- 类名、接口名、trait 名用大驼峰，方法和属性用小驼峰；接口以 `Interface` 结尾，trait 以 `Trait` 结尾；
- 文件以 `<?php` 开头，接着是 `namespace` 和 `use` 语句。`var/Widget/` 下的大多数文件在 `use` 之后有直接访问保护：

```php
<?php

namespace Widget;

use Typecho\Cookie;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 登录组件
 */
class Login extends Users implements ActionInterface
{
    public function action()
    {
        // ……
    }
}
```

- 用 `use` 导入类，代码中写短名，不在代码中写完整的 `\Widget\...` 路径（模板和 `admin/*.php` 除外）。

## 格式

仓库根目录的 `.editorconfig` 规定：缩进 4 个空格（`.yml`、`.scss` 为 2 个空格），PHP 文件以换行结尾。现有代码的其他习惯：

- 类和方法的左花括号另起一行，`if`、`foreach` 等控制结构的左花括号与条件在同一行；
- 数组用短语法 `[]`；
- 公共方法写 PHPDoc 注释，说明参数、返回值和可能抛出的异常；注释可以用中文。

## 类型声明

新代码给属性、参数和返回值都写上类型声明。修改已有类时要注意：给一个可能被主题或插件继承的类补上类型，会让子类中不兼容的同名声明产生致命错误：父类属性有了类型，子类的同名属性必须写相同的类型；父类方法有了返回类型，子类的同名方法必须写兼容的返回类型。子类方法的参数不写类型仍然兼容，PHP 允许子类放宽参数类型（这正是 1.3 中一些旧插件需要修改的原因，见[主题与插件迁移到 1.3](../migrate-to-1-3.md)）。对公开的、常被继承的类（`Widget\Archive`、`Widget\Base\*` 等），修改签名前先评估对现有插件的影响。

## 保持旧类名可用 {#aliases}

已发布的主题和插件大量使用下划线写法的旧类名（`Typecho_Db`、`Widget_Abstract_Contents`、`Helper`），它们必须继续可用：

- 自动加载器把 `Typecho_Foo_Bar` 映射到 `\Typecho\Foo\Bar`、`Widget_Foo` 映射到 `\Widget\Foo`，所以按机械规则对应的类不需要额外处理；
- 新旧名字不能机械对应的类列在 `var/Widget/Init.php` 的 `__TYPECHO_CLASS_ALIASES__` 中。**移动或重命名这张表中的类时，同步修改表中的条目**；把一个按机械规则对应的类移到别处时，要为它的旧名字新增一条；
- 插件钩子的句柄名也由旧类名决定：`Widget\Base\Contents` 中的钩子句柄是 `Widget_Abstract_Contents`。重命名或移动一个提供钩子的类，如果不保留别名，插件注册在旧句柄上的钩子会全部失效，见[使用钩子](../plugin/hooks.md)；
- 新增一个插件可能直接使用的公开类时，考虑它是否需要别名。

对照表见[旧类名对照表](../reference/class-aliases.md)。

## 弃用而不删除

插件和主题依赖的公开方法、属性不要直接删除或改名，先保留旧名字并标注 `@deprecated`，让它调用新实现，例如：

```php
/**
 * @deprecated alias for destroy
 */
public static function destory(string $alias)
{
    self::destroy($alias);
}
```

`@deprecated` 后面写明从哪个版本开始弃用（如 `@deprecated ^1.3.0`）或替代方法。

## admin/*.php 是模板

`admin/` 下的 PHP 文件是页面模板，不是类：

- 不要在其中定义类或函数；页面逻辑放进 `var/Widget/` 下的 Widget，模板只负责调用和输出；
- 每个页面以 `include 'common.php';` 开头，可以直接使用 `common.php` 准备好的 `$options`、`$user`、`$security`、`$menu`、`$request`、`$response`；
- 公共片段（`header.php`、`menu.php`、`copyright.php`、`common-js.php`、`footer.php` 等）按现有页面的顺序 `include`；
- 后台页面的访问权限由 `Widget\Menu` 中的菜单定义决定，新增后台页面时要在菜单定义中给出最低角色，见[权限模型](./permissions.md#admin-screens)；
- 表单提交到 `/action/<名字>`，由实现了 `Widget\ActionInterface` 的 Widget 处理，地址用 `$security->getIndex()` 或 `$security->getTokenUrl()` 生成以带上令牌。

## 其他约定

- **界面文字**：所有面向用户的文字用 `_t()`/`_e()`/`_n()` 包裹，原文写简体中文，并且写成字符串字面量（变量或拼接出来的文字不会被提取到翻译模板中），见[开发环境与前端构建](./build.md#pot)；
- **数据库**：通过 `Typecho\Db` 和 `Db\Query` 访问，表名写 `table.名字`，同时兼容 MySQL、PostgreSQL、SQLite。修改表结构要同时修改 `install/` 下三个 `.sql` 文件，并在 `Utils\Upgrade` 中加入升级方法，见[编写升级脚本](./upgrade-scripts.md)；
- **前端资源**：修改 `admin/src/` 下的源文件，再运行构建脚本，源文件和构建结果一起提交，不要只改构建结果；
- **usr/ 目录**：仓库只跟踪 `usr/plugins/HelloWorld`、`usr/themes/default` 和 `usr/themes/classic-22`，不要提交其他插件、主题或上传文件；
- **配置文件**：`config.inc.php` 包含本地数据库信息，在 `.gitignore` 中，不要提交。
