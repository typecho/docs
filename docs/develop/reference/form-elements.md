---
title: 表单元素
sidebar_position: 7
---

# 表单元素

主题设置（`themeConfig()`）、预置自定义字段（`themeFields()`）和插件设置（`config()` / `personalConfig()`）都用 `\Typecho\Widget\Helper\Form\Element\` 下的表单元素构建。

:::note 本页的生成方式
元素列表取自 Typecho 1.3.0 的 `var/Typecho/Widget/Helper/Form/Element/` 目录，方法表由 `php scripts/api-dump.php <Typecho 源码目录> 'Typecho\Widget\Helper\Form\Element' --own` 和 `'Typecho\Validate'` 生成；各元素输出的 HTML 是逐个构造并调用 `render()` 得到的。
:::

## 构造参数

```text
new Element(?string $name, ?array $options, $value, ?string $label, ?string $description)
```

| 参数 | 说明 |
|---|---|
| `$name` | 字段名。读取设置时用的名字（`$this->options->名字`），也是表单中的 `name` |
| `$options` | 选项数组 `值 => 显示文字`，只有 `Radio`、`Checkbox`、`Select` 使用；其他元素传 `null` |
| `$value` | 默认值。`Checkbox` 为默认勾选的值组成的数组 |
| `$label` | 字段标题，可以包含 HTML |
| `$description` | 字段下方的说明，可以包含 HTML |

## 元素一览

命名空间均为 `\Typecho\Widget\Helper\Form\Element`。

| 类 | 输出的控件 | 读到的值 | 说明 |
|---|---|---|---|
| `Text` | `<input type="text">` | 字符串 | |
| `Textarea` | `<textarea>` | 字符串 | **必须提供 `$label`**，见下文 |
| `Password` | `<input type="password">` | 字符串 | 已保存的值会回填到页面源码中，不适合保存需要保密的内容 |
| `Url` | `<input type="url">` | 字符串 | 国际化域名（`xn--`）显示时转换为原文；只改变控件类型，校验仍需 `addRule('url', …)` |
| `Number` | `<input type="number">` | 数字字符串 | 继承自 `Text`；浏览器按整数校验，不能输入小数 |
| `Hidden` | `<input type="hidden">` | 字符串 | 整行隐藏 |
| `Radio` | 一组单选按钮 | 选中项的值 | |
| `Checkbox` | 一组复选框，`name` 为 `名字[]` | 选中项的值组成的数组 | |
| `Select` | `<select>` 下拉框 | 选中项的值 | **必须提供 `$label`**，见下文 |
| `Submit` | `<button type="submit">` | — | 表单的提交按钮，`$value` 为按钮文字；主题和插件设置页会自动添加，不需要自己加 |
| `Fake` | 不输出任何控件 | — | 构造函数只有 `($name, $value)` 两个参数，用来在表单中“带上”一个值 |

:::caution Textarea 与 Select 必须有标题
1.3.0 中，`Textarea` 和 `Select` 在构造时会访问标题元素；`$label` 传 `null` 时会抛出 `Typed property Typecho\Widget\Helper\Form\Element::$label must not be accessed before initialization`，整个设置页无法打开。不需要标题时传空字符串 `''`。其余元素可以不传标题。
:::

## 元素的方法

| 方法 | 说明 |
|---|---|
| `addRule(...$rules): Element` | 增加验证器 |
| `container(Layout $item): Element` | 在容器里增加元素 |
| `description(string $description): Element` | 设置描述信息 |
| `init()` | 自定义初始函数 |
| `input(?string $name = null, ?array $options = null): ?Layout` | 初始化当前输入项 |
| `label(string $value): Element` | 创建表单标题 |
| `message(string $message): Element` | 设置提示信息 |
| `multiline(): Layout` | 多行输出模式 |
| `multiMode(): Element` | 多行输出模式 |
| `setInputsAttribute(string $attributeName, $attributeValue)` | 统一设置所有输入项的属性值 |
| `value($value): Element` | 设置表单元素值 |

常用的几个：

- `addRule($rule, $message, ...$params)`：添加校验规则，见下文；可以连续调用，返回元素本身；
- `multiMode()`：给 `Radio`、`Checkbox` 的每个选项加上 `multiline` class，后台中每个选项各占一行显示，只影响排版；
- `value($value)`：设置值；
- `$element->input->setAttribute('class', 'w-100')`：给输入控件加属性，`input` 是控件本身（`Typecho\Widget\Helper\Layout`）；`setInputsAttribute()` 给一组单选 / 复选框统一设置属性。

## addRule()：校验规则

```php
$logoUrl = new \Typecho\Widget\Helper\Form\Element\Text('logoUrl', null, null, _t('LOGO 地址'));
$form->addInput($logoUrl->addRule('url', _t('请填写一个合法的URL地址')));

