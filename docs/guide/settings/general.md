---
title: 基本设置
sidebar_position: 1
---

# 基本设置

后台“设置 → 基本”（`admin/options-general.php`），只有管理员能打开。保存后提示“设置已经保存”。

## 字段一览

| 界面名称 | 选项名 | 默认值 | 说明 |
|---|---|---|---|
| 站点名称 | `title` | 安装时填写 | 显示在网页标题和后台中，必填 |
| 站点地址 | `siteUrl` | 安装时的地址 | 首页链接，以及主题、插件静态文件和附件地址的前缀，见[下文](#site-url) |
| 站点描述 | `description` | `Your description here.` | 输出为页面头部的 `<meta name="description">` |
| 关键词 | `keywords` | `typecho,php,blog` | 输出为 `<meta name="keywords">`，用半角逗号分隔 |
| 是否允许注册 | `allowRegister` | 不允许（`0`） | 允许后访客可以注册，新用户为“关注者”，见[用户、角色与注册](../content/users.md#register) |
| XMLRPC 接口 | `allowXmlRpc` | 打开（`2`） | `0` 关闭、`1` 仅关闭 Pingback 接口、`2` 打开，见[订阅源与离线客户端](../content/feeds-xmlrpc.md#xmlrpc) |
| 语言 | `lang` | `zh_CN` | 只在 `usr/langs` 中有语言包时才显示，见[界面语言](../extend/languages.md) |
| 时区 | `timezone` | GMT +8（`28800`） | 安装程序不提供时区选择，安装后请改成你所在的时区，见[下文](#timezone) |
| 允许上传的文件类型 | `attachmentTypes` | `@image@` | 见[下文](#attachment-types) |

站点名称、站点描述、关键词中不能包含 `<`、`>` 等特殊字符，否则提示“请不要在……中使用特殊字符”。

## 站点地址 {#site-url}

“站点地址”（`siteUrl`）用于首页链接，以及主题、插件的静态文件（CSS、JS、图片）和附件地址。文章、页面、分类、订阅源等永久链接**不使用**它，而是使用当前请求的根地址，所以永久链接的域名或协议不对时，只改这一项解决不了，见[HTTPS、反向代理与 CDN：永久链接的域名和协议](../operations/https-proxy.md#root-url)。迁移到新域名或改用 HTTPS 时也要修改它，见[从其他博客迁移：换域名](../operations/migration.md#new-domain)。

- 保存时会去掉末尾的 `/`。
- 1.3 起输入框是网址类型，可以直接输入中文等国际化域名（以 `xn--` 形式保存，显示时转回原文，需要 PHP `intl` 扩展）。
- 当你访问后台所用的地址与这里的设定不一致时，下方会提示“当前地址 某某 与上述设定值不一致”。

### 用 `__TYPECHO_SITE_URL__` 固定站点地址

在 `config.inc.php` 中定义这个常量，站点地址就以常量为准：

```php
define('__TYPECHO_SITE_URL__', 'https://example.com');
```

定义后，“基本设置”中不再显示“站点地址”这一项；保存其他设置时，数据库中原有的 `siteUrl` 不会被改动。适合多环境部署或不希望在后台被误改的场景。注意这个常量只改变站点地址本身，HTTPS、反向代理等情况下的链接生成规则见[HTTPS、反向代理与 CDN](../operations/https-proxy.md#site-url)。

## 时区 {#timezone}

“时区”（`timezone`）的值是与格林威治时间相差的**秒数**，例如“北京时间 香港,新加坡,越南 (GMT +8)”保存为 `28800`。它决定后台和前台显示的时间，以及写文章时“发布日期”按哪个时区理解。

- 下拉框只有从 GMT -12 到 GMT +12 的 25 个**整点**时区，没有半点时区（例如印度的 +5:30），也不会自动处理夏令时。列表中“新德里时间 巴基斯坦,马尔代夫 (GMT +5)”一项的偏移实际是 +5 小时，与新德里的 +5:30 不符。
- 修改时区不会改动已经保存的时间，只改变显示：数据库中保存的都是时间戳。

## 允许上传的文件类型 {#attachment-types}

四个复选框对应 `attachmentTypes` 中的四种值，可以组合：

| 界面上的选项 | 保存的值 |
|---|---|
| 图片文件 (gif jpg jpeg png tiff bmp webp avif) | `@image@` |
| 多媒体文件 (mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv) | `@media@` |
| 常用档案文件 (txt doc docx xls xlsx ppt pptx zip rar pdf) | `@doc@` |
| 其他格式 + 文本框（`attachmentTypesOther`） | 文本框中的扩展名，用半角逗号隔开 |

“其他格式”中的扩展名会转为小写，`php`、`sh`、`exe` 等可执行类型会被自动去掉；另外“图片文件”的说明中虽然列了 `avif`，1.3.0 实际并不允许。详细规则见[附件与文件管理](../content/attachments.md#types)。

## 语言 {#lang}

只有 `usr/langs`（或 `__TYPECHO_LANG_DIR__` 指定的目录）中至少有一个 `.mo` 语言包时，才会出现“语言”下拉框，选项是“简体中文”加上每个语言包自己声明的语言名称，例如 `English`、`日本語`。切换后整个后台和主题中使用 `_t()` 的文字都会改变，**对所有用户生效**，没有按用户分别设置的语言。

安装语言包和语言包缺失时的行为见[界面语言](../extend/languages.md)。
