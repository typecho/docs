---
title: Custom fields (editor side)
sidebar_position: 6
translation: machine
source_commit: c9097eed4a7142f9eabc1bca041ef819d6a64bbd
---

# Custom fields (editor side)

Custom fields let you attach extra data to a post or a page, such as a subtitle, a cover image URL or a view count; the theme decides how to display them. The hint below the **Custom fields** panel in the editor, "Custom fields can extend your template features, see the help documentation for usage", points to this page.

This page only covers how to fill them in on the admin panel. For reading and defining fields in a theme template (`$this->fields->fieldName`), see [Custom fields in templates](../../develop/theme/custom-fields.md).

## Where the panel is

In the post and page editors, there is an expandable **Custom fields** area below the content. It is expanded by default when the post or page already has fields, or when the theme predefines fields.

Each row is one field:

| UI element | Form field | Description |
|---|---|---|
| Field name | `fieldNames[]` | Letters, digits and underscores only, must not start with a digit (`^[_a-zA-Z][_a-zA-Z0-9]*$`), case-sensitive |
| Field type | `fieldTypes[]` | A drop-down with four types, see below |
| Field value | `fieldValues[]` | A multi-line text box |
| Delete | | Removes the row after you confirm "Are you sure you want to delete this field?" |

Click **+ Add field** to add a row. Fields are saved together with **Save draft** or **Publish**:

- Rows with an empty field name are ignored; for a name that breaks the rules, the browser warns you immediately, and even if it is submitted, the server discards the row.
- Delete a row and save, and the field is removed from the post.
- When you **Save draft** on a published post, the fields are also saved in the [revision](./posts.md#drafts), and take effect only after you publish.

## The four field types {#types}

| Name in the drop-down | Type value | Use | How it is saved | Value read in the template |
|---|---|---|---|---|
| String | `str` | Text, URLs, HTML snippets and so on; the most common | Saved as is in the `str_value` column | string |
| Integer | `int` | Counts, sort weights, switches (0 / 1) | Converted with PHP `intval()` and saved in the `int_value` column | integer |
| Float | `float` | Ratings, prices, coordinates | Converted with `floatval()` and saved in the `float_value` column | float |
| JSON structure | `json` | Lists, key-value pairs and other structured data | Saved in the `str_value` column after `json_encode()` | the value after `json_decode()` |

Examples of the value you enter and the value read in the template:

| Field | Type | Entered | Read in the template |
|---|---|---|---|
| `subtitle` | String | `A subtitle` | `'A subtitle'` |
| `views` | Integer | `42abc` | `42` (the non-numeric part is dropped) |
| `rating` | Float | `4.5` | `4.5` |
| `meta` | JSON structure | `{"color":"red","size":[1,2]}` | the string `'{"color":"red","size":[1,2]}'`, **not an array** (see the note below) |

The integer and float types do not validate input: if you enter something that is not a number, it is saved as `0` or as its leading numeric part, without an error.

:::caution In 1.3.0, "JSON structure" in the panel is encoded twice
When you choose "JSON structure" in the panel, the text you enter is treated as a **string** and passed through `json_encode()` once more, so the template reads back the original text rather than a parsed array. Worse, when you open the editor again, the field value is shown escaped as `"{\"color\":\"red\",...}"`, and every save (including saving a revision) adds another layer of escaping.

Until this is fixed, we recommend: when you need structured data, choose the "String" type in the panel, enter the JSON text, and let the theme call `json_decode()` itself; or use fields predefined by the theme (see the next section), where the theme's checkboxes and other multi-value controls produce the JSON data, and saving and reading both work correctly.
:::

## Fields predefined by the theme {#theme-fields}

A theme can predefine fields in `functions.php`. They appear at the top of the panel with a description, and can take the form of text boxes, drop-downs, checkboxes and so on; you do not need to type a field name and type by hand.

- The theme defines these fields with `themeFields()` (shown for both posts and pages), `themePostFields()` (shown only for posts) or `themePageFields()` (shown only for pages).
- Predefined fields are usually saved as the "String" type; the value of a multi-select control (for example a checkbox group) is saved as "JSON structure", and the template reads an array. For example, a checkbox group with the two options "Sticky" and "Hot", with both checked, is saved as `["top","hot"]`, the template reads `['top', 'hot']`, and both options stay checked the next time you edit.
- The names of predefined fields no longer appear in the free-form field rows below.
- After you switch to another theme, the fields predefined by the old theme no longer appear as controls; the saved values are still in the database and are shown as ordinary field rows, which you can keep editing or delete (a field that was "JSON structure" runs into the double-encoding problem above when you save it again in the panel).

Plugins can also predefine fields, or make certain fields read-only. Read-only fields are not shown in the panel and are not modified by the editor.
