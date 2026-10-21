---
title: Legacy Class Names
sidebar_position: 11
translation: machine
source_commit: 342fdf14c307f4efef7d31add7e5c261f14c71b4
---

# Legacy Class Names

Since 1.2, Typecho uses namespaced class names (such as `\Widget\Contents\Post\Recent`), and 1.3 keeps them. The underscore class names (such as `Widget_Contents_Post_Recent`) common in themes and plugins written for versions before 1.3 still work in 1.3: the autoloader maps them to the new classes and creates aliases with `class_alias()`. For the migration steps, see [Migrating Themes and Plugins to 1.3](../migrate-to-1-3.md).

## Mapping rules

The autoloader handles a class name it cannot find in the following order:

1. **Explicit aliases**: if the class name is in the alias table below, it maps directly to the new class in the table.
2. **Underscore class names starting with `Typecho_`, `Widget_` or `IXR_`**: `_` is replaced with `\`, for example `Widget_Contents_Post_Recent` → `\Widget\Contents\Post\Recent`, `Typecho_Widget_Helper_Form_Element_Text` → `\Typecho\Widget\Helper\Form\Element\Text`.
3. **Other class names containing underscores** are treated as plugin classes: `PluginName_ClassName` → `\TypechoPlugin\PluginName\ClassName`, in the file `usr/plugins/PluginName/ClassName.php`. For example, `HelloWorld_Plugin` in an old-style plugin maps to `\TypechoPlugin\HelloWorld\Plugin`.
4. Conversely, new namespaced class names also get an underscore alias automatically. Both forms therefore point to the same class, and `instanceof` checks and inheritance work with either name.

Plugin hook handle names are normalized in the same way: `\Typecho\Plugin::factory('Widget\Archive')` and `factory('Widget_Archive')` are the same handle; classes in the explicit alias table use the old name as the handle name (for example, the handle of `\Widget\Base\Contents` is `Widget_Abstract_Contents`).

## Explicit alias table

The 21 aliases defined in `var/Widget/Init.php`:

| Old class name | Class in 1.3 |
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


## Old class names that no longer work

The following Typecho 1.1 class names cannot be resolved in 1.3.0:

| Old class name | Notes |
|---|---|
| `Typecho_AutoP` | Moved to `\Utils\AutoP`; the old name `AutoP` still works |
| `Typecho_Db_Adapter_Exception` | Split into `\Typecho\Db\Adapter\ConnectionException` and `\Typecho\Db\Adapter\SQLException` |
| `Typecho_Http_Client_Adapter`, `Typecho_Http_Client_Adapter_Curl`, `Typecho_Http_Client_Adapter_Socket` | The HTTP client no longer has separate adapters; it is unified as `\Typecho\Http\Client` |
| `Widget_Comments_Ping` | The corresponding `\Widget\Comments\Ping` causes a fatal error when loaded in 1.3.0 (the return type of `___parentContent()` is incompatible with the parent class); see [Comments Template](../theme/comments.md#pings) |

A class name that resolves does not necessarily behave the same: method signatures and return values may have changed. See [Migrating Themes and Plugins to 1.3](../migrate-to-1-3.md).

## All old class names

The 116 old class names from Typecho 1.1 that resolve in 1.3.0, and the classes they map to:

| Old class name | Class in 1.3 |
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
