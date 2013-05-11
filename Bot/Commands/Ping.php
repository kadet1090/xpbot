<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Bot\Commands;

use XPBot\Bot\Command;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Ping extends Command {
    public function execute($args, $groupchat) {
        $jid = new Jid($args[1]);
        $time = microtime(true);

        $this->_bot->ping($jid, new Delegate(function () use ($time) {
            $time = microtime(true) - $time;

            $this->_author->room->message('Ping: '.round($time * 1000).'ms');
        }));
    }
}