<?php
/**
 * Dump the public API of Typecho classes as Markdown tables.
 *
 * Used to (re)generate the pages under docs/develop/reference/.
 *
 * Usage:
 *   php scripts/api-dump.php <typecho-root> <class> [<class> ...] [--own] [--magic-only] [--methods-only]
 *
 * Example:
 *   php scripts/api-dump.php ../typecho 'Widget\Archive' --own
 *
 * For each class it prints:
 *   - public methods (name, signature, declaring class, first line of the doc comment, deprecated flag)
 *   - magic properties: protected/public methods named ___xxx, read as $widget->xxx
 * --own limits the output to members declared in the class itself.
 */

if ($argc < 3) {
    fwrite(STDERR, "usage: php api-dump.php <typecho-root> <class> [...] [--own] [--magic-only] [--methods-only]\n");
    exit(1);
}

$root = realpath($argv[1]);
$args = array_slice($argv, 2);
$own = in_array('--own', $args, true);
$magicOnly = in_array('--magic-only', $args, true);
$methodsOnly = in_array('--methods-only', $args, true);
$classes = array_values(array_filter($args, fn($a) => strpos($a, '--') !== 0));

define('__TYPECHO_ROOT_DIR__', $root);
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
define('__TYPECHO_THEME_DIR__', '/usr/themes');
define('__TYPECHO_ADMIN_DIR__', '/admin/');
require_once $root . '/var/Typecho/Common.php';

function typeName(?ReflectionType $type): string
{
    if (null === $type) {
        return '';
    }
    if ($type instanceof ReflectionNamedType) {
        $name = $type->getName();
        if (!$type->isBuiltin()) {
            $parts = explode('\\', $name);
            $name = end($parts);
        }
        return ($type->allowsNull() && $name !== 'mixed' && $name !== 'null' ? '?' : '') . $name;
    }
    return implode('|', array_map('typeName', $type->getTypes()));
}

function signature(ReflectionMethod $m): string
{
    $params = [];
    foreach ($m->getParameters() as $p) {
        $s = typeName($p->getType());
        $s = ($s ? $s . ' ' : '') . ($p->isPassedByReference() ? '&' : '') . ($p->isVariadic() ? '...' : '') . '$' . $p->getName();
        if ($p->isDefaultValueAvailable()) {
            $d = $p->getDefaultValue();
            $s .= ' = ' . (null === $d ? 'null' : (is_bool($d) ? ($d ? 'true' : 'false') : (is_array($d) ? '[]' : var_export($d, true))));
        }
        $params[] = $s;
    }
    $ret = typeName($m->getReturnType());
    return $m->getName() . '(' . implode(', ', $params) . ')' . ($ret ? ': ' . $ret : '');
}

function summary(ReflectionMethod $m): array
{
    $doc = $m->getDocComment() ?: '';
    $deprecated = (bool) preg_match('/@deprecated/', $doc);
    $text = '';
    foreach (preg_split('/\r\n|\n|\r/', $doc) as $line) {
        $line = trim(preg_replace('#^\s*/?\*+/?#', '', $line));
        if ($line === '' || $line[0] === '@') {
            if ($text !== '') {
                break;
            }
            continue;
        }
        $text = $line;
        break;
    }
    return [str_replace('|', '\|', $text), $deprecated];
}

function shortClass(string $c): string
{
    return '`' . $c . '`';
}

foreach ($classes as $class) {
    $rc = new ReflectionClass($class);
    echo "## {$class}\n\n";
    echo '父类：' . ($rc->getParentClass() ? shortClass($rc->getParentClass()->getName()) : '无') . "\n\n";

    $methods = [];
    $magic = [];
    foreach ($rc->getMethods() as $m) {
        $decl = $m->getDeclaringClass()->getName();
        if ($own && $decl !== $rc->getName()) {
            continue;
        }
        $name = $m->getName();
        if (preg_match('/^___([a-zA-Z].*)$/', $name, $mm)) {
            if (!$m->isPrivate()) {
                $magic[$mm[1]] = $m;
            }
            continue;
        }
        if (!$m->isPublic() || strpos($name, '__') === 0 || $m->isStatic() && $name === 'pluginHandle') {
            continue;
        }
        $methods[$name] = $m;
    }
    ksort($methods, SORT_STRING | SORT_FLAG_CASE);
    ksort($magic, SORT_STRING | SORT_FLAG_CASE);

    if (!$magicOnly) {
        echo "### 方法\n\n| 方法 | 声明于 | 说明 |\n|---|---|---|\n";
        foreach ($methods as $m) {
            [$text, $dep] = summary($m);
            echo '| `' . ($m->isStatic() ? 'static ' : '') . signature($m) . '` | ' . shortClass($m->getDeclaringClass()->getName())
                . ' | ' . ($dep ? '**已弃用**。' : '') . $text . " |\n";
        }
        echo "\n";
    }
    if (!$methodsOnly) {
        echo "### 魔术属性\n\n| 属性 | 类型 | 声明于 | 说明 |\n|---|---|---|---|\n";
        foreach ($magic as $prop => $m) {
            [$text] = summary($m);
            echo '| `' . $prop . '` | `' . (typeName($m->getReturnType()) ?: 'mixed') . '` | ' . shortClass($m->getDeclaringClass()->getName())
                . ' | ' . $text . " |\n";
        }
        echo "\n";
    }
}
