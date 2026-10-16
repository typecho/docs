---
title: 寻找插件与主题
sidebar_position: 4
---

# 寻找插件与主题

Typecho 目前**没有仍在维护的官方或社区插件 / 主题目录**。插件和主题由作者各自发布，大多托管在 GitHub 上。本页按需求列出搜索入口，并说明怎么判断一个插件或主题能否用在 1.3 上。本页不推荐、也不介绍具体的第三方插件。

找不到合适的，或者不确定该用哪个，到 [Typecho 论坛](https://forum.typecho.org/) 发帖询问，见[获取帮助](../help/getting-help.md)。

## GitHub 上的入口

- 插件：GitHub topic [`typecho-plugin`](https://github.com/topics/typecho-plugin)
- 主题：GitHub topic [`typecho-theme`](https://github.com/topics/typecho-theme)
- 关键字搜索：[搜索 “typecho plugin”，按最近更新排序](https://github.com/search?q=typecho+plugin&type=repositories&s=updated&o=desc)、[搜索 “typecho theme”，按最近更新排序](https://github.com/search?q=typecho+theme&type=repositories&s=updated&o=desc)

并不是所有作者都会给仓库加 topic，topic 页面只是一部分；关键字搜索覆盖面更大，但结果也更杂。2026 年 10 月查询时，`typecho-plugin` 和 `typecho-theme` 两个 topic 下各有不到 200 个仓库。

## 按需求搜索

下表的链接都是 GitHub 仓库搜索，按最近更新时间排序。中英文关键字的结果往往不同，可以都试试。

| 需求 | 搜索 | 不装插件的做法 |
|---|---|---|
| 代码高亮 | [typecho highlight](https://github.com/search?q=typecho+highlight&type=repositories&s=updated&o=desc)、[typecho 代码高亮](https://github.com/search?q=typecho+%E4%BB%A3%E7%A0%81%E9%AB%98%E4%BA%AE&type=repositories&s=updated&o=desc) | 很多主题自带；也可以在主题模板中引入 highlight.js、Prism 等前端库 |
| 数学公式 | [typecho katex](https://github.com/search?q=typecho+katex&type=repositories&s=updated&o=desc)、[typecho mathjax](https://github.com/search?q=typecho+mathjax&type=repositories&s=updated&o=desc) | 在主题模板中引入 KaTeX 或 MathJax 的脚本 |
| 站点地图 | [typecho sitemap](https://github.com/search?q=typecho+sitemap&type=repositories&s=updated&o=desc) | — |
| 访问统计 | [typecho analytics](https://github.com/search?q=typecho+analytics&type=repositories&s=updated&o=desc)、[typecho 统计](https://github.com/search?q=typecho+%E7%BB%9F%E8%AE%A1&type=repositories&s=updated&o=desc) | 把统计服务提供的代码粘贴到主题的 `footer.php` 或 `header.php` 中（可以用[在线编辑器](./themes.md#editor)） |
| 评论邮件通知 | [typecho comment mail](https://github.com/search?q=typecho+comment+mail&type=repositories&s=updated&o=desc)、[typecho 邮件 通知](https://github.com/search?q=typecho+%E9%82%AE%E4%BB%B6+%E9%80%9A%E7%9F%A5&type=repositories&s=updated&o=desc) | Typecho 本身不发送任何邮件，需要插件 |
| 附件存到对象存储 | [typecho oss](https://github.com/search?q=typecho+oss&type=repositories&s=updated&o=desc)、[typecho cos](https://github.com/search?q=typecho+cos&type=repositories&s=updated&o=desc)、[typecho s3](https://github.com/search?q=typecho+s3&type=repositories&s=updated&o=desc) | 只想让附件走 CDN、或放到网站目录之外，用 `config.inc.php` 中的常量即可，见[附件存储位置](../operations/uploads-storage.md) |

## 判断是否适用于 1.3

Typecho 1.3 需要 PHP 7.4 及以上，内部代码也改用了命名空间。为旧版本（1.1、1.2 甚至更早）写的插件和主题不一定能在 1.3 上正常工作。安装前看这几点：

1. **最近更新时间**：仓库在 1.3.0 发布（2026 年 1 月）之后还有提交、或者 README / 发布说明里提到 1.3，最可靠。很多年没有更新的项目要做好不能用的准备。
2. **声明的最低版本**：插件 `Plugin.php`、主题 `index.php` 开头的注释中如果写了 `@since 1.3.0` 之类的版本号，表示作者声明它需要这个版本以上。Typecho 启用插件时会检查 `@since`，当前版本更低时拒绝启用。没有写 `@since` 的不会被检查，并不代表兼容。
3. **代码写法**：1.3 的写法是带命名空间的类，例如插件类为 `namespace TypechoPlugin\插件名;`、`class Plugin implements PluginInterface`，使用 `\Widget\Options`、`\Typecho\Widget\Helper\Form` 这样的类名。只用 `Typecho_Widget`、`Widget_Abstract_Contents` 这类下划线旧类名的，是为旧版本写的，能否运行要实际试过才知道。
4. **Issue 区**：搜索 “1.3” 或 “PHP 8”，看看有没有人报告兼容问题。

试用新插件或主题时：

- 先备份，见[备份与恢复](../operations/backup.md)；
- 插件按[安装与配置插件](./plugins.md)的顺序安装，出问题时**先禁用、再删除目录**；
- 主题切换会清掉原主题的设置，见[安装与设置主题](./themes.md)；
- 来源不明的插件和主题拥有和 Typecho 本身一样的权限，可以读取数据库和所有文件。只从可信的作者处获取，并留意代码中是否有远程加载脚本、外发数据的行为。
