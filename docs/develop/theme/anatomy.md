---
title: 主题结构与头部信息
sidebar_position: 3
---

# 主题结构与头部信息

## 目录

主题是 `usr/themes/` 下的一个目录。**目录名就是主题的标识**：站点设置中保存的当前主题（`theme` 选项）是目录名，主题设置保存在名为 `theme:目录名` 的选项中。发布后不要再改目录名，否则站长已经保存的主题设置会对不上。

唯一必需的文件是 `index.php`：只要目录中有 `index.php`，后台就会把它识别为主题。常见的文件：

| 文件 | 作用 |
|---|---|
| `index.php` | **必需**。头注释声明主题信息；同时是所有页面的最后回退模板 |
| `header.php`、`footer.php`、`sidebar.php` | 被其他模板用 `$this->need()` 引入的公共部分，文件名可以随意 |
| `post.php`、`page.php` | 文章页、独立页面 |
| `archive.php`、`category.php`、`tag.php`、`search.php`、`author.php` 等 | 各类列表页，可选 |
| `comments.php` | 评论列表和表单，通常由 `post.php`、`page.php` 引入 |
| `404.php` | 找不到页面 |
| `functions.php` | 不是模板，放主题设置、自定义字段、回调函数等，见 [functions.php 回调](./functions-php.md) |
| `screenshot.png` | 后台主题列表中显示的截图 |
| 其他 | CSS、JavaScript、图片、字体等静态文件，可以放在子目录中 |

模板文件按什么顺序被选用，见[模板层级](./template-hierarchy.md)。

## index.php 的头注释

`index.php` 开头的文档注释（`/** ... */`）就是主题信息：

```php
<?php
/**
 * 一个简洁的博客主题
 *
 * @package Starter
 * @author 你的名字
 * @version 1.0.0
 * @link https://example.com
 */
```

| 写法 | 后台显示 |
|---|---|
| 第一段文字（`@` 之前的行） | 主题描述 |
| `@package` | 主题名称。不写时列表中显示主题目录名 |
| `@author` | 作者 |
| `@link` | 作者名字链接到这个地址 |
| `@version` | 版本 |

后台主题列表显示“名称、作者: …、版本: …、描述”，当前启用的主题排在第一位。

`@since` 也能被读取，用来声明主题需要的最低 Typecho 版本（例如 `@since 1.3.0`）。注意：插件的 `@since` 会在启用时检查，**主题的 `@since` 不会被检查**，版本不够也能启用，它只起说明作用。

## 截图

主题目录（不含子目录）中文件名为 `screenshot` 的图片会作为截图显示，扩展名可以是 `png`、`jpg`、`jpeg`、`gif`、`bmp`、`webp`、`avif`，不区分大小写。有多个时使用第一个找到的。没有截图时，后台显示 Typecho 自带的占位图 `admin/img/noscreen.png`。

Typecho 对截图尺寸没有要求，后台按缩略图显示。

## 防止直接访问

每个模板文件开头都加上这一行：

```php
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
```

`__TYPECHO_ROOT_DIR__` 只在经由 Typecho 的入口（`index.php`）运行时才有定义。有人直接在浏览器中访问 `usr/themes/你的主题/post.php` 时，这一行让文件立即退出，不会因为 `$this` 不存在而输出错误信息。

## 加载静态文件：themeUrl()

主题中的 CSS、脚本、图片要用完整地址引用。`$this->options->themeUrl()` 输出当前主题目录中某个文件的地址：

```php
<link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
<script src="<?php $this->options->themeUrl('js/main.js'); ?>"></script>
<img src="<?php $this->options->themeUrl('img/logo.png'); ?>" alt="">
```

- 不带参数的 `$this->options->themeUrl` 属性是主题目录本身的地址，可以用来拼接；
- 第二个参数可以指定另一个主题的目录名，这时方法**返回**地址而不是直接输出：

  ```php
  <?php echo $this->options->themeUrl('style.css', 'classic-22'); ?>
  ```

- 想让主题静态文件走 CDN，可以在 `config.inc.php` 中定义 `__TYPECHO_THEME_URL__`，见[配置文件](../../guide/operations/config-file.md)。

同理，`$this->options->siteUrl('path')` 输出站点地址下的路径，`$this->options->adminUrl()` 输出后台地址。
