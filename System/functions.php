<?php
function __autoload($class)
{
    require_once str_replace('\\', DIRECTORY_SEPARATOR, substr(strstr($class, '\\'), 1)) . '.php';
}

function arrayDeepSearch(array $array, $search)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveArrayIterator($array), RecursiveIteratorIterator::SELF_FIRST
    );

    $results = array();
    $parent  = '';
    foreach ($iterator as $key => $value) {
        if ($iterator->callHasChildren()) {
            $parent = $key;
            continue;
        }

        if ($key == $search)
            $results[$parent] = $value;
    }

    if (count($results) == 1)
        return reset($results);
    elseif (count($results) == 0)
        return false; else
        return $results;
}

function getCaller()
{
    $backtrace = debug_backtrace();

    return isset($backtrace[2]['class']) ?
        $backtrace[2]['class'] :
        null;
}

function __($phrase, $lang, $namespace = 'default', $arguments = array())
{
    return XPBot\System\Utils\Language::get($phrase, $lang, $namespace, $arguments);
}

function multilineTrim($string)
{
    return implode("\n", array_map('trim', explode("\n", $string)));
}

function printColouredText($color, $text)
{
    $colors = array(
        'normal' => chr(27) . "[0;39m",
        'red'    => chr(27) . "[1;31m",
        'green'  => chr(27) . "[1;32m",
        'yellow' => chr(27) . "[1;33m",
        'blue'   => chr(27) . "[1;34m",
        'cyan'   => chr(27) . "[1;36m",
        'white'  => chr(27) . "[1;37m"
    );

    if (isset($colors[$color])) echo $colors[$color] . $text . $colors['normal'] . PHP_EOL;
    else echo $text . PHP_EOL;
}

function parseNumber($number) {
    if (is_numeric($number)) return $number;

    switch(substr($number, 0, 2)) {
        case '0x': return intval(substr($number, 2), 16);
        case '0b': return intval(substr($number, 2), 10);
        case '0o': return intval(substr($number, 2), 8);
        default:   return (int)$number;
    }
}