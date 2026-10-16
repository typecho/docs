<?php
/**
 * List every plugin hook call site in the Typecho source as Markdown.
 *
 * Used to (re)generate docs/develop/reference/hooks.md.
 *
 * Usage:
 *   php scripts/hook-dump.php <typecho-root> [--summary]
 *
 * Example:
 *   php scripts/hook-dump.php ../typecho > /tmp/hooks.md
 *
 * It tokenizes index.php, admin/*.php and var/**.php and finds every
 * `X::pluginHandle()` and `Typecho\Plugin::factory('...')` call, then follows the
 * chain to `->call('hook', ...)` / `->filter('hook', ...)` (optionally through
 * `->trigger($flag)`). Any other method name in the chain is the deprecated
 * `Plugin::__call` form. A handle obtained without a literal hook name is a
 * dynamic hook family and is listed separately.
 *
 * The handle is the name Typecho\Plugin::__construct() derives: the legacy alias
 * from __TYPECHO_CLASS_ALIASES__ if the class has one, otherwise the class name
 * with `\` replaced by `_`. `self::` / `static::` resolve to the declaring class
 * (for a trait: to every class that uses the trait).
 *
 * --summary prints only the counts.
 */

if ($argc < 2) {
    fwrite(STDERR, "usage: php hook-dump.php <typecho-root> [--summary]\n");
    exit(1);
}

$root = rtrim(realpath($argv[1]), '/');
$summaryOnly = in_array('--summary', $argv, true);

/** class aliases, read from var/Widget/Init.php without running it */
function readAliases(string $root): array
{
    $src = file_get_contents($root . '/var/Widget/Init.php');
    preg_match("/define\('__TYPECHO_CLASS_ALIASES__',\s*\[(.*?)\]\);/s", $src, $m);
    preg_match_all("/'([A-Za-z_]+)'\s*=>\s*'\\\\?([^']+)'/", $m[1], $pairs, PREG_SET_ORDER);
    $aliases = [];
    foreach ($pairs as [, $alias, $class]) {
        $aliases[ltrim($class, "\\")] = $alias;
    }
    return $aliases;
}

$aliases = readAliases($root);

function handleName(string $class, array $aliases): string
{
    $class = ltrim($class, '\\');
    return $aliases[$class] ?? trim(str_replace('\\', '_', $class), '_');
}

