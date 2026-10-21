---
title: Theme Options
sidebar_position: 8
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Theme Options

A theme can provide its own options so that site owners can adjust the logo, color scheme, sidebar and so on without changing code. Options are defined in `themeConfig()` in `functions.php`, site owners fill them in under "Console → Appearance → Theme settings" in the admin panel, and templates read them through `$this->options`.

## Defining the settings form

```php title="usr/themes/starter/functions.php"
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Logo URL'),
        _t('Image URL; leave empty to show the site name')
    );
    $form->addInput($logoUrl->addRule('url', _t('Enter a valid URL')));

    $colorScheme = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorScheme',
        ['auto' => _t('Follow system'), 'light' => _t('Light'), 'dark' => _t('Dark')],
        'auto',
        _t('Color scheme')
    );
    $form->addInput($colorScheme);

    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        ['recent' => _t('Latest posts'), 'comments' => _t('Recent comments'), 'categories' => _t('Categories')],
        ['recent', 'categories'],
        _t('Sidebar blocks')
    );
    $form->addInput($sidebarBlock->multiMode());

    $footerText = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerText',
        null,
        null,
        _t('Footer text'),
        _t('Shown at the bottom of every page; HTML is allowed')
    );
    $form->addInput($footerText);
}
```

All form elements live in the `\Typecho\Widget\Helper\Form\Element\` namespace. The constructor arguments are, in order:

```text
new Element(string $name, ?array $options, $value, ?string $label, ?string $description)
```

| Argument | Description |
|---|---|
| `$name` | The field name, which is also the name used to read it in templates |
| `$options` | An array of options `value => label`, for `Radio`, `Checkbox` and `Select`; pass `null` for text elements |
| `$value` | The default value; for `Checkbox`, an array of the values checked by default |
| `$label` | The field title |
| `$description` | The description below the field; may be left empty |

Common elements:

| Class | UI | Value read |
|---|---|---|
| `Text` | Single-line text box | string |
| `Textarea` | Multi-line text box | string |
| `Radio` | Radio buttons | the value of the selected option |
| `Select` | Drop-down | the value of the selected option |
| `Checkbox` | Checkboxes | an array of the values of the checked options |

- `addRule()` adds a validation rule, for example `addRule('url', 'Message')` or `addRule('required', 'Message')`. When validation fails, the form is not saved and the message is shown below the field.
- `multiMode()` puts each option of a radio / checkbox group on its own line; it only affects layout.

## How values are stored

When the site owner clicks "Save settings", the values of all fields are saved as JSON in the row named `theme:<directory>` (`theme:` followed by the theme directory name) of the `options` table in the database, for example:

```text
theme:starter  {"logoUrl":"https://example.com/logo.png","colorScheme":"auto","sidebarBlock":["recent","categories"],"footerText":""}
```

- **When the theme is activated**, Typecho first saves a copy using the form's default values;
- A field added to `themeConfig()` after the theme has been activated **has no value** (reads as `null`) until the site owner saves the settings again, so templates must check for it;
- When the site owner switches to another theme, this row is deleted; after switching back, the values return to the defaults.

## Reading them in templates

Theme options are merged into the site settings, so read them with `$this->options->fieldName`:

```php
<?php if ($this->options->logoUrl): ?>
    <img src="<?php $this->options->logoUrl(); ?>" alt="<?php $this->options->title(); ?>">
<?php else: ?>
    <?php $this->options->title(); ?>
<?php endif; ?>

<html data-theme="<?php $this->options->colorScheme(); ?>">

<?php if (!empty($this->options->sidebarBlock) && in_array('recent', $this->options->sidebarBlock)): ?>
    <!-- Show recent posts -->
<?php endif; ?>
```

- `$this->options->logoUrl` returns the value, and `$this->options->logoUrl()` outputs it directly. Direct output **does no HTML escaping**: what the user entered goes into the page as is. When the value goes into an HTML attribute and the site owner should not be able to inject HTML, use `echo htmlspecialchars($this->options->logoUrl);`.
- The value of a `Checkbox` is an array; check it with `in_array()`. It is `null` when it has never been saved, so check with `!empty()` first.
- In functions in `functions.php`, you can read the same values with `\Widget\Options::alloc()->logoUrl`.

:::caution Do not give fields the same name as a site setting
Theme options **override** site settings of the same name. For example, if a theme defines a field named `description` and the site owner fills it in, both `$this->options->description` and the `<meta name="description">` in the page header become that value, and the site description under "Settings → General" no longer takes effect; on the "Theme settings" page, this field is also prefilled with the site description. `title`, `description`, `keywords`, `siteUrl`, `pageSize`, `theme` and others are all names of site settings; add a prefix to theme fields or pick a different name. Even if you later remove the field from `themeConfig()`, the saved value keeps overriding until the site owner saves the theme settings once more.
:::

## Handling saving yourself: themeConfigHandle

When `themeConfigHandle(array $settings, bool $isInit)` is defined, Typecho no longer writes the settings to `theme:<directory>` but calls this function instead; see [functions.php callbacks](./functions-php.md#themeconfig-and-themeconfighandle).
