<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet;

use XPBot\Bot\Plugin;
use XPBot\Plugins\Math\Lib\RpnParser;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\User;

class InternetPlugin extends Plugin {

    public function getInfo()
    {
        // TODO: Implement getInfo() method.
    }

    public function load()
    {
        $this->_bot->findCommands('Plugins/Internet/Commands/', 'internet', 'XPBot\\Plugins\\Internet\\Commands');
    }

    public function unload()
    {
        // TODO: Implement unload() method.
    }
}