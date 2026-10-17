---
title: HTTP 请求与异步任务
sidebar_position: 8
---

# HTTP 请求与异步任务

插件经常需要调用外部服务：推送通知、请求反垃圾接口、上传到对象存储。Typecho 自带一个基于 curl 的 HTTP 客户端 `Typecho\Http\Client`；对于不需要等待结果的请求，还可以交给异步任务，由站点向自己发起的另一个请求执行，访客的请求不必等它完成。

## Typecho\Http\Client

```php
use Typecho\Http\Client;

$client = Client::get();
if (!$client) {
    // 服务器没有安装 PHP 的 curl 扩展
    return;
}

try {
    $client->setHeader('Accept', 'application/json')
        ->setTimeout(5)
        ->setQuery(['q' => 'typecho'])
        ->send('https://api.example.com/search');

    $status = $client->getResponseStatus();               // 200
    $body = $client->getResponseBody();                   // 响应正文
    $type = $client->getResponseHeader('Content-Type');   // 响应头，名字不区分大小写
} catch (Client\Exception $e) {
    // 连接失败、超时等网络错误
}
```

`Client::get()` 每次返回一个新的客户端对象；PHP 没有 curl 扩展时返回 `null`。设置方法都返回客户端本身，可以连着写，最后调用 `send($url)` 发出请求。

| 方法 | 作用 |
|---|---|
| `setQuery($query)` | 追加 URL 查询参数，数组或 `a=1&b=2` 形式的字符串 |
| `setData($data, $method = 'POST')` | 请求体，数组或字符串，同时把方法设为 POST（或第二个参数指定的方法） |
| `setJson($data, $method = 'POST')` | 把 `$data` 编码为 JSON 作为请求体，并设置 `Content-Type: application/json` |
| `setMultipart(bool $multipart)` | `setData()` 传数组时用哪种编码，见下文 |
| `setFiles(array $files, $method = 'POST')` | 上传文件，`['字段名' => '本地路径']`，以 `multipart/form-data` 发送，可以与 `setData()` 的数组字段一起使用 |
| `setMethod($method)` | 请求方法，可以用常量 `Client::METHOD_GET`、`METHOD_POST`、`METHOD_PUT`、`METHOD_DELETE` |
| `setHeader($key, $value)` | 请求头 |
| `setCookie($key, $value)` | Cookie |
| `setTimeout(int $seconds)` | 整个请求的超时时间，默认 3 秒 |
| `setOption(int $key, $value)` | 直接设置 curl 选项，如 `setOption(CURLOPT_SSL_VERIFYPEER, false)` |
| `send(string $url)` | 发出请求 |
| `getResponseStatus()` | HTTP 状态码 |
| `getResponseHeader($key)` | 某个响应头，不存在时为 `null` |
| `getResponseBody()` | 响应正文 |

需要注意的行为：

- **只有网络错误才抛异常**。服务器返回 4xx、5xx 时 `send()` 正常返回，要自己检查 `getResponseStatus()`；
- **默认不发送 User-Agent**。有些服务会拒绝没有 User-Agent 的请求，可以用 `setHeader('User-Agent', \Widget\Options::alloc()->generator)` 设为 `Typecho 1.3.0` 这样的值；
- **`setData()` 传数组时默认以 `multipart/form-data` 发送**。要发送普通的表单编码（`application/x-www-form-urlencoded`），先调用 `setMultipart(false)`：

  ```php
  $client->setMultipart(false)
      ->setData(['name' => '张三', 'text' => '你好'])
      ->send('https://api.example.com/messages');
  ```

- **`setQuery()` 会丢掉地址中已有的查询参数**：1.3.0 中，`send()` 的地址本身带有 `?a=1` 时，再用 `setQuery()` 追加参数，发出的请求中只剩 `setQuery()` 的参数。所以查询参数要么全部写在地址中，要么全部用 `setQuery()` 传入。

## 异步任务

调用外部服务会拖慢页面：访客提交一条留言，如果要等通知接口返回才能看到页面，就要多等几秒，接口出错时还会影响提交本身。异步任务把这类工作交给另一个请求去做：

```php
\Utils\Helper::requestService('guestbookNotify', $id);
```

工作过程：

1. `requestService('任务名', 参数...)` 只是把任务记下来；
2. 当前请求输出完毕、退出之前，Typecho 向站点自己的 `/action/service?do=async` 发一个 POST 请求，带上所有记下来的任务和一个有效期几秒钟的令牌（超时 2 秒）。这一步是同步的，当前请求要等对方结束响应（或超时）才真正结束，最多多花 2 秒；
3. 那个请求验证令牌后，**立即结束对它的响应**，然后在后台逐个触发 `Widget_Service` 上与任务名同名的钩子，把参数传给回调。

所以插件要做两件事：在 `activate()` 中把回调注册到 `Widget\Service` 上，在需要的地方调用 `requestService()`。

使用异步任务时要注意：

- **只在以跳转或直接输出结束的请求中才会发出**：`goBack()`、`redirect()`、`throwJson()` 等方法会触发它，Action 和后台操作都是这样结束的。前台模板正常渲染结束的页面**不会**发出异步请求，在 `footer` 之类的钩子中调用 `requestService()` 没有效果；
- **参数会经过 JSON 编码**：只能传字符串、数字、布尔值和数组，不能传对象。传 ID，再在回调中查数据库；
- **回调运行在一个没有登录用户的请求中**，不能依赖当前用户的权限或个人设置；
- **回调中的错误不会显示给任何人**，需要的话自己写日志；
- 站点必须能通过网址访问到自己。

### __TYPECHO_SERVICE_URL__

Typecho 用站点地址（`index` 选项）拼出 `/action/service` 的地址。服务器访问不到自己的公网域名时（例如在内网、容器中，或公网地址经过只对外的 CDN），异步请求会失败。这时在 `config.inc.php` 中定义：

```php title="config.inc.php"
define('__TYPECHO_SERVICE_URL__', 'http://127.0.0.1');
```

Typecho 会用它的协议、主机和端口代替站点地址中的对应部分。它同样用于发送引用通告，见 [config.inc.php 与常量](../../guide/operations/config-file.md)。

## 示例：新留言通知

给[路由、Action 与后台面板](./routes-actions.md)中的留言板插件加上通知：管理员在插件设置中填写一个通知地址，每当有新留言，插件在后台把留言以 JSON 推送到这个地址。

在 `Plugin.php` 开头增加三个导入：

```php
use Typecho\Http\Client;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;
```

在 `activate()` 的末尾注册异步任务的回调：

```php
\Typecho\Plugin::factory('Widget\Service')->guestbookNotify = __CLASS__ . '::notify';
```

把空的 `config()` 换成下面的代码，并加上 `notify()` 方法：

```php
public static function config(Form $form)
{
    $webhook = new Text('webhook', null, '', _t('通知地址'), _t('有新留言时向这个地址 POST 一个 JSON，留空则不通知'));
    $webhook->addRule('url', _t('请填写一个合法的 URL'));
    $form->addInput($webhook);
}

/**
 * 异步任务 guestbookNotify：把留言推送到通知地址
 */
public static function notify($id)
{
    $webhook = Options::alloc()->plugin('Guestbook')->webhook;
    $client = Client::get();
    if (empty($webhook) || !$client) {
        return;
    }

    $db = Db::get();
    $message = $db->fetchRow($db->select()->from('table.guestbook')->where('id = ?', $id));
    if (empty($message)) {
        return;
    }

    try {
        $client->setHeader('User-Agent', Options::alloc()->generator)
            ->setTimeout(10)
            ->setJson([
                'author'  => $message['author'],
                'text'    => $message['text'],
                'created' => (int) $message['created'],
            ])
            ->send($webhook);
    } catch (Client\Exception $e) {
        error_log('Guestbook webhook: ' . $e->getMessage());
    }
}
```

在 `Action.php` 开头增加 `use Utils\Helper;`，然后把 `add()` 中的插入语句改为取得新留言的 ID 并安排任务：

```php
$id = $this->db->query($this->db->insert('table.guestbook')->rows([
    'author'  => $author,
    'text'    => $text,
    'ip'      => $this->request->getIp(),
    'created' => time(),
]));

Helper::requestService('guestbookNotify', $id);
```

`activate()` 有改动，所以要禁用再启用插件，然后在插件设置中填写通知地址。之后每提交一条留言，页面不必等通知接口返回就会跳转回来，随后通知地址收到一个请求：

```text
POST /hook
User-Agent: Typecho 1.3.0
Content-Type: application/json

{"author":"李四","text":"异步通知测试","created":1792159301}
```
