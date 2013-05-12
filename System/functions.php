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