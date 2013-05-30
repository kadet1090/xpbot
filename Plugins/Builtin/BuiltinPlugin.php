<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Builtin;


use XPBot\Bot\Plugin;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\User;

class BuiltinPlugin extends Plugin{

    public function getInfo()
    {
        // TODO: Implement getInfo() method.
    }

    public function load()
    {
        $this->_bot->findCommands('Plugins/Builtin/Commands/', 'builtin', 'XPBot\\Plugins\\Builtin\\Commands');
    }

    public function unload()
    {
        // nothing here
    }
}