---
title: 插件开发概览
sidebar_position: 1
---

# 插件开发概览

插件是放在 `usr/plugins/` 下的一段 PHP 代码，在后台“控制台 → 插件”中启用后，可以改变或扩展 Typecho 的几乎所有行为，而不需要修改 Typecho 自己的文件。主题负责“页面长什么样”，插件负责“站点能做什么”。

## 插件能做什么

| 想做的事 | 用什么 | 详见 |
|---|---|---|
| 在页面输出前后插入内容，修改正文、标题、摘要 | 过滤类钩子，如 `contentEx` | [使用钩子](./hooks.md)、[常见插件模式](./patterns.md#content-filter) |
| 在发布文章、提交评论、登录时做点什么 | 通知类钩子，如 `finishPublish`、`finishComment`、`loginSucceed` | [钩子列表](../reference/hooks.md) |
| 接管 Typecho 的某个功能：编辑器、附件存储、Markdown 解析、密码校验 | trigger 类钩子，如 `richEditor`、`uploadHandle` | [常见插件模式](./patterns.md) |
| 给模板提供新的方法和属性 | 动态钩子 `call<Method>`、`___<prop>` | [使用钩子](./hooks.md#动态钩子) |
| 让管理员或用户配置插件 | `config()`、`personalConfig()` | [插件配置](./settings.md) |
| 增加前台页面 | 路由 | [路由、Action 与后台面板](./routes-actions.md) |
| 接收表单提交、Ajax 请求 | Action | 同上 |
| 增加后台管理页面和菜单 | 后台面板 | 同上 |
| 保存自己的数据 | 数据库，自建数据表 | [数据库操作](./database.md) |
| 调用外部服务，在后台执行耗时任务 | `Typecho\Http\Client`、异步任务 | [HTTP 请求与异步任务](./http-async.md) |

## 钩子与扩展点

Typecho 的扩展点分两类。

**钩子**是 Typecho 代码中预留的位置，执行到这里时依次调用插件注册的回调。1.3.0 中有 124 个名字固定的钩子（句柄与钩子名的组合），另有 3 类名字在运行时拼出的动态钩子，按所在位置分为：

- **后台模板中的钩子**：`admin/menu.php` 的 `navBar`（导航栏）、`admin/write-post.php` 的 `option` 和 `advanceOption`（撰写页侧栏）、`admin/footer.php` 的 `end` 等，用来在后台页面中输出内容；
- **入口文件的钩子**：`index.php` 的 `begin` 和 `end`，在每个前台请求的开始和结束时调用；
- **Widget 类中的钩子**：前台页面的输出（`Widget\Archive`）、内容和评论的过滤（`Widget\Base\Contents`、`Widget\Base\Comments`）、发布和删除文章（`Widget\Contents\Post\Edit`）、提交评论（`Widget\Feedback`）、上传附件（`Widget\Upload`）、登录（`Widget\User`）等。

**注册表**是保存在数据库中的几张表：路由表、Action 表、后台面板表。插件在启用时往里面添加条目，在禁用时删除。

## 阅读顺序

1. [第一个插件](./hello-world.md)：读懂自带的 HelloWorld，写一个自己的插件并启用；
2. [插件结构与生命周期](./structure.md)：文件布局、头注释、启用和禁用时发生了什么；
3. [使用钩子](./hooks.md)：注册回调、句柄命名、优先级、三种触发方式；
4. [插件配置](./settings.md)：设置表单、读取设置、个人设置；
5. 按需要阅读：[路由、Action 与后台面板](./routes-actions.md)、[数据库操作](./database.md)、[HTTP 请求与异步任务](./http-async.md)；
6. [常见插件模式](./patterns.md)：内容过滤、评论反垃圾、附件存储、编辑器、登录事件的完整示例；
7. [发布插件](./releasing.md)。

写插件时经常要查的参考：

- [钩子列表](../reference/hooks.md)：全部钩子的类型、参数和调用位置；
- [Utils\Helper](../reference/helper.md)：插件常用的静态方法；
- [默认路由与 Action](../reference/routes-actions.md)、[数据表结构](../reference/database-schema.md)；
- [表单元素](../reference/form-elements.md)、[$this->options](../reference/options.md)；
- [旧类名对照表](../reference/class-aliases.md)：读旧插件代码时对照。

从旧版本迁移插件见[主题与插件迁移到 1.3](../migrate-to-1-3.md)，翻译插件界面见[主题与插件国际化](../i18n.md)。

## 开发环境

开发插件时，在 `config.inc.php` 中打开调试模式，出错时能看到完整的错误信息和调用栈：

```php title="config.inc.php"
define('__TYPECHO_DEBUG__', true);
```

见[调试与排错](../../guide/operations/debugging.md#debug)。另外请记住：

- 修改了 `activate()` 中的注册代码，要在后台禁用再启用插件才会生效；
- 如果服务器开启了 PHP 的 OPcache，修改文件后可能要等几秒才会生效。
