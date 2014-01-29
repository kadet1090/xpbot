<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 29.01.14
 * Time: 21:50
 */

/**
 * Alias for Language::get().
 *
 * @see Kadet\Xmpp\Utils\Language::get
 *
 * @param string $phrase    Phrase id,
 * @param string $lang      Target language,
 * @param string $namespace Phrase namespace (default: default),
 * @param array  $arguments Variables given to phrase (ie nick, version).
 *
 * @return mixed|string     Phrase in specified language.
 */
function __($phrase, $lang, $namespace = 'default', $arguments = array())
{
    return \XPBot\Utils\Language::get($phrase, $lang, $namespace, $arguments);
}

/**
 * Performs deep search in array.
 *
 * @param array $array  Array to search in.
 * @param mixed $search Value of our interest.
 *
 * @return array|bool|mixed Array of found variables or variable.
 */
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
        return false;
    else
        return $results;
}

/**
 * Check from where function/method was executed.
 *
 * @return string|null Class name.
 */
function getCaller()
{
    $backtrace = debug_backtrace();

    return isset($backtrace[2]['class']) ?
        $backtrace[2]['class'] :
        null;
}

/**
 * Trim multi line text.
 * Runs trim on every line of text.
 *
 * @param string $string String to be trimmed.
 * @return string Trimmed string.
 */
function multiLineTrim($string)
{
    return implode("\n", array_map('trim', explode("\n", $string)));
}

/**
 * Gets integer in proper base.
 *
 * @param string|int $number Number to parse.
 *
 * @return int       Number converted to int.
 */
function parseNumber($number) {
    if (is_numeric($number)) return $number;

    switch(substr($number, 0, 2)) {
        case '0x': return intval(substr($number, 2), 16);
        case '0b': return intval(substr($number, 2), 10);
        case '0o': return intval(substr($number, 2), 8);
        default:   return (int)$number;
    }
}