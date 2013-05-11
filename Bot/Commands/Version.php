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

class Version extends Command {
    public function execute($args, $groupchat) {
        $jid = new Jid($args[1]);

        $this->_bot->version($jid, new Delegate(function ($reply) {
            $str = "Komunikator {$reply->query->name} {$reply->query->version}";
            if(isset($reply->query->os)) $str .= "\n{$reply->query->os}";

            $this->_author->room->message($str);
        }));
    }
}