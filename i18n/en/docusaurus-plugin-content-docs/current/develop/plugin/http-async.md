---
title: HTTP Requests and Async Tasks
sidebar_position: 8
translation: machine
source_commit: fc55a1004f355202effc7d999ebe984ff3ec2cf2
---

# HTTP Requests and Async Tasks

Plugins often need to call external services: pushing notifications, querying an anti-spam API, uploading to object storage. Typecho ships with a curl-based HTTP client, `Typecho\Http\Client`. For requests whose result you do not need to wait for, you can also hand them off to an async task, which runs after the visitor's request has finished.

## Typecho\Http\Client

```php
use Typecho\Http\Client;

$client = Client::get();
if (!$client) {
    // PHP's curl extension is not installed on the server
    return;
}

try {
    $client->setHeader('Accept', 'application/json')
        ->setTimeout(5)
        ->setQuery(['q' => 'typecho'])
        ->send('https://api.example.com/search');

    $status = $client->getResponseStatus();               // 200
    $body = $client->getResponseBody();                   // response body
    $type = $client->getResponseHeader('Content-Type');   // response header; names are case-insensitive
} catch (Client\Exception $e) {
    // network errors such as connection failures and timeouts
}
```

`Client::get()` returns a new client object each time; it returns `null` when PHP has no curl extension. The setter methods all return the client itself, so you can chain them, and finally call `send($url)` to send the request.

| Method | Purpose |
|---|---|
| `setQuery($query)` | Appends URL query parameters, as an array or a string like `a=1&b=2` |
| `setData($data, $method = 'POST')` | Request body, as an array or a string; also sets the method to POST (or the method given as the second argument) |
| `setJson($data, $method = 'POST')` | Encodes `$data` as JSON for the request body and sets `Content-Type: application/json` |
| `setMultipart(bool $multipart)` | Which encoding to use when `setData()` receives an array; see below |
| `setFiles(array $files, $method = 'POST')` | Uploads files, `['字段名' => '本地路径']`, sent as `multipart/form-data`; can be combined with array fields from `setData()` |
| `setMethod($method)` | Request method; you can use the constants `Client::METHOD_GET`, `METHOD_POST`, `METHOD_PUT`, `METHOD_DELETE` |
| `setHeader($key, $value)` | Request header |
| `setCookie($key, $value)` | Cookie |
| `setTimeout(int $seconds)` | Timeout for the whole request, 3 seconds by default |
| `setOption(int $key, $value)` | Sets a curl option directly, e.g. `setOption(CURLOPT_SSL_VERIFYPEER, false)` |
| `send(string $url)` | Sends the request |
| `getResponseStatus()` | HTTP status code |
| `getResponseHeader($key)` | A response header, `null` if it does not exist |
| `getResponseBody()` | Response body |

Behavior to watch out for:

- **Only network errors throw exceptions.** When the server returns 4xx or 5xx, `send()` returns normally; you have to check `getResponseStatus()` yourself.
- **No User-Agent is sent by default.** Some services reject requests without a User-Agent. You can use `setHeader('User-Agent', \Widget\Options::alloc()->generator)` to set it to a value like `Typecho 1.3.0`.
- **When `setData()` receives an array, it is sent as `multipart/form-data` by default.** To send ordinary form encoding (`application/x-www-form-urlencoded`), call `setMultipart(false)` first:

  ```php
  $client->setMultipart(false)
      ->setData(['name' => '张三', 'text' => '你好'])
      ->send('https://api.example.com/messages');
  ```

- **`setQuery()` drops query parameters already in the URL**: in 1.3.0, if the URL passed to `send()` already contains `?a=1` and you append parameters with `setQuery()`, the request that goes out contains only the `setQuery()` parameters. So either put all query parameters in the URL, or pass all of them with `setQuery()`.

## Async tasks

Calling external services slows pages down: when a visitor submits a message and has to wait for the notification API to respond before seeing the page, they wait several extra seconds, and an API error can even affect the submission itself. Async tasks move this kind of work to after the visitor's request has finished:

```php
\Utils\Helper::requestService('guestbookNotify', $id);
```

How it works:

1. `requestService('任务名', 参数...)` only records the task.
2. When the current request ends, Typecho sends a POST request to the site's own `/action/service?do=async`, carrying all recorded tasks and a token valid for a few seconds (timeout 2 seconds).
3. That request verifies the token, **immediately finishes its response**, and then, in the background, triggers one by one the hooks on `Widget_Service` named after each task, passing the arguments to the callbacks.

So a plugin has to do two things: register the callback on `Widget\Service` in `activate()`, and call `requestService()` where needed.

Things to keep in mind when using async tasks:

- **They are only sent from requests that end with a redirect or direct output**: methods such as `goBack()`, `redirect()` and `throwJson()` trigger them, and actions and admin operations all end this way. Front-end pages that finish normal template rendering **do not** send the async request, so calling `requestService()` in hooks like `footer` has no effect.
- **Arguments are JSON-encoded**: you can only pass strings, numbers, booleans and arrays, not objects. Pass an ID and query the database in the callback.
- **The callback runs in a request with no logged-in user**, so it cannot rely on the current user's permissions or personal settings.
- **Errors in the callback are not shown to anyone**; write your own log if you need one.
- The site must be able to reach itself over its URL.

### __TYPECHO_SERVICE_URL__

Typecho builds the `/action/service` URL from the site URL (the `index` option). When the server cannot reach its own public domain (for example on an internal network, in a container, or when the public address goes through an outward-only CDN), the async request fails. In that case, define this in `config.inc.php`:

```php title="config.inc.php"
define('__TYPECHO_SERVICE_URL__', 'http://127.0.0.1');
```

Typecho uses its scheme, host and port in place of the corresponding parts of the site URL. It is also used for sending trackbacks; see [config.inc.php and constants](../../guide/operations/config-file.md).

## Example: new message notifications

Add notifications to the guestbook plugin from [Routes, Actions and Admin Panels](./routes-actions.md): the administrator enters a notification URL in the plugin settings, and whenever there is a new message, the plugin pushes it as JSON to that URL in the background.

Add three imports at the top of `Plugin.php`:

```php
use Typecho\Http\Client;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;
```

Register the async task callback at the end of `activate()`:

```php
\Typecho\Plugin::factory('Widget\Service')->guestbookNotify = __CLASS__ . '::notify';
```

Replace the empty `config()` with the code below and add a `notify()` method:

```php
public static function config(Form $form)
{
    $webhook = new Text('webhook', null, '', _t('通知地址'), _t('有新留言时向这个地址 POST 一个 JSON，留空则不通知'));
    $webhook->addRule('url', _t('请填写一个合法的 URL'));
    $form->addInput($webhook);
}

/**
 * Async task guestbookNotify: pushes the message to the notification URL
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

Add `use Utils\Helper;` at the top of `Action.php`, then change the insert statement in `add()` to get the new message's ID and schedule the task:

```php
$id = $this->db->query($this->db->insert('table.guestbook')->rows([
    'author'  => $author,
    'text'    => $text,
    'ip'      => $this->request->getIp(),
    'created' => time(),
]));

Helper::requestService('guestbookNotify', $id);
```

Because `activate()` has changed, deactivate and reactivate the plugin, then enter the notification URL in the plugin settings. From then on, each time a message is submitted, the page returns immediately as usual, and the notification URL then receives a request:

```text
POST /hook
User-Agent: Typecho 1.3.0
Content-Type: application/json

{"author":"李四","text":"异步通知测试","created":1792159301}
```
