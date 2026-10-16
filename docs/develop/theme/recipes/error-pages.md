---
title: 404 与错误页
sidebar_position: 6
---

# 404 与错误页

Typecho 有两种“出错页面”：**找不到页面**（HTTP 404）由主题输出；**程序出错**（数据库连不上、模板出现致命错误等，HTTP 500 或 503）由 Typecho 自己输出，可以用 `__TYPECHO_EXCEPTION_FILE__` 换成自己的页面。

## 主题的 404.php

**问题**：访问不存在的地址时，显示带站点样式的 404 页面。

**做法**：在主题目录中新建 `404.php`：

```php
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main>
    <h1>404</h1>
    <p><?php _e('页面没找到'); ?></p>
    <form method="post" action="<?php $this->options->siteUrl(); ?>">
        <input type="search" name="s" placeholder="<?php _e('搜索'); ?>">
    </form>
    <p><a href="<?php $this->options->siteUrl(); ?>"><?php _e('返回首页'); ?></a></p>
</main>
<?php $this->need('footer.php'); ?>
```

**效果**：不存在的地址、未发布或无权查看的文章都使用这个模板，HTTP 状态码为 404，归档标题为“页面没找到”。

**原理**：遇到 404 时 Typecho 直接指定主题文件为 `404.php`。主题中没有这个文件时，按 `archive/404.php`、`archive.php`、`index.php` 的顺序查找（见[模板层级](../template-hierarchy.md)），状态码仍然是 404。在模板中可以用 `$this->is('archive', '404')` 判断当前是不是 404 页面。

开启 `__TYPECHO_DEBUG__` 时，不匹配任何路由的地址不会显示 `404.php`，而是直接输出异常信息和调用栈（状态码为 200），调试结束后关闭即可恢复。

## 程序出错时的页面：`__TYPECHO_EXCEPTION_FILE__`

**问题**：数据库连接失败等错误时，不想显示 Typecho 默认的错误页。

**做法**：新建一个 PHP 文件，例如网站目录外的 `/var/www/error.php`：

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>站点暂时无法访问</title>
</head>
<body>
    <h1>站点暂时无法访问</h1>
    <p>错误代码 <?php echo intval($code); ?>，请稍后再试。</p>
</body>
</html>
```

然后在 `config.inc.php` 中指定它：

```php
define('__TYPECHO_EXCEPTION_FILE__', '/var/www/error.php');
```

**效果**：程序出错时，Typecho 不再输出自带的错误页，而是执行这个文件。

**原理**：

- 执行这个文件之前，Typecho 已经设置好 HTTP 状态码。文件中可以使用三个变量：`$code`（状态码）、`$message`（错误信息）、`$exception`（异常对象）。`$message` 已经过处理：数据库错误显示为 `Error establishing a database connection` 等固定文字，其他程序错误显示为 `Server Error`；`$exception` 中则有完整的错误信息和调用栈，可能包含数据库地址、文件路径等内部信息，**不要输出给访客**。
- 这个文件在主题之外执行，不能使用 `$this`，也不能调用主题模板。
- 404 不经过这个文件，仍然由主题的 `404.php` 输出。
- 开启了 `__TYPECHO_DEBUG__` 时，Typecho 直接输出错误详情和调用栈，不使用这个文件。调试方法见[调试与排错](../../../guide/operations/debugging.md#exception-file)。
