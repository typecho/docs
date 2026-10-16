---
title: 自定义页面标题
sidebar_position: 1
---

# 自定义页面标题

**问题**：希望浏览器标签和搜索结果中的标题按页面类型变化，例如分类页显示“分类 技术 下的文章 - 站点名”，首页显示“站点名 - 站点描述”。

**做法**：把 `header.php` 中的 `<title>` 换成下面这段：

```php
<title><?php $this->archiveTitle([
    'category' => _t('分类 %s 下的文章'),
    'search'   => _t('包含关键字 %s 的文章'),
    'tag'      => _t('标签 %s 下的文章'),
    'author'   => _t('%s 发布的文章'),
], '', ' - '); ?><?php $this->options->title(); ?><?php if ($this->is('index')): ?> - <?php $this->options->description(); ?><?php endif; ?></title>
```

**效果**：

| 页面 | 标题 |
|---|---|
| 首页 | 站点名 - 站点描述 |
| 文章、独立页面 | 文章标题 - 站点名 |
| 分类 | 分类 技术 下的文章 - 站点名 |
| 标签 | 标签 PHP 下的文章 - 站点名 |
| 搜索 | 包含关键字 Typecho 的文章 - 站点名 |
| 作者 | admin 发布的文章 - 站点名 |
| 日期归档 | 2026年10月 - 站点名 |
| 404 | 页面没找到 - 站点名 |

**原理**：`archiveTitle($defines, $before, $end)` 只在当前页面有归档标题时输出“`$before` + 标题 + `$end`”，首页没有归档标题，什么都不输出。

- `$defines` 是按页面类型（`category`、`tag`、`search`、`author`、`date`、`post`、`page` 等）给出的格式，`%s` 换成分类名、关键字等；没有列出的类型直接输出标题本身。
- `$before` 默认为 ` &raquo; `，`$end` 默认为空。上面把 `$before` 设为空、`$end` 设为 ` - `，所以标题在前、站点名在后。
- 需要拿到标题字符串而不是直接输出时，用 `$this->getArchiveTitle()`。
