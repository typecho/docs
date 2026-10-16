---
title: config.inc.php 与常量
sidebar_position: 4
---

# config.inc.php 与常量

`config.inc.php` 位于网站根目录（与 `index.php` 同级），由安装程序生成，保存数据库连接和几个基础路径。Typecho 的许多高级行为都通过在这个文件中**定义常量**来调整，本页列出 1.3 中全部可用的常量。

本页的常量与读取位置对应 Typecho 1.3.0，文件路径相对于 Typecho 程序根目录。

## 安装程序生成的内容

```php title="config.inc.php"
<?php
// site root path
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

// plugin directory (relative path)
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

// theme directory (relative path)
define('__TYPECHO_THEME_DIR__', '/usr/themes');

// admin directory (relative path)
define('__TYPECHO_ADMIN_DIR__', '/admin/');

// register autoload
require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';

// init
\Typecho\Common::init();

// config db
$db = new \Typecho\Db('Pdo_Mysql', 'typecho_');
$db->addServer(array (
  'host' => 'localhost',
  'port' => 3306,
  'user' => 'typecho',
  'password' => '******',
  'charset' => 'utf8mb4',
  'database' => 'typecho',
  'engine' => 'InnoDB',
  'sslCa' => NULL,
  'sslVerify' => false,
), \Typecho\Db::READ | \Typecho\Db::WRITE);
\Typecho\Db::set($db);
```

（模板见 `install.php` 中的 `install_config_file()`。）

| 部分 | 说明 |
|---|---|
| 4 个 `define` | 网站根目录，以及插件、主题、后台目录相对于根目录的路径。前三个一般不改；后台目录可以改，见[自定义后台路径](./admin-dir.md) |
| `Common.php` 与 `Common::init()` | 注册自动加载、初始化输出缓冲和默认的异常输出，必须保留 |
| `new \Typecho\Db(适配器, 表前缀)` | 适配器是 `Pdo_Mysql`、`Mysqli`、`Pdo_Pgsql`、`Pgsql`、`Pdo_SQLite`、`SQLite` 之一 |
| `addServer(连接参数, 用途)` | 连接参数见下表；用途 `Db::READ \| Db::WRITE` 表示这台服务器同时用于读和写 |

连接参数的键：

| 数据库 | 键 |
|---|---|
| MySQL | `host`、`port`、`user`、`password`、`charset`、`database`、`engine`、`sslCa`、`sslVerify`。`host` 中含有 `/` 时视为 Unix socket 路径，此时 `port` 为 `null` |
| PostgreSQL | `host`、`port`、`user`、`password`、`charset`、`database`、`sslVerify` |
| SQLite | `file`：数据库文件的绝对路径 |

更换数据库服务器、改密码时，直接修改这里的值即可。

:::danger 保护好这个文件
`config.inc.php` 里有数据库密码。它是 PHP 文件，正常情况下通过网页访问只会得到空白页；但不要把它复制成 `config.inc.php.bak` 之类的非 `.php` 文件名留在网站目录里，也不要提交到公开的代码仓库。
:::

## 如何添加常量

把 `define(...)` 加在文件末尾即可，例如：

```php
// 以下为自定义设置
define('__TYPECHO_SECURE__', true);
define('__TYPECHO_THEME_WRITEABLE__', false);
```

每个常量只能定义一次。升级 Typecho 不会修改 `config.inc.php`，自定义的常量会一直保留。

## 可选常量一览

共 21 个。“读取位置”是源码中检查这个常量的地方，方便核对和排错。

### 调试与错误

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_DEBUG__` | 布尔，未定义 | 为真时不安装友好的错误页，未捕获的异常直接显示完整的错误信息和调用栈。**只在排错时临时开启**，见[调试与排错](./debugging.md#debug) | `var/Widget/Init.php:36` |
| `__TYPECHO_EXCEPTION_FILE__` | 文件路径，未定义 | 出错时用这个 PHP 文件代替内置的错误页 | `var/Typecho/Common.php:252` |

### 地址与 HTTPS

详细说明和使用场景见[HTTPS、反向代理与 CDN](./https-proxy.md)。

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_SITE_URL__` | 字符串，未定义 | 固定站点地址，代替后台设置的值；后台“基本设置”中不再显示“站点地址” | `var/Widget/Options.php:440`、`var/Widget/Options/General.php:100`、`:155` |
| `__TYPECHO_DYNAMIC_SITE_URL__` | 布尔，未定义 | 为真时站点地址跟随当前访问的域名 | `var/Widget/Options.php:442` |
| `__TYPECHO_ROOT_URL__` | 字符串，未定义 | 固定根地址（永久链接、后台、表单地址的前缀，含安装目录） | `var/Widget/Options.php:422` |
| `__TYPECHO_URL_PREFIX__` | 字符串，未定义 | 固定“协议 + 域名”部分，如 `https://blog.example.com`，安装目录仍按请求计算 | `var/Typecho/Request.php:614` |
| `__TYPECHO_SECURE__` | 布尔，未定义 | 为真时总是认为当前请求是 HTTPS | `var/Typecho/Request.php:462` |
| `__TYPECHO_IP_SOURCE__` | 请求头名，默认按 `X-Forwarded-For` | 从哪个请求头读取客户端 IP，如 `X-Real-IP`、`CF-Connecting-IP` | `var/Typecho/Request.php:393` |
| `__TYPECHO_COOKIE_OPTIONS__` | 数组，未定义 | Cookie 的 `domain`、`secure`、`httponly`，三个键都要写 | `var/Widget/Init.php:97`，生效于 `var/Typecho/Cookie.php` 的 `setOptions()` |

