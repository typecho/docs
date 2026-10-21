---
title: Custom Fields in Templates
sidebar_position: 9
translation: machine
source_commit: 715b32e6e39938f9f8c621d5604038433f977c15
---

# Custom Fields in Templates

Custom fields are extra data attached to a post or page, such as a subtitle, a cover image or a view count. Authors fill them in in the "Custom fields" panel of the editor (see [Custom fields (editor side)](../../guide/content/custom-fields.md)), and themes read them in templates. A theme can also declare fields in advance so that they appear as a form in the editor.

## Reading fields

On post and page views, or inside a post list loop, use `$this->fields->fieldName`:

```php
<?php if ($this->fields->subtitle): ?>
    <p class="subtitle"><?php $this->fields->subtitle(); ?></p>
<?php endif; ?>

<?php if ($this->fields->cover): ?>
    <img src="<?php $this->fields->cover(); ?>" alt="">
<?php endif; ?>
```

- `$this->fields->subtitle` returns the value; `$this->fields->subtitle()` outputs it directly **without HTML escaping**.
- When a field does not exist, `$this->fields->subtitle` is `null`, so you can use it directly in an `if`. But calling the output method on a field that does not exist (`$this->fields->nothing()`) produces an `Undefined array key` warning, so check before you output.
- The type of the value read depends on the field type: "String" gives a string, "Integer" an `int`, "Decimal" a `float`, and "JSON structure" the decoded array or value. For example, if the author sets `views` to the integer 42, `$this->fields->views + 1` gives `43`.
- This works the same inside the loops of other widgets, for example `$posts->fields->cover`.

:::caution "JSON structure" in the editor panel
In 1.3.0, for a field that the author adds manually in the editor panel with the type "JSON structure", the template reads the raw text string rather than an array (see the [note](../../guide/content/custom-fields.md#types) on the editor-side page). If your theme depends on structured data, either have authors enter the JSON with the "String" type and decode it with `json_decode()` in the template, or have the theme predefine multi-value fields such as checkboxes as described below.
:::

## Predefined fields

Define any of the following three functions in `functions.php`; the fields they declare appear as a form at the top of the "Custom fields" panel in the editor:

| Function | Fields appear in |
|---|---|
| `themeFields($layout)` | Posts and pages |
| `themePostFields($layout)` | Posts only |
| `themePageFields($layout)` | Pages only |

```php title="usr/themes/starter/functions.php"
function themeFields($layout)
{
    $cover = new \Typecho\Widget\Helper\Form\Element\Text(
        'cover',
        null,
        null,
        _t('Cover image URL'),
        _t('Leave empty to show no cover')
    );
    $layout->addItem($cover);
}

function themePostFields($layout)
{
    $source = new \Typecho\Widget\Helper\Form\Element\Text(
        'source',
        null,
        null,
        _t('Source')
    );
    $layout->addItem($source);
}

function themePageFields($layout)
{
    $hideTitle = new \Typecho\Widget\Helper\Form\Element\Radio(
        'hideTitle',
        ['0' => _t('Show'), '1' => _t('Hide')],
        '0',
        _t('Page title')
    );
    $layout->addItem($hideTitle);
}
```

With this, the post editor has the two fields "Cover image URL" and "Source", and the page editor has the two fields "Cover image URL" and "Page title".

- Form elements are written the same way as in [Theme options](./theme-options.md); the difference is that here you use `$layout->addItem()`.
- Predefined fields are normally saved with the "String" type; multi-value elements such as `Checkbox` are saved as "JSON structure", so the template reads an array, and the options stay checked when the editor is opened again.
- A predefined field the author did not fill in is saved as an empty string.
- The names of predefined fields no longer appear in the free-form field rows lower in the panel; after switching to a theme that does not declare these fields, the saved values remain and are shown as ordinary field rows.

Reading them in a template:

```php
<?php if ($this->is('page') && '1' === $this->fields->hideTitle): ?>
    <!-- Hide the title -->
<?php else: ?>
    <h1><?php $this->title(); ?></h1>
<?php endif; ?>
```

## Field name rules

Field names may contain only letters, digits and underscores, and must not start with a digit. The names of predefined fields are up to the theme; it is a good idea to add a theme prefix (such as `starter_cover`) to avoid clashing with fields from plugins or fields authors add manually.
