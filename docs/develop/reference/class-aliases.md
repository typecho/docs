---
title: 旧类名对照表
sidebar_position: 11
---

# 旧类名对照表

Typecho 1.2 起改用带命名空间的类名（如 `\Widget\Contents\Post\Recent`），1.3 继续沿用。为 1.3 之前的版本写的主题和插件中常见的下划线类名（如 `Widget_Contents_Post_Recent`）在 1.3 中仍然可以使用：自动加载器会把它们对应到新的类，并用 `class_alias()` 建立别名。迁移步骤见[主题与插件迁移到 1.3](../migrate-to-1-3.md)。

## 映射规则

自动加载器按下面的顺序处理一个找不到的类名：

1. **显式别名**：类名在下面的别名表中，直接对应到表中的新类。
2. **`Typecho_`、`Widget_`、`IXR_` 开头的下划线类名**：把 `_` 换成 `\`，例如 `Widget_Contents_Post_Recent` → `\Widget\Contents\Post\Recent`，`Typecho_Widget_Helper_Form_Element_Text` → `\Typecho\Widget\Helper\Form\Element\Text`。
3. **其他带下划线的类名**视为插件类：`插件名_类名` → `\TypechoPlugin\插件名\类名`，文件为 `usr/plugins/插件名/类名.php`。例如旧式插件的 `HelloWorld_Plugin` 对应 `\TypechoPlugin\HelloWorld\Plugin`。
4. 反过来，带命名空间的新类名也会自动得到下划线形式的别名。因此新旧两种写法指向同一个类，`instanceof` 判断和继承用哪种写法都可以。

插件钩子的句柄名同样做了归一：`\Typecho\Plugin::factory('Widget\Archive')` 与 `factory('Widget_Archive')` 是同一个句柄；显式别名表中的类以旧名作为句柄名（例如 `\Widget\Base\Contents` 的句柄是 `Widget_Abstract_Contents`）。

## 显式别名表

`var/Widget/Init.php` 中定义的 21 条：

| 旧类名 | 1.3 中的类 |
|---|---|
| `Typecho_Plugin_Interface` | `\Typecho\Plugin\PluginInterface` |
| `Typecho_Widget_Helper_Empty` | `\Typecho\Widget\Helper\EmptyClass` |
| `Typecho_Db_Adapter_Mysql` | `\Typecho\Db\Adapter\Mysqli` |
| `Widget_Abstract` | `\Widget\Base` |
| `Widget_Abstract_Contents` | `\Widget\Base\Contents` |
| `Widget_Abstract_Comments` | `\Widget\Base\Comments` |
| `Widget_Abstract_Metas` | `\Widget\Base\Metas` |
| `Widget_Abstract_Options` | `\Widget\Base\Options` |
| `Widget_Abstract_Users` | `\Widget\Base\Users` |
| `Widget_Metas_Category_List` | `\Widget\Metas\Category\Rows` |
| `Widget_Contents_Page_List` | `\Widget\Contents\Page\Rows` |
| `Widget_Plugins_List` | `\Widget\Plugins\Rows` |
| `Widget_Themes_List` | `\Widget\Themes\Rows` |
| `Widget_Interface_Do` | `\Widget\ActionInterface` |
| `Widget_Do` | `\Widget\Action` |
| `AutoP` | `\Utils\AutoP` |
| `PasswordHash` | `\Utils\PasswordHash` |
| `Markdown` | `\Utils\Markdown` |
| `HyperDown` | `\Utils\HyperDown` |
| `Helper` | `\Utils\Helper` |
| `Upgrade` | `\Utils\Upgrade` |


## 无法再使用的旧类名

下面这些 Typecho 1.1 的类名在 1.3.0 中无法解析：

| 旧类名 | 说明 |
|---|---|
| `Typecho_AutoP` | 已移至 `\Utils\AutoP`，旧名 `AutoP` 仍可用 |
| `Typecho_Db_Adapter_Exception` | 已拆分为 `\Typecho\Db\Adapter\ConnectionException` 和 `\Typecho\Db\Adapter\SQLException` |
| `Typecho_Http_Client_Adapter`、`Typecho_Http_Client_Adapter_Curl`、`Typecho_Http_Client_Adapter_Socket` | HTTP 客户端不再区分适配器，统一为 `\Typecho\Http\Client` |
| `Widget_Comments_Ping` | 对应的 `\Widget\Comments\Ping` 在 1.3.0 中加载时出现致命错误（`___parentContent()` 的返回类型与父类不兼容），见[评论模板](../theme/comments.md#pings) |

类名能够解析，不代表行为完全相同：方法签名、返回值可能已经改变，见[主题与插件迁移到 1.3](../migrate-to-1-3.md)。

## 旧类名一览

Typecho 1.1 中能在 1.3.0 中解析的 116 个旧类名及其对应的类：

| 旧类名 | 1.3 中的类 |
|---|---|
| `Typecho_Common` | `\Typecho\Common` |
| `Typecho_Config` | `\Typecho\Config` |
| `Typecho_Cookie` | `\Typecho\Cookie` |
| `Typecho_Date` | `\Typecho\Date` |
| `Typecho_Db` | `\Typecho\Db` |
| `Typecho_Db_Adapter` | `\Typecho\Db\Adapter` |
| `Typecho_Db_Adapter_Mysql` | `\Typecho\Db\Adapter\Mysqli` |
| `Typecho_Db_Adapter_Mysqli` | `\Typecho\Db\Adapter\Mysqli` |
| `Typecho_Db_Adapter_Pdo` | `\Typecho\Db\Adapter\Pdo` |
| `Typecho_Db_Adapter_Pdo_Mysql` | `\Typecho\Db\Adapter\Pdo\Mysql` |
| `Typecho_Db_Adapter_Pdo_Pgsql` | `\Typecho\Db\Adapter\Pdo\Pgsql` |
| `Typecho_Db_Adapter_Pdo_SQLite` | `\Typecho\Db\Adapter\Pdo\SQLite` |
| `Typecho_Db_Adapter_Pgsql` | `\Typecho\Db\Adapter\Pgsql` |
| `Typecho_Db_Adapter_SQLite` | `\Typecho\Db\Adapter\SQLite` |
| `Typecho_Db_Exception` | `\Typecho\Db\Exception` |
| `Typecho_Db_Query` | `\Typecho\Db\Query` |
| `Typecho_Db_Query_Exception` | `\Typecho\Db\Query\Exception` |
| `Typecho_Exception` | `\Typecho\Exception` |
| `Typecho_Feed` | `\Typecho\Feed` |
| `Typecho_Http_Client` | `\Typecho\Http\Client` |
| `Typecho_Http_Client_Exception` | `\Typecho\Http\Client\Exception` |
| `Typecho_I18n` | `\Typecho\I18n` |
| `Typecho_I18n_GetText` | `\Typecho\I18n\GetText` |
| `Typecho_I18n_GetTextMulti` | `\Typecho\I18n\GetTextMulti` |
| `Typecho_Plugin` | `\Typecho\Plugin` |
| `Typecho_Plugin_Exception` | `\Typecho\Plugin\Exception` |
| `Typecho_Plugin_Interface` | `\Typecho\Plugin\PluginInterface` |
| `Typecho_Request` | `\Typecho\Request` |
| `Typecho_Response` | `\Typecho\Response` |
| `Typecho_Router` | `\Typecho\Router` |
| `Typecho_Router_Exception` | `\Typecho\Router\Exception` |
| `Typecho_Router_Parser` | `\Typecho\Router\Parser` |
| `Typecho_Validate` | `\Typecho\Validate` |
| `Typecho_Widget` | `\Typecho\Widget` |
| `Typecho_Widget_Exception` | `\Typecho\Widget\Exception` |
| `Typecho_Widget_Helper_Empty` | `\Typecho\Widget\Helper\EmptyClass` |
| `Typecho_Widget_Helper_Form` | `\Typecho\Widget\Helper\Form` |
| `Typecho_Widget_Helper_Form_Element` | `\Typecho\Widget\Helper\Form\Element` |
| `Typecho_Widget_Helper_Form_Element_Checkbox` | `\Typecho\Widget\Helper\Form\Element\Checkbox` |
| `Typecho_Widget_Helper_Form_Element_Fake` | `\Typecho\Widget\Helper\Form\Element\Fake` |
| `Typecho_Widget_Helper_Form_Element_Hidden` | `\Typecho\Widget\Helper\Form\Element\Hidden` |
| `Typecho_Widget_Helper_Form_Element_Password` | `\Typecho\Widget\Helper\Form\Element\Password` |
| `Typecho_Widget_Helper_Form_Element_Radio` | `\Typecho\Widget\Helper\Form\Element\Radio` |
| `Typecho_Widget_Helper_Form_Element_Select` | `\Typecho\Widget\Helper\Form\Element\Select` |
| `Typecho_Widget_Helper_Form_Element_Submit` | `\Typecho\Widget\Helper\Form\Element\Submit` |
| `Typecho_Widget_Helper_Form_Element_Text` | `\Typecho\Widget\Helper\Form\Element\Text` |
| `Typecho_Widget_Helper_Form_Element_Textarea` | `\Typecho\Widget\Helper\Form\Element\Textarea` |
| `Typecho_Widget_Helper_Layout` | `\Typecho\Widget\Helper\Layout` |
| `Typecho_Widget_Helper_PageNavigator` | `\Typecho\Widget\Helper\PageNavigator` |
| `Typecho_Widget_Helper_PageNavigator_Box` | `\Typecho\Widget\Helper\PageNavigator\Box` |
| `Typecho_Widget_Helper_PageNavigator_Classic` | `\Typecho\Widget\Helper\PageNavigator\Classic` |
| `Widget_Abstract` | `\Widget\Base` |
| `Widget_Abstract_Comments` | `\Widget\Base\Comments` |
| `Widget_Abstract_Contents` | `\Widget\Base\Contents` |
| `Widget_Abstract_Metas` | `\Widget\Base\Metas` |
| `Widget_Abstract_Options` | `\Widget\Base\Options` |
| `Widget_Abstract_Users` | `\Widget\Base\Users` |
| `Widget_Ajax` | `\Widget\Ajax` |
| `Widget_Archive` | `\Widget\Archive` |
| `Widget_Backup` | `\Widget\Backup` |
| `Widget_Comments_Admin` | `\Widget\Comments\Admin` |
| `Widget_Comments_Archive` | `\Widget\Comments\Archive` |
| `Widget_Comments_Edit` | `\Widget\Comments\Edit` |
| `Widget_Comments_Recent` | `\Widget\Comments\Recent` |
| `Widget_Contents_Attachment_Admin` | `\Widget\Contents\Attachment\Admin` |
| `Widget_Contents_Attachment_Edit` | `\Widget\Contents\Attachment\Edit` |
| `Widget_Contents_Attachment_Related` | `\Widget\Contents\Attachment\Related` |
| `Widget_Contents_Attachment_Unattached` | `\Widget\Contents\Attachment\Unattached` |
| `Widget_Contents_Page_Admin` | `\Widget\Contents\Page\Admin` |
| `Widget_Contents_Page_Edit` | `\Widget\Contents\Page\Edit` |
| `Widget_Contents_Page_List` | `\Widget\Contents\Page\Rows` |
| `Widget_Contents_Post_Admin` | `\Widget\Contents\Post\Admin` |
| `Widget_Contents_Post_Date` | `\Widget\Contents\Post\Date` |
| `Widget_Contents_Post_Edit` | `\Widget\Contents\Post\Edit` |
| `Widget_Contents_Post_Recent` | `\Widget\Contents\Post\Recent` |
| `Widget_Contents_Related` | `\Widget\Contents\Related` |
| `Widget_Contents_Related_Author` | `\Widget\Contents\Related\Author` |
| `Widget_Do` | `\Widget\Action` |
| `Widget_ExceptionHandle` | `\Widget\ExceptionHandle` |
| `Widget_Feedback` | `\Widget\Feedback` |
| `Widget_Init` | `\Widget\Init` |
| `Widget_Interface_Do` | `\Widget\ActionInterface` |
| `Widget_Login` | `\Widget\Login` |
| `Widget_Logout` | `\Widget\Logout` |
| `Widget_Menu` | `\Widget\Menu` |
| `Widget_Metas_Category_Admin` | `\Widget\Metas\Category\Admin` |
| `Widget_Metas_Category_Edit` | `\Widget\Metas\Category\Edit` |
| `Widget_Metas_Category_List` | `\Widget\Metas\Category\Rows` |
| `Widget_Metas_Tag_Admin` | `\Widget\Metas\Tag\Admin` |
| `Widget_Metas_Tag_Cloud` | `\Widget\Metas\Tag\Cloud` |
| `Widget_Metas_Tag_Edit` | `\Widget\Metas\Tag\Edit` |
| `Widget_Notice` | `\Widget\Notice` |
| `Widget_Options` | `\Widget\Options` |
| `Widget_Options_Discussion` | `\Widget\Options\Discussion` |
| `Widget_Options_General` | `\Widget\Options\General` |
| `Widget_Options_Permalink` | `\Widget\Options\Permalink` |
| `Widget_Options_Reading` | `\Widget\Options\Reading` |
| `Widget_Plugins_Config` | `\Widget\Plugins\Config` |
| `Widget_Plugins_Edit` | `\Widget\Plugins\Edit` |
| `Widget_Plugins_List` | `\Widget\Plugins\Rows` |
| `Widget_Register` | `\Widget\Register` |
| `Widget_Security` | `\Widget\Security` |
| `Widget_Service` | `\Widget\Service` |
| `Widget_Stat` | `\Widget\Stat` |
| `Widget_Themes_Config` | `\Widget\Themes\Config` |
| `Widget_Themes_Edit` | `\Widget\Themes\Edit` |
| `Widget_Themes_Files` | `\Widget\Themes\Files` |
| `Widget_Themes_List` | `\Widget\Themes\Rows` |
| `Widget_Upgrade` | `\Widget\Upgrade` |
| `Widget_Upload` | `\Widget\Upload` |
| `Widget_User` | `\Widget\User` |
| `Widget_Users_Admin` | `\Widget\Users\Admin` |
| `Widget_Users_Author` | `\Widget\Users\Author` |
| `Widget_Users_Edit` | `\Widget\Users\Edit` |
| `Widget_Users_Profile` | `\Widget\Users\Profile` |
| `Widget_XmlRpc` | `\Widget\XmlRpc` |