### 永久链接 {#rewrite}

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_REWRITE__` | 布尔，未定义 | 为真时总是生成不带 `index.php` 的地址，不再看后台的“是否使用地址重写功能”设置；后台“永久链接”页中这个选项和重写检测都不再出现。适合服务器访问不到自己、导致重写检测失败的环境。**定义后要确保服务器的重写规则确实生效**，否则所有文章都会 404 | `var/Widget/Options.php:549`、`var/Widget/Options/Permalink.php:167`、`:204` |
| `__TYPECHO_PATHINFO_ENCODING__` | 编码名，未定义 | 服务器传入的路径不是 UTF-8 时，先从这个编码转换成 UTF-8（源码注释说明是针对 IIS），如 `'GBK'` | `var/Typecho/Request.php:353` |

`__TYPECHO_REWRITE__` 为假（`false`）时同样会隐藏后台的选项，此时是否重写由数据库中原有的 `rewrite` 选项决定。

### 主题与插件

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_THEME_WRITEABLE__` | 布尔，未定义（视为可写） | 设为 `false` 时关闭后台的主题文件编辑器：编辑页返回 404，也不能保存。见[安全加固](./security.md#theme-editor) | `var/Widget/Themes/Edit.php:109`、`var/Widget/Themes/Files.php:54`、`:91` |
| `__TYPECHO_THEME_URL__` | 字符串，未定义 | 主题静态文件的地址前缀，用来把主题的 CSS、JS 放到 CDN。注意它代替的是**包含主题目录名**的完整地址（默认为 `站点地址/usr/themes/主题名`），所以换主题时要同步修改 | `var/Widget/Options.php:228` |
| `__TYPECHO_PLUGIN_URL__` | 字符串，未定义 | 插件目录的地址（默认为 `站点地址/usr/plugins`），用于把插件静态文件放到 CDN | `var/Widget/Options.php:570` |
| `__TYPECHO_LANG_DIR__` | 绝对路径，默认 `根目录/usr/langs` | 后台语言包（`.mo` 文件）所在目录 | `var/Widget/Init.php:86`、`var/Widget/Options/General.php:48` |

### 附件与备份

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_UPLOAD_DIR__` | 相对路径，默认 `/usr/uploads` | 附件的相对路径前缀，会写入每个附件的记录 | `var/Widget/Upload.php:377`、`:418` |
| `__TYPECHO_UPLOAD_ROOT_DIR__` | 绝对路径，默认为网站根目录 | 附件相对路径从哪个目录算起，即文件实际存放的位置 | `var/Widget/Upload.php:80`、`:194`、`:378` |
| `__TYPECHO_UPLOAD_URL__` | 字符串，默认为站点地址 | 附件地址前缀，生成的地址为“前缀 + 相对路径” | `var/Widget/Upload.php:60` |
| `__TYPECHO_BACKUP_DIR__` | 绝对路径，默认 `根目录/usr/backups` | 后台“备份 → 恢复数据 → 从服务器”读取备份文件的目录 | `var/Widget/Init.php:91`、`var/Widget/Backup.php:84`、`:209`、`admin/backup.php:57` |

附件三个常量的组合用法见[附件存储位置](./uploads-storage.md)，备份见[备份与恢复](./backup.md)。

### 其他

| 常量 | 类型 / 默认 | 作用 | 读取位置 |
|---|---|---|---|
| `__TYPECHO_GRAVATAR_PREFIX__` | 字符串，默认 `https://secure.gravatar.com/avatar/` | 头像服务地址，用于换成 Gravatar 镜像。Typecho 会在后面直接拼接邮箱的 MD5 和 `?s=尺寸` 等参数，所以要以 `/avatar/` 这样的路径结尾 | `var/Typecho/Common.php:853` |
| `__TYPECHO_SERVICE_URL__` | 字符串，未定义 | Typecho 向**自己**发送异步请求（发送引用通告、插件的异步任务）时使用的地址，会代替协议、域名和端口。适合服务器访问不到自己公网域名的环境，例如 `'http://127.0.0.1'` | `var/Widget/Service.php:203` |

## 不要自己定义的常量

| 常量 | 说明 |
|---|---|
| `__TYPECHO_ADMIN__` | 后台页面的标记，由 `admin/common.php:6` 定义 |
| `__TYPECHO_CLASS_ALIASES__` | 旧类名到新类名的映射表，由 `var/Widget/Init.php:57` 定义 |
| `__TYPECHO_DB_HOST__`、`__TYPECHO_DB_PORT__`、`__TYPECHO_DB_NAME__`、`__TYPECHO_DB_USER__`、`__TYPECHO_DB_PASS__`、`__TYPECHO_DB_CHAR__` | 只出现在 `var/Typecho/Db.php` 的一段旧注释里，程序并不读取。数据库连接请写在 `addServer()` 中 |

## 读写分离

`addServer()` 可以调用多次，分别指定用途。例如把读请求发到只读副本：

```php
$db = new \Typecho\Db('Pdo_Mysql', 'typecho_');
$db->addServer([/* 主库连接参数 */], \Typecho\Db::WRITE);
$db->addServer([/* 只读副本连接参数 */], \Typecho\Db::READ);
\Typecho\Db::set($db);
```

这只是 `Typecho\Db` 提供的能力，Typecho 不处理主从复制延迟：刚写入的数据可能暂时从只读副本中读不到，请谨慎使用。
