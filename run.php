<?php
define('DEBUG_MODE', 2);

include 'System/functions.php';
require 'System/Utils/AutoLoader.php';

$autoloader = new \XPBot\System\Utils\AutoLoader('XPBot\\', './');
$autoloader->register();

$client = new \XPBot\Bot\Bot();
$client->connect();