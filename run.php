<?php
define('DEBUG_MODE', 1);

include 'system/functions.php';

$client = new \XPBot\Bot\Bot();
$client->connect();