---
title: functions.php Callbacks
sidebar_position: 6
translation: machine
source_commit: b0ec365a3ca43910ca5e09209e55c73aa58e7b60
---

# functions.php Callbacks

`functions.php` in the theme directory is not a template but a function file that is loaded when needed. Typecho looks for functions with specific names in it and calls them at specific moments. Besides these callbacks, you can also write your own helper functions here for templates to call (for example `postMeta()` in `classic-22`).

```php
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// Put callbacks and your own helper functions here
```

## Callbacks at a glance

| Function | When it is called | Purpose |
|---|---|---|
| `themeInit($archive)` | On every front-end request, after the data has been queried and before the template is output | Change the parameters of the current page, for example the number of posts per page |
| `themeConfig($form)` | When the "Theme settings" page is opened, and when the theme is activated | Define the theme settings form |
| `themeConfigHandle($settings, $isInit)` | When the theme settings are saved, and when the theme is activated | Handle saving the settings yourself |
| `themeFields($layout)` | When the post or page editor is opened, and on save | Predefine custom fields for posts and pages |
| `themePostFields($layout)` | As above, for posts only | Predefine fields for posts only |
| `themePageFields($layout)` | As above, for pages only | Predefine fields for pages only |
| `threadedComments($comments, $options)` | When `listComments()` outputs each comment | Customize the HTML of a single comment |
| `singlePing($pings, $options)` | When `listPings()` outputs each trackback | Customize the HTML of a single trackback (not usable in 1.3.0, see below) |

All callbacks are optional; when one is not defined, Typecho's default behavior applies.

## themeInit

```php
function themeInit($archive)
{
    if ($archive->is('category', 'tech')) {
        $archive->parameter->pageSize = 2;
    }
}
```

- The `$archive` argument is the `$this` of templates (`Widget\Archive`).
- When it is called: Typecho has already determined the page type from the URL and prepared the query, but **has not yet run the list query**, so changing `pageSize` here takes effect on the current list page. The example above makes the `tech` category show only 2 posts per page; `/category/tech/2/` is the second page.
- For posts and pages, the content has already been fetched before `themeInit` is called, so you can read properties such as `$archive->title` to make decisions.
- It is only called for front-end pages; the admin panel and feeds do not call it.

For more examples, see [Custom templates](./custom-templates.md#page-size).

## themeConfig and themeConfigHandle {#themeconfig-and-themeconfighandle}

```php
function themeConfig($form)
{
    $footerText = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerText',
        null,
        null,
        _t('页脚文字'),
        _t('显示在每个页面底部，可以留空')
    );
    $form->addInput($footerText);
}
```

- `$form` is a `\Typecho\Widget\Helper\Form` object; add form elements with `addInput()`. When this function is defined, a "Theme settings" tab appears on the Appearance page of the admin panel.
- The saved values are stored as JSON in the `theme:主题目录名` option (`theme:` followed by the theme directory name), and are read in templates with `$this->options->footerText`.

If `themeConfigHandle()` is defined as well, Typecho **no longer saves** the settings itself but hands the form values to it:

```php
function themeConfigHandle(array $settings, bool $isInit)
{
    // $settings: the values of all fields in the form
    // $isInit: true when the theme is activated, false when saved in "Theme settings" (设置外观)
}
```

When the theme is activated, `$settings` holds the default value of each field. If you use it to write the settings somewhere else (for example to generate a CSS file), you are responsible for storing them yourself, and these values cannot be read from `$this->options` either.

For the kinds of form elements and how to read them, see [Theme options](./theme-options.md).

## themeFields, themePostFields, themePageFields

```php
function themeFields($layout)
{
    $cover = new \Typecho\Widget\Helper\Form\Element\Text(
        'cover',
        null,
        null,
        _t('封面图地址'),
        _t('留空则不显示封面')
    );
    $layout->addItem($cover);
}
```

- `$layout` is a `\Typecho\Widget\Helper\Layout` object; add form elements with `addItem()`.
- Fields from `themeFields()` appear in both the post and the page editor; `themePostFields()` fields appear only in the post editor, and `themePageFields()` fields only in the page editor. All three can be defined at once, and the fields are shown together.
- The fields appear at the top of the "Custom fields" panel in the editor, and are read in templates with `$this->fields->cover`.

For details, see [Custom fields in templates](./custom-fields.md).

## threadedComments

```php
function threadedComments($comments, $options)
{
    // $comments: the current comment (Widget\Comments\Archive)
    // $options: the options of listComments() (Typecho\Config), e.g. $options->avatarSize
}
```

When this function is defined, `$comments->listComments()` no longer outputs Typecho's default comment HTML but calls it for each comment instead. The function must output the comment itself and, when there are child comments, call `$comments->threadedComments()` to output the replies recursively. For a complete example, see [Comment templates](./comments.md#callback).

## singlePing

`singlePing($pings, $options)` is meant to customize the output of each trackback in `$this->pings()->listPings()`. In 1.3.0, `$this->pings()` cannot be used (calling it causes a fatal error; see [Comment templates](./comments.md#pings)), so this callback is never called either. To list trackbacks separately, filter them from `comments()` as shown on the comment templates page.
