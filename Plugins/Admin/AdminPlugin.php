<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin;


use XPBot\Bot\Plugin;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\User;

class AdminPlugin extends Plugin {

    public function getInfo()
    {
        // TODO: Implement getInfo() method.
    }

    public function load()
    {
        $this->_bot->findCommands('Plugins/Admin/Commands/', 'admin', 'XPBot\\Plugins\\Admin\\Commands');
        $this->_bot->onJoin->add(array($this, '_auto'));
    }

    public function unload()
    {
        // TODO: Implement unload() method.
    }

    public function _auto(Room $room, User $user, $broadcast) {
        $users = $room->configuration->xpath("//auto/user[@jid='{$user->jid->bare()}']");
        try {
            if($users) {
                if($users[0]['role']) $room->role($user->nick, $users[0]['role']);
                if($users[0]['affiliation']) $room->affiliate($user->nick, $users[0]['affiliation']);
            }
        } catch (\Exception $e) {}
    }
}