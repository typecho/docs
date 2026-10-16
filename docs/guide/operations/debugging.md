---
title: 调试与排错
sidebar_position: 10
---

# 调试与排错

本页介绍出现 500 错误、空白页、功能失灵时的排查方法：先看错误信息，再用“禁用插件 + 切回默认主题”缩小范围。

:::info 测试情况
本页中错误页、`__TYPECHO_DEBUG__`、`__TYPECHO_EXCEPTION_FILE__` 的行为，以及通过数据库禁用插件、切换主题的方法，都已在 Typecho 1.3.0（源码 `v1.3.0-10`）+ Apache + PHP-FPM（PHP 8.5）+ SQLite 上实测。SQL 语句未在 MySQL 和 PostgreSQL 上执行过，但只用了三者共有的语法。
:::

## Typecho 的错误页

默认情况下，Typecho 捕获到未处理的异常时，会显示一个只有状态码和简短说明的错误页（`var/Typecho/Common.php` 中的 `error()`），不显示具体原因。数据库错误的信息会被统一替换，以免泄露数据库细节：

| 页面上的文字 | 状态码 | 含义 |
|---|---|---|
| `Error establishing a database connection` | 503 | 连不上数据库：数据库服务没运行、`config.inc.php` 中的地址或密码错误、SQLite 文件路径不存在或不可写 |
| `Database Query Error` | 500 | SQL 执行出错：常见于数据表缺失、表结构与程序版本不匹配（升级没有完成） |
| `Database Server Error` | 500 | 其他数据库错误 |
| `Server Error` 或其他说明 | 500 等 | 程序或插件、主题抛出的错误 |

要看到具体原因，打开调试模式，或者看 PHP 的错误日志。

## 调试模式 `__TYPECHO_DEBUG__` {#debug}

在 `config.inc.php` 末尾加上：

```php
define('__TYPECHO_DEBUG__', true);
```

开启后，Typecho 不再使用上面的错误页（`var/Widget/Init.php`），未捕获的异常会直接输出错误信息和完整的调用栈，例如：

```text
SQLSTATE[HY000] [14] unable to open database file
Typecho\Db\Adapter\ConnectionException: SQLSTATE[HY000] [14] unable to open database file in /srv/www/typecho/var/Typecho/Db/Adapter/Pdo.php:62
Stack trace:
#0 /srv/www/typecho/var/Typecho/Db.php(218): Typecho\Db\Adapter\Pdo->connect(Object(Typecho\Config))
...
```

注意：

- 调试模式只影响**异常**的显示。PHP 的警告、提示（Warning、Notice、Deprecated）是否显示在页面上，由 PHP 的 `display_errors` 决定，与这个常量无关。
- 调试模式下出错的页面返回的状态码是 **200**，而不是 500（实测）。
- 调用栈会暴露服务器路径、类名等信息，**查完问题立即删除这一行**，不要在正式站点上长期开启。

## 自定义错误页 `__TYPECHO_EXCEPTION_FILE__` {#exception-file}

想让错误页与站点风格一致时，指定一个 PHP 文件代替内置的错误页：

```php
define('__TYPECHO_EXCEPTION_FILE__', __DIR__ . '/usr/error.php');
```

Typecho 出错时会 `require_once` 这个文件，此时可以使用这几个变量：

| 变量 | 内容 |
|---|---|
| `$code` | HTTP 状态码，已经设置到响应中 |
| `$message` | 错误说明（已转换换行），数据库错误同样是上表中被替换后的文字 |
| `$exception` | 异常对象 |

```php title="usr/error.php"
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><title><?php echo $code; ?></title></head>
<body>
  <h1>出错了（<?php echo $code; ?>）</h1>
  <p><?php echo $message; ?></p>
</body>
</html>
```

不要在这个文件里输出 `$exception` 的内容，理由同调试模式。`404` 错误不经过这里，而是由当前主题的 `404.php` 显示。开启了 `__TYPECHO_DEBUG__` 时这个常量不起作用。

## 查看日志 {#logs}

页面上看不到错误时（例如空白页），答案通常在日志里。

