---
title: 头部输出 header()
sidebar_position: 12
---

# 头部输出 header()

`$this->header()` 放在模板的 `<head>` 中，一次输出页面需要的各种元数据和脚本：

```php
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <title><?php $this->archiveTitle('', '', ' - '); ?><?php $this->options->title(); ?></title>
    <?php $this->header(); ?>
</head>
```

## 输出哪些内容

| 键 | 输出 | 条件 |
|---|---|---|
| — | `<link rel="canonical">` | 文章、页面（不能屏蔽） |
| `description` | `<meta name="description">` | 有归档描述时 |
| `keywords` | `<meta name="keywords">` | 有关键词时 |
| `generator` | `<meta name="generator" content="Typecho 1.3.0">` | |
| `template` | `<meta name="template" content="主题目录名">` | |
| `pingback` | `<link rel="pingback">` | “XMLRPC 接口”为“打开” |
| `xmlrpc` | `<link rel="EditURI">`（RSD） | “XMLRPC 接口”不为“关闭” |
| `wlw` | `<link rel="wlwmanifest">` | 同上 |
| `rss2` / `rss1` / `atom` | 三条 `<link rel="alternate">` 订阅源 | 文章页还要求文章“允许在聚合中出现” |
| `social` | `og:type`、`og:url`、`og:title`、`og:description`、`og:site_name` 和 `twitter:*` 标签 | |
| `commentReply` | 评论回复脚本（`window.TypechoComment`） | 文章、页面，且开启了“启用评论回复” |
| `antiSpam` | 反垃圾脚本，向评论表单加入校验字段 | 文章、页面，且开启了“开启反垃圾保护” |

`commentReply` 和 `antiSpam` 是评论功能所必需的：屏蔽 `commentReply` 后“回复”按钮不能把表单移到评论下方；屏蔽 `antiSpam` 后，开启了反垃圾保护的站点上评论会提交失败。

## 屏蔽或替换某些项

给 `header()` 传一个查询字符串，把不需要的键设为空：

```php
<?php $this->header('generator=&template=&pingback=&xmlrpc=&wlw='); ?>
```

上面这行去掉了版本号、主题名以及 Pingback、RSD、wlwmanifest 三个链接，其余照常输出。几种常见的用法：

| 写法 | 效果 |
|---|---|
| `generator=&template=` | 不暴露 Typecho 版本和主题名 |
| `rss1=&atom=` | 只保留 RSS 2.0 订阅源 |
| `social=` | 不输出 Open Graph / Twitter 标签（例如主题或插件自己输出了这些标签） |
| `description=&keywords=` | 由主题自己输出描述和关键词 |
| `commentReply=/usr/themes/你的主题/js/reply.js` | 不输出内联的回复脚本，改为加载指定地址的脚本文件 |

`commentReply` 和 `antiSpam` 的值为 `1`（默认）时输出内置脚本，为空时不输出，为其他值时当作脚本地址输出 `<script src="...">`。替换成自己的脚本时，要自己实现原脚本的功能。

## headerOptions 过滤器

`header()` 在输出前会把这些键值交给 `Widget\Archive` 的 `headerOptions` 过滤器，插件可以借此统一修改。主题也可以在 `functions.php` 中注册：

```php
\Typecho\Plugin::factory('Widget\Archive')->headerOptions = 'starterHeaderOptions';

function starterHeaderOptions($allows, $archive)
{
    $allows['generator'] = '';
    $allows['template'] = '';

    if ($archive->is('single')) {
        $allows['rss1'] = '';
        $allows['atom'] = '';
    }

    return $allows;
}
```

- 回调接收两个参数：键值数组 `$allows` 和当前的 `Widget\Archive`；必须返回修改后的数组。
- 过滤器在 `header()` 的参数合并之后执行，所以它可以覆盖模板中传入的设置。
- 写在 `functions.php` 顶层的注册语句，每次前台请求加载 `functions.php` 时都会执行，只对当前请求有效，不需要像插件那样启用。

## header() 之后

插件可以通过 `Widget\Archive` 的 `header` 接口在 `header()` 输出的内容之后追加内容，例如统计代码、额外的样式。对应地，`$this->footer()` 应放在 `</body>` 之前，供插件在页面底部输出内容。
