---
title: 安全加固
sidebar_position: 9
---

# 安全加固

本页列出部署 Typecho 后值得做的安全设置，按重要程度排列，适用于 Typecho 1.3。

## 1. 及时升级 {#upgrade}

最有效的一条。新版本发布时，后台控制台会显示“官方最新版本是 …”的提示；也可以关注 [GitHub Releases](https://github.com/typecho/typecho/releases)。升级步骤见[升级](../getting-started/upgrade.md)。

主题和插件同样需要更新。长期没有维护、没有说明支持 1.3 的插件，能不用就不用。

## 2. 报告安全漏洞 {#report}

Typecho 仓库的 `SECURITY.md` 规定：漏洞请发邮件到 **security@typecho.org** 报告。

请不要在论坛、GitHub Issues 或其他公开场合发布漏洞细节，在修复版本发布之前公开细节会让所有站点暴露在风险中。第三方主题、插件的漏洞请联系它们的作者。

## 3. 安装后删除安装程序 {#remove-installer}

安装完成后，`install.php` 和 `install/` 目录就不再需要了：Typecho 运行时和后台升级程序都不会用到它们（已在源码中检索确认）。

```bash
cd /srv/www/typecho
rm -rf install.php install/
```

站点安装完成后再访问 `install.php`，它会直接跳转到首页，并不能重新安装。删除它是为了防范意外情况：安装程序判断“是否已安装”的依据是配置文件和数据表（`install.php` 中的 `install_check()`），如果数据表被误删或数据库被清空，它会认为站点尚未安装，访问者就可能借它在你的数据库上重新建表并创建管理员。

[升级](../getting-started/upgrade.md)时新版本安装包会带回这两项，不复制它们即可。

## 4. 文件权限 {#permissions}

Typecho 运行时只需要写入很少的位置（详细列表见[容器与云主机部署](./containers.md#read-only)），其余文件都应该让 PHP 进程**只读**。这样即使出现漏洞，攻击者也很难改写程序文件。

| 路径 | PHP 进程需要 | 建议 |
|---|---|---|
| `usr/uploads/` | 读写 | 属于 PHP 进程的用户（如 `www-data`），目录 `755` |
| SQLite 数据库文件及其所在目录 | 读写 | 同上；最好放到网站目录之外，见下一节 |
| `config.inc.php` | 只读 | `640`，属于部署用户、组为 PHP 进程的组 |
| 其他所有文件和目录 | 只读 | 属于部署用户，目录 `755`、文件 `644` |

一个示例（部署用户 `deploy`，PHP 以 `www-data` 运行）：

```bash
cd /srv/www/typecho
chown -R deploy:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 640 config.inc.php
chown -R www-data:www-data usr/uploads
```

这样设置之后会有两个影响：

- 后台的主题编辑器无法保存文件。这正是推荐的状态，见[第 6 条](#theme-editor)；
- 在 Apache `mod_php` 下开启永久链接时，Typecho 不能自动写入 `.htaccess`，需要手动创建，见[Web 服务器配置](./web-server.md#htaccess)。

“全部文件 chmod 777”或“chmod 644 就够了”之类的做法都不适用：前者让 PHP 可以改写一切，后者会让上传失败。

## 5. 不让敏感文件被下载 {#sensitive-files}

| 文件 | 风险 | 处理 |
|---|---|---|
| SQLite 数据库（安装时默认放在 `usr/` 下，文件名随机，扩展名 `.db`） | 文件名被猜到或泄露后，可以直接下载整个数据库，包括用户的密码哈希 | 安装时把数据库文件放到网站目录之外（命令行安装用 `TYPECHO_DB_FILE` 指定绝对路径）；已经安装的站点，在 Web 服务器上禁止访问 `.db` 文件 |
| `usr/backups/` 中的 `.dat` 备份 | 包含全部内容和用户数据 | 恢复后删除，或禁止访问这个目录；也可以用 `__TYPECHO_BACKUP_DIR__` 把备份目录移到网站目录之外 |
| `usr/uploads/` 中的脚本 | 一旦有可执行脚本被上传，就可能被执行 | 禁止在上传目录执行 PHP |
| `config.inc.php` 的副本（如 `.bak`、`.swp`、`~` 结尾的文件） | 以纯文本暴露数据库密码 | 不要在网站目录中留下这类文件 |

nginx、Apache、Caddy 的对应规则见[Web 服务器配置](./web-server.md)，其中的示例配置已经包含了前三项。

## 6. 关闭在线主题编辑器 {#theme-editor}

后台“控制台 → 外观 → 编辑当前外观”可以直接修改主题的 `.php` 文件。管理员帐号一旦被盗，攻击者就能借此在服务器上执行任意代码。不需要在线改主题时，在 `config.inc.php` 中关闭它：

```php
define('__TYPECHO_THEME_WRITEABLE__', false);
```

关闭后，“外观”页上的“编辑当前外观”标签页不再显示，直接访问编辑器页面返回 404，也无法通过表单提交保存（`var/Widget/Themes/Files.php`、`var/Widget/Themes/Edit.php`）。

## 7. 自定义后台路径 {#admin-path}

把 `/admin/` 改成别的名字，可以减少针对默认路径的扫描和暴力破解，做法见[自定义后台路径](./admin-dir.md)。这只是减少噪音的手段：登录表单仍然提交到固定的 `/index.php/action/login`，主题也可能在页面上显示后台链接。

## 8. 帐号与功能开关

- **使用强密码**，每个管理员一个帐号，不要共用。Typecho 在登录失败时会等待 3 秒再返回（`var/Widget/Login.php`），但没有失败次数限制，也没有两步验证。需要的话可以在 Web 服务器上对登录地址 `/index.php/action/login` 限流，或使用插件（Typecho 提供了 `loginFailure` 插件接口）。
- **不需要注册时关闭注册**：“设置 → 基本 → 是否允许注册”。开放注册时，新用户默认是“关注者”（`subscriber`），没有写入权限。
- **不用离线客户端时关闭 XML-RPC**：“设置 → 基本 → XMLRPC 接口”，可选“关闭”“仅关闭 Pingback 接口”“打开”。
- **附件类型只允许必要的类型**：“设置 → 基本”中的允许上传的文件类型。Typecho 只按扩展名白名单判断，不要在自定义类型里加入 `php`、`phtml`、`phar`、`html`、`htm` 等可执行或可被浏览器解析为网页的类型。SVG 文件中也可以包含脚本，同样要谨慎。

## 9. HTTPS 与 Cookie {#https}

- 全站启用 HTTPS。在反向代理或 CDN 后面时，要让 Typecho 正确识别 HTTPS，见[HTTPS、反向代理与 CDN](./https-proxy.md)。
- 全站只用 HTTPS 时，用 `__TYPECHO_COOKIE_OPTIONS__` 为登录 Cookie 加上 `Secure` 和 `HttpOnly` 属性，见[同一页](./https-proxy.md#cookie-options)。
- 没有代理的站点，访客可以伪造 `X-Forwarded-For` 请求头来改变 Typecho 记录的 IP，见[客户端 IP](./https-proxy.md#ip-source)。

## 10. 备份 {#backup}

被入侵后最可靠的恢复手段是一份干净的备份。按[备份与恢复](./backup.md)定期做完整备份，并保存在网站服务器之外。

被入侵后：先下线站点；从官方安装包重新部署程序文件，不要沿用服务器上的旧文件；检查 `usr/` 下的主题、插件和上传目录中是否有可疑的 PHP 文件；修改所有管理员密码和数据库密码；然后升级到最新版本。