$count = new \Typecho\Widget\Helper\Form\Element\Text('count', null, '5', _t('显示数量'));
$form->addInput($count->addRule('required', _t('必须填写'))->addRule('isInteger', _t('请填入一个数字')));
```

`addRule($rule, $message, ...$params)`：第一个参数是规则，第二个是校验失败时的提示，之后的参数会传给规则。保存时按添加顺序逐条检查，第一条失败的规则的提示显示在字段下方，表单不会保存。

可用的规则名（`Typecho\Validate` 的方法）：

| 方法 | 说明 |
|---|---|
| `addRule(string $key, $rule, string $message): Validate` | 增加验证规则 |
| `static alpha(string $str): bool` | Alpha |
| `static alphaDash(string $str): bool` | Alpha-numeric with underscores and dashes |
| `static alphaNumeric(string $str): bool` | Alpha-numeric |
| `confirm(?string $str, string $key): bool` | 验证输入是否一致 |
| `static email(string $str): bool` | Valid Email |
| `static enum(string $str, array $params): bool` | 枚举类型判断 |
| `static isFloat($str): bool` | Numeric |
| `static isInteger($str): bool` | Is Numeric |
| `static maxLength(string $str, int $length): bool` | Max Length |
| `static minLength(string $str, int $length): bool` | 最小长度 |
| `static regexp(string $str, string $pattern): bool` | 正则表达式验证 |
| `required(): bool` | 是否为空 |
| `run(array $data, ?array $rules = null): array` | Run the Validator |
| `setBreak()` | 设置为中断模式 |
| `static url(string $str): bool` | 验证是否为网址 |
| `static xssCheck(string $str): bool` | 对xss字符串的检测 |

| 规则 | 额外参数 | 写法示例 |
|---|---|---|
| `required` | — | `addRule('required', '必须填写')` |
| `email` | — | `addRule('email', '邮箱格式错误')` |
| `url` | — | `addRule('url', '网址格式错误')` |
| `isInteger` / `isFloat` | — | `addRule('isInteger', '请填入整数')` |
| `minLength` / `maxLength` | 长度 | `addRule('maxLength', '最多 20 个字', 20)` |
| `alpha` / `alphaNumeric` / `alphaDash` | — | 只允许字母 / 字母和数字 / 字母、数字、下划线和横杠 |
| `enum` | 允许的值组成的数组 | `addRule('enum', '取值不正确', ['a', 'b'])` |
| `regexp` | 正则表达式 | `addRule('regexp', '格式不正确', '/^#[0-9a-f]{6}$/i')` |
| `xssCheck` | — | 拒绝含有 `<`、`>` 等可能用于 XSS 的字符 |
| `confirm` | 另一个字段名 | 要求与另一个字段的值相同 |

- 除 `required` 和 `confirm` 外，字段**为空时不检查**其他规则，所以只有 `url` 规则的字段可以留空。
- 规则也可以是任意可调用对象，它接收字段值（以及额外参数），返回 `true` 表示通过：

  ```php
  $color = new \Typecho\Widget\Helper\Form\Element\Text('color', null, '#336699', _t('主色'));
  $form->addInput($color->addRule(function ($value) {
      return (bool) preg_match('/^#[0-9a-f]{6}$/i', $value);
  }, _t('请填写 #RRGGBB 格式的颜色')));
  ```

- 字符串规则名会先被当作 PHP 函数名尝试调用，找不到同名函数才使用 `Typecho\Validate` 的方法；因此不要用与 PHP 内置函数同名的字符串作为规则。