function phpFiles(string $root): array
{
    $files = [$root . '/index.php'];
    foreach (glob($root . '/admin/*.php') as $file) {
        $files[] = $file;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/var'));
    foreach ($it as $file) {
        if ($file->isFile() && substr($file->getFilename(), -4) === '.php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
    return $files;
}

/** significant tokens only, as [id, text, line] */
function tokens(string $file): array
{
    $result = [];
    $line = 1;
    foreach (token_get_all(file_get_contents($file)) as $t) {
        if (is_array($t)) {
            $line = $t[2];
            if (in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $result[] = [$t[0], $t[1], $t[2]];
        } else {
            $result[] = [null, $t, $line];
        }
    }
    return $result;
}

function isName(array $t): bool
{
    return in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STATIC], true);
}

/** index of the matching closing bracket */
function closing(array $tokens, int $open): int
{
    $depth = 0;
    for ($i = $open, $n = count($tokens); $i < $n; $i++) {
        $text = $tokens[$i][1];
        if (in_array($text, ['(', '[', '{'], true) || $tokens[$i][0] === T_CURLY_OPEN
            || $tokens[$i][0] === T_DOLLAR_OPEN_CURLY_BRACES) {
            $depth++;
        } elseif (in_array($text, [')', ']', '}'], true)) {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }
    return $n - 1;
}

/** source text of the top-level comma separated arguments between $open and its closing bracket */
function arguments(array $tokens, int $open): array
{
    $close = closing($tokens, $open);
    $args = [];
    $current = '';
    $depth = 0;
    for ($i = $open + 1; $i < $close; $i++) {
        $text = $tokens[$i][1];
        if (in_array($text, ['(', '[', '{'], true)) {
            $depth++;
        } elseif (in_array($text, [')', ']', '}'], true)) {
            $depth--;
        }
        if ($text === ',' && $depth === 0) {
            $args[] = trim($current);
            $current = '';
            continue;
        }
        $sep = ($current !== '' && preg_match('/^\w/', $text) && preg_match('/\w$/', $current)) ? ' ' : '';
        $current .= $sep . $text;
    }
    if (trim($current) !== '') {
        $args[] = trim($current);
    }
    return [$args, $close];
}

/**
 * Per file: namespace, imports, and the class-like scope of every token.
 * Returns [scopes, uses, declared] where scopes[i] = [kind, fqcn, function].
 */
function scan(array $tokens): array
{
    $namespace = '';
    $imports = [];
    $scopes = [];
    $classUses = [];
    $declared = [];
    $stack = [];
    $pending = null;
    $pendingFunction = null;
    $depth = 0;

    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        [$id, $text] = $tokens[$i];

        if ($id === T_NAMESPACE && isset($tokens[$i + 1]) && isName($tokens[$i + 1])) {
            $namespace = ltrim($tokens[$i + 1][1], '\\');
        } elseif ($id === T_USE && empty($stack)) {
            // top-level import(s)
            $j = $i + 1;
            while ($tokens[$j][1] !== ';') {
                if (isName($tokens[$j])) {
                    $name = ltrim($tokens[$j][1], '\\');
                    $alias = substr($name, strrpos('\\' . $name, '\\'));
                    if (isset($tokens[$j + 1]) && $tokens[$j + 1][0] === T_AS) {
                        $alias = $tokens[$j + 2][1];
                        $j += 2;
                    }
                    $imports[$alias] = $name;
                }
                $j++;
            }
            $i = $j;
        } elseif ($id === T_USE && !empty($stack) && end($stack)[2] === $depth - 1 && $pendingFunction === null
            && end($stack)[3] === null) {
            // trait use inside a class body
            $j = $i + 1;
            while (!in_array($tokens[$j][1], [';', '{'], true)) {
                if (isName($tokens[$j])) {
                    $classUses[end($stack)[1]][] = $tokens[$j][1];
                }
                $j++;
            }
        } elseif (in_array($id, [T_CLASS, T_TRAIT, T_INTERFACE], true)
            && isset($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING
            && !(isset($tokens[$i - 1]) && $tokens[$i - 1][0] === T_DOUBLE_COLON)) {
            $fqcn = ($namespace ? $namespace . '\\' : '') . $tokens[$i + 1][1];
            $pending = [$id === T_TRAIT ? 'trait' : 'class', $fqcn];
            $declared[$fqcn] = $pending[0];
        } elseif ($id === T_FUNCTION && !empty($stack) && isset($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING) {
            $pendingFunction = $tokens[$i + 1][1];
        }

        if ($text === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
            if ($pending !== null) {
                $stack[] = [$pending[0], $pending[1], $depth, null];
                $pending = null;
            } elseif ($pendingFunction !== null && !empty($stack) && end($stack)[3] === null) {
                $top = array_pop($stack);
                $top[3] = $pendingFunction;
                $top[4] = $depth;
                $stack[] = $top;
                $pendingFunction = null;
            }
            $depth++;
        } elseif ($text === '}') {
            $depth--;
            if (!empty($stack)) {
                $top = end($stack);
                if (isset($top[4]) && $top[4] === $depth) {
                    array_pop($stack);
                    $top[3] = null;
                    unset($top[4]);
                    $stack[] = $top;
                } elseif ($top[2] === $depth) {
                    array_pop($stack);
                }
            }
        } elseif ($text === ';' && $pendingFunction !== null) {
            $pendingFunction = null; // abstract / interface method
        }

        $scopes[$i] = empty($stack) ? null : [end($stack)[0], end($stack)[1], end($stack)[3]];
    }

    $resolve = function (string $name) use ($namespace, $imports): string {
        if ($name[0] === '\\') {
            return ltrim($name, '\\');
        }
        $first = explode('\\', $name)[0];
        if (isset($imports[$first])) {
            return $imports[$first] . substr($name, strlen($first));
        }
        return ($namespace ? $namespace . '\\' : '') . $name;
    };

    $uses = [];
    foreach ($classUses as $class => $traits) {
        foreach ($traits as $trait) {
            $uses[$resolve($trait)][] = $class;
        }
    }

    return [$scopes, $uses, $declared, $resolve];
}

$files = phpFiles($root);
$parsed = [];
$traitUsers = [];
foreach ($files as $file) {
    $tokens = tokens($file);
    [$scopes, $uses, $declared, $resolve] = scan($tokens);
    $parsed[$file] = [$tokens, $scopes, $resolve];
    foreach ($uses as $trait => $classes) {
        foreach ($classes as $class) {
            $traitUsers[$trait][] = $class;
        }
    }
}

$sites = [];     // each literal call site
$handleClass = []; // handle => 1.3 class name
$dynamic = [];   // handles without a literal hook name

foreach ($parsed as $file => [$tokens, $scopes, $resolve]) {
    $rel = substr($file, strlen($root) + 1);
    $n = count($tokens);

    for ($i = 2; $i < $n; $i++) {
        $t = $tokens[$i];
        if ($t[0] !== T_STRING || !in_array($t[1], ['pluginHandle', 'factory'], true)) {
            continue;
        }
        if ($tokens[$i - 1][0] !== T_DOUBLE_COLON || !isName($tokens[$i - 2]) || $tokens[$i + 1][1] !== '(') {
            continue;
        }
        $owner = $tokens[$i - 2][1];
        $scope = $scopes[$i];
        $line = $t[2];

        [$args, $close] = arguments($tokens, $i + 1);

        if ($t[1] === 'factory') {
            if ($resolve($owner) !== 'Typecho\Plugin' || count($args) !== 1 || ($scope[2] ?? null) === 'pluginHandle') {
                continue;
            }
            if ($args[0][0] === "'") {
                $handles = [handleName(trim($args[0], "'"), $aliases)];
            } elseif (preg_match('/^([\\\\\w]+)::class$/', $args[0], $m)) {
                $handles = [handleName($resolve($m[1]), $aliases)];
                $handleClass[$handles[0]] = $resolve($m[1]);
            } else {
                continue;
            }
        } else {
            if (in_array(strtolower($owner), ['self', 'static'], true)) {
                $classes = ($scope && $scope[0] === 'trait') ? ($traitUsers[$scope[1]] ?? []) : [$scope[1]];
            } else {
                $classes = [$resolve($owner)];
            }
            sort($classes);
            $handles = array_map(fn($c) => handleName($c, $aliases), $classes);
            foreach ($classes as $c) {
                $handleClass[handleName($c, $aliases)] = $c;
            }
        }

        // follow the chain
        $j = $close + 1;
        $trigger = false;
        $method = null;
        $hook = null;
        $hookArgs = [];
        while ($j < $n && $tokens[$j][0] === T_OBJECT_OPERATOR) {
            $name = $tokens[$j + 1][1];
            [$callArgs, $end] = arguments($tokens, $j + 2);
            if ($name === 'trigger') {
                $trigger = true;
                $j = $end + 1;
                continue;
            }
            $method = $name;
            if (in_array($name, ['call', 'filter'], true)) {
                if (isset($callArgs[0]) && $callArgs[0][0] === "'") {
                    $hook = trim($callArgs[0], "'");
                    $hookArgs = array_slice($callArgs, 1);
                }
            } else {
                $hook = $name;
                $hookArgs = $callArgs;
            }
            break;
        }

        $where = $rel . ':' . $line;
        $function = $scope[2] ?? null;

        if ($hook === null) {
            $dynamic[] = [
                'handles'  => $handles,
                'where'    => $where,
                'function' => ($scope ? $scope[1] : '') . ($function ? '::' . $function . '()' : ''),
            ];
            continue;
        }

        if (in_array($method, ['call', 'filter'], true)) {
            $kind = $trigger ? 'trigger + ' . $method : $method;
        } else {
            $kind = 'deprecated magic';
        }

        $sites[] = [
            'handles'  => $handles,
            'hook'     => $hook,
            'kind'     => $kind,
            'args'     => $hookArgs,
            'where'    => $where,
        ];
    }
}

/** group into (handle, hook) pairs */
$pairs = [];
foreach ($sites as $site) {
    foreach ($site['handles'] as $handle) {
        $key = $handle . ':' . $site['hook'];
        if (!isset($pairs[$key])) {
            $pairs[$key] = [
                'handle' => $handle,
                'hook'   => $site['hook'],
                'kinds'  => [],
                'args'   => [],
                'where'  => [],
            ];
        }
        $pairs[$key]['kinds'][$site['kind']] = true;
        $pairs[$key]['args'][implode(', ', $site['args'])] = true;
        $pairs[$key]['where'][] = $site['where'];
    }
}

$byHandle = [];
foreach ($pairs as $pair) {
    $byHandle[$pair['handle']][] = $pair;
}

// file handles (admin/*.php, index.php) first, then widget classes, each sorted by name
uksort($byHandle, function ($a, $b) {
    $fa = strpos($a, '.php') !== false;
    $fb = strpos($b, '.php') !== false;
    return $fa === $fb ? strcmp($a, $b) : ($fa ? -1 : 1);
});

$fileHandles = count(array_filter(array_keys($pairs), fn($k) => strpos($k, '.php:') !== false));

if ($summaryOnly) {
    printf("call sites: %d (+ %d dynamic)\n", count($sites), count($dynamic));
    printf("pairs: %d (file handles %d, widget hooks %d)\n", count($pairs), $fileHandles, count($pairs) - $fileHandles);
    printf("handles: %d\n", count($byHandle));
    foreach ($dynamic as $d) {
        printf("dynamic: %s %s %s\n", implode(',', $d['handles']), $d['where'], $d['function']);
    }
    exit(0);
}

function cell(string $s): string
{
    return str_replace('|', '\|', $s);
}

echo "| 句柄 | 所在文件或类 | 钩子数 |\n|---|---|---|\n";
foreach ($byHandle as $handle => $list) {
    $where = isset($handleClass[$handle]) ? '`' . $handleClass[$handle] . '`' : '`' . $handle . '`';
    echo '| [`' . $handle . '`](#' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $handle)) . ') | ' . $where . ' | ' . count($list) . " |\n";
}
echo "\n";

foreach ($byHandle as $handle => $list) {
    usort($list, fn($a, $b) => strcmp($a['hook'], $b['hook']));
    echo "### `{$handle}` {#" . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $handle)) . "}\n\n";
    if (isset($handleClass[$handle])) {
        echo "类：`{$handleClass[$handle]}`\n\n";
    }
    echo "| 钩子 | 类型 | 参数 | 调用位置 |\n|---|---|---|---|\n";
    foreach ($list as $pair) {
        $args = array_keys($pair['args']);
        echo '| `' . $pair['hook'] . '` | '
            . implode(' / ', array_keys($pair['kinds'])) . ' | '
            . implode('<br/>', array_map(fn($a) => $a === '' ? '（无）' : '`' . cell($a) . '`', $args)) . ' | '
            . implode('<br/>', array_map(fn($w) => '`' . $w . '`', $pair['where'])) . " |\n";
    }
    echo "\n";
}

echo "### 动态钩子\n\n";
echo "以下钩子名在运行时拼出，句柄是对象实际所属的类（异步任务为 Widget_Service）。\n\n";
echo "| 调用位置 | 所在方法 |\n|---|---|\n";
foreach ($dynamic as $d) {
    echo '| `' . $d['where'] . '` | `' . $d['function'] . "` |\n";
}
