<?php

use XPBot\System\Xmpp\Jid;

include 'system/functions.php';

$client = new \XPBot\Bot\Bot();

$client->onReady->add(new \XPBot\System\Utils\Delegate(function () use ($client) {
    $room = $client->join(new Jid('testbot@conference.aqq.eu'), 'XPBot[test]');
}));


$client->connect(false);