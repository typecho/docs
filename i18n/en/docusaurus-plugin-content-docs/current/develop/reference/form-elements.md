---
title: Form Elements
sidebar_position: 7
translation: machine
source_commit: c4fe96854858d8912390c7dfe6c2eab21c87ac5c
---

# Form Elements

Theme settings (`themeConfig()`), predefined custom fields (`themeFields()`) and plugin settings (`config()` / `personalConfig()`) are all built from the form elements under `\Typecho\Widget\Helper\Form\Element\`.

## Constructor parameters

```text
new Element(?string $name, ?array $options, $value, ?string $label, ?string $description)
```

| Parameter | Description |
|---|---|
| `$name` | Field name. The name you use to read the setting (`$this->options->name`), and also the `name` in the form |
| `$options` | Options array `value => display text`, used only by `Radio`, `Checkbox` and `Select`; pass `null` for other elements |
| `$value` | Default value. For `Checkbox`, an array of the values checked by default |
| `$label` | Field title; may contain HTML |
| `$description` | Description below the field; may contain HTML |

## Element list

All are in the namespace `\Typecho\Widget\Helper\Form\Element`.

| Class | Control output | Value you read | Notes |
|---|---|---|---|
| `Text` | `<input type="text">` | string | |
| `Textarea` | `<textarea>` | string | **`$label` is required**; see below |
| `Password` | `<input type="password">` | string | The saved value is filled back into the page source, so it is not suitable for storing secrets |
| `Url` | `<input type="url">` | string | Internationalized domain names (`xn--`) are converted to their original form for display; it only changes the control type, validation still needs `addRule('url', …)` |
| `Number` | `<input type="number">` | numeric string | Extends `Text`; the browser validates it as an integer, so decimals cannot be entered |
| `Hidden` | `<input type="hidden">` | string | Hides the whole row |
| `Radio` | A group of radio buttons | Value of the selected item | |
| `Checkbox` | A group of checkboxes, with `name` set to `name[]` | Array of the values of the checked items | |
| `Select` | `<select>` drop-down | Value of the selected item | **`$label` is required**; see below |
| `Submit` | `<button type="submit">` | — | The form's submit button; `$value` is the button text. Theme and plugin settings pages add it automatically, so you do not need to add it yourself |
| `Fake` | Outputs no control | — | Its constructor takes only two parameters, `($name, $value)`; use it to "carry" a value in the form |

:::caution Textarea and Select must have a title
In 1.3.0, `Textarea` and `Select` access the title element during construction. If you pass `null` as `$label`, it throws `Typed property Typecho\Widget\Helper\Form\Element::$label must not be accessed before initialization` and the whole settings page fails to open. When you do not need a title, pass an empty string `''`. The other elements can be created without a title.
:::

## Element methods

| Method | Description |
|---|---|
| `addRule(...$rules): Element` | Add a validator |
| `container(Layout $item): Element` | Add an element to the container |
| `description(string $description): Element` | Set the description |
| `init()` | Custom initialization function |
| `input(?string $name = null, ?array $options = null): ?Layout` | Initialize the current input |
| `label(string $value): Element` | Create the form title |
| `message(string $message): Element` | Set the message |
| `multiline(): Layout` | Multi-line output mode |
| `multiMode(): Element` | Multi-line output mode |
| `setInputsAttribute(string $attributeName, $attributeValue)` | Set an attribute value on all inputs at once |
| `value($value): Element` | Set the value of the form element |

The ones you commonly use:

- `addRule($rule, $message, ...$params)`: adds a validation rule, see below; you can chain calls, as it returns the element itself;
- `multiMode()`: adds the `multiline` class to each option of a `Radio` or `Checkbox`, so each option is shown on its own line in the admin panel; it only affects layout;
- `value($value)`: sets the value;
- `$element->input->setAttribute('class', 'w-100')`: adds an attribute to the input control; `input` is the control itself (`Typecho\Widget\Helper\Layout`). `setInputsAttribute()` sets an attribute on a whole group of radio buttons / checkboxes at once.

## addRule(): validation rules

```php
$logoUrl = new \Typecho\Widget\Helper\Form\Element\Text('logoUrl', null, null, _t('LOGO 地址'));
$form->addInput($logoUrl->addRule('url', _t('请填写一个合法的URL地址')));

$count = new \Typecho\Widget\Helper\Form\Element\Text('count', null, '5', _t('显示数量'));
$form->addInput($count->addRule('required', _t('必须填写'))->addRule('isInteger', _t('请填入一个数字')));
```

`addRule($rule, $message, ...$params)`: the first argument is the rule, the second is the message shown when validation fails, and any further arguments are passed to the rule. On save, the rules are checked one by one in the order you added them; the message of the first failing rule is shown below the field, and the form is not saved.

Available rule names (methods of `Typecho\Validate`):

| Method | Description |
|---|---|
| `addRule(string $key, $rule, string $message): Validate` | Add a validation rule |
| `static alpha(string $str): bool` | Alpha |
| `static alphaDash(string $str): bool` | Alpha-numeric with underscores and dashes |
| `static alphaNumeric(string $str): bool` | Alpha-numeric |
| `confirm(?string $str, string $key): bool` | Check that the inputs match |
| `static email(string $str): bool` | Valid Email |
| `static enum(string $str, array $params): bool` | Enumeration check |
| `static isFloat($str): bool` | Numeric |
| `static isInteger($str): bool` | Is Numeric |
| `static maxLength(string $str, int $length): bool` | Max Length |
| `static minLength(string $str, int $length): bool` | Minimum length |
| `static regexp(string $str, string $pattern): bool` | Regular expression check |
| `required(): bool` | Whether it is empty |
| `run(array $data, ?array $rules = null): array` | Run the Validator |
| `setBreak()` | Set break mode |
| `static url(string $str): bool` | Check whether it is a URL |
| `static xssCheck(string $str): bool` | Check a string for XSS |

| Rule | Extra arguments | Example |
|---|---|---|
| `required` | — | `addRule('required', '必须填写')` |
| `email` | — | `addRule('email', '邮箱格式错误')` |
| `url` | — | `addRule('url', '网址格式错误')` |
| `isInteger` / `isFloat` | — | `addRule('isInteger', '请填入整数')` |
| `minLength` / `maxLength` | length | `addRule('maxLength', '最多 20 个字', 20)` |
| `alpha` / `alphaNumeric` / `alphaDash` | — | Allows only letters / letters and digits / letters, digits, underscores and dashes |
| `enum` | Array of allowed values | `addRule('enum', '取值不正确', ['a', 'b'])` |
| `regexp` | Regular expression | `addRule('regexp', '格式不正确', '/^#[0-9a-f]{6}$/i')` |
| `xssCheck` | — | Rejects characters such as `<` and `>` that could be used for XSS |
| `confirm` | Name of another field | Requires the value to equal that of another field |

- Except for `required` and `confirm`, other rules are **not checked when the field is empty**, so a field with only the `url` rule can be left blank.
- A rule can also be any callable. It receives the field value (and any extra arguments) and returns `true` to pass:

  ```php
  $color = new \Typecho\Widget\Helper\Form\Element\Text('color', null, '#336699', _t('主色'));
  $form->addInput($color->addRule(function ($value) {
      return (bool) preg_match('/^#[0-9a-f]{6}$/i', $value);
  }, _t('请填写 #RRGGBB 格式的颜色')));
  ```

- A string rule name is first tried as a PHP function name; only if no function with that name exists is the `Typecho\Validate` method used. So do not use a string with the same name as a built-in PHP function as a rule.