| 日志 | 常见位置 | 说明 |
|---|---|---|
| PHP 错误日志 | 由 PHP 的 `error_log` 设置决定；PHP-FPM 还可能写在 FPM 自己的日志里，如 `/var/log/php8.2-fpm.log` | 用 `phpinfo()` 查看**Web 环境**中 `error_log` 和 `log_errors` 的值。命令行 `php -i` 显示的是 CLI 的配置，可能与 Web 不同 |
| nginx 错误日志 | `/var/log/nginx/error.log` | “Primary script unknown”“upstream timed out”等 FastCGI 错误在这里 |
| Apache 错误日志 | `/var/log/apache2/error.log`（Debian / Ubuntu）、`/var/log/httpd/error_log`（RHEL 系） | `.htaccess` 语法错误、模块未加载等 |
| 容器 | `docker logs 容器名` | 官方镜像把 PHP 错误输出到标准错误，见[容器与云主机部署](./containers.md) |

用 `systemd` 管理的服务也可以用 `journalctl -u php8.2-fpm -u nginx --since "10 min ago"` 查看。

实时观察：

```bash
tail -f /var/log/nginx/error.log /var/log/php8.2-fpm.log
```

然后在浏览器中重现问题。

## 排查流程：禁用插件、切回默认主题 {#isolate}

升级后、启用某个插件后、更换主题后出现 500、空白页、后台按钮失效等问题，大多与插件或主题有关。按下面的顺序缩小范围：

1. **禁用全部插件**：后台“控制台 → 插件”，逐个点击“禁用”。
2. **切换到自带主题**：后台“控制台 → 外观”，启用 `default`（或 1.3 新增的 `classic-22`）。
3. 问题消失后，**逐个**重新启用插件，每启用一个就测试一次，找出引起问题的那一个；最后再切回原来的主题测试。
4. 找到之后：升级该插件或主题到支持 1.3 的版本，或联系作者，或停用它。

插件的设置保存在数据库中，禁用后再启用，设置不会丢失。

### 后台进不去时：通过数据库操作

如果错误导致后台也打不开，可以直接改数据库中的选项（表前缀以 `config.inc.php` 为准，默认 `typecho_`）。**先备份数据库**。

**一次禁用全部插件**（与新安装站点的初始值相同）：

```sql
UPDATE typecho_options SET value = 'a:0:{}' WHERE name = 'plugins'     AND user = 0;
UPDATE typecho_options SET value = '[]'     WHERE name = 'actionTable' AND user = 0;
UPDATE typecho_options SET value = '[]'     WHERE name = 'panelTable'  AND user = 0;
```

- `plugins` 记录了启用的插件和它们挂接的接口；后两项是插件注册的动作地址和后台菜单，不清空的话，插件留下的菜单项还会显示。
- 各插件的设置（`plugin:插件名` 选项）不受影响，之后在后台重新启用插件即可恢复。

**切换到默认主题**：

```sql
UPDATE typecho_options SET value = 'default' WHERE name = 'theme' AND user = 0;
```

:::warning 不要只删除插件目录
插件处于启用状态时，直接删除或重命名 `usr/plugins/` 下它的目录，并不会禁用它：Typecho 仍会去调用这个插件挂接的接口，相关页面会变成 500（实测：启用自带的 `HelloWorld` 插件后移走它的目录，后台页面返回 500）。请用后台禁用，或按上面的 SQL 操作。
:::

## 常见问题速查

| 现象 | 先看 |
|---|---|
| 所有文章 404、No input file specified、登录时 405 | [Web 服务器配置](./web-server.md#troubleshooting) |
| 升级后访问后台总是跳到升级页，或“完成升级”一直加载 | [升级](../getting-started/upgrade.md)，特别是附件超过 100 个时的已知问题 |
| 503 `Error establishing a database connection` | 检查数据库服务和 `config.inc.php` 中的连接参数，见[config.inc.php 与常量](./config-file.md) |
| 样式丢失、混合内容、登录后又回到登录页 | [HTTPS、反向代理与 CDN](./https-proxy.md) |
| 上传附件失败 | `usr/uploads/` 是否可写、PHP 的 `upload_max_filesize`，见[附件存储位置](./uploads-storage.md) |
| 时间显示不对 | 后台“设置 → 基本”中的时区 |

还解决不了时，带上本页收集到的错误信息和日志去求助，见[获取帮助](../help/getting-help.md)。
