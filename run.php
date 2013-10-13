<?php
if ($index = array_search('-d', $argv))
    define('DEBUG_MODE', isset($argv[$index + 1]) ? (int)$argv[$index + 1] : 1);
else
    define('DEBUG_MODE', 0);

include 'System/functions.php';
require 'System/Utils/AutoLoader.php';

$autoloader = new \XPBot\System\Utils\AutoLoader('XPBot\\', './');
$autoloader->register();

$client = new \XPBot\Bot\Bot();
$client->connect();