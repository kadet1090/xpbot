<?php

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
 * Alias for Language::get().
 *
 * @see XPBot\System\Utils\Language::get
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
    return XPBot\System\Utils\Language::get($phrase, $lang, $namespace, $arguments);
}

/**
 * Trim multi line text.
 * Runs trim on every line of text.
 *
 * @param string $string String to be trimmed.
 * @return string Trimmed string.
 */
function multilineTrim($string)
{
    return implode("\n", array_map('trim', explode("\n", $string)));
}

/**
 * Print coloured text to console output.
 *
 * @param string $color Target text color, one of: red, green, yellow, blue, cyan, normal (default for user console).
 * @param string $text
 */
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

/**
 * Test if given string is serialized or not.
 *
 * @param string $data Data to check.
 *
 * @return bool 
 */
function is_serialized($data) {
    // if it isn't a string, it isn't serialized
    if ( !is_string( $data ) )
        return false;
    $data = trim( $data );
    if ( 'N;' == $data )
        return true;
    if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
        return false;
    switch ( $badions[1] ) {
        case 'a' :
        case 'O' :
        case 's' :
            if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
                return true;
            break;
        case 'b' :
        case 'i' :
        case 'd' :
            if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
                return true;
            break;
    }
    return false;
}