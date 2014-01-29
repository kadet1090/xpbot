<?php
if ($index = array_search('-d', $argv))
    define('DEBUG_MODE', isset($argv[$index + 1]) ? (int)$argv[$index + 1] : 1);
else
    define('DEBUG_MODE', 0);

include 'XPBot/functions.php';
include 'Kadet/Utils/functions.php';
require 'Kadet/Utils/AutoLoader.php';

$autoloader['Plugins'] = new \Kadet\Utils\AutoLoader('XPBot\\Plugins\\', './Plugins/');
$autoloader['Plugins']->register();

$autoloader['XPBot'] = new \Kadet\Utils\AutoLoader('XPBot\\', './XPBot/');
$autoloader['XPBot']->register();

$autoloader['Kadet'] = new \Kadet\Utils\AutoLoader('Kadet\\', './Kadet/');
$autoloader['Kadet']->register();

$client = new \XPBot\Bot();
$client->connect();