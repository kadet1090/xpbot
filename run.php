<?php
if ($index = array_search('-d', $argv))
    define('DEBUG_MODE', isset($argv[$index + 1]) ? (int)$argv[$index + 1] : 1);
else
    define('DEBUG_MODE', 0);

include 'XPBot/functions.php';
include 'vendor/autoload.php';

$plugins = new \Kadet\Utils\AutoLoader('XPBot\\Plugins\\', './Plugins/');
$plugins->register();

$autoloader = new \Kadet\Utils\AutoLoader('XPBot\\', './XPBot/');
$autoloader->register();

$client = new \XPBot\Bot();
$client->connect();