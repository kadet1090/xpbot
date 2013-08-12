<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Base;


use XPBot\Bot\Plugin;
use XPBot\System\Utils\Language;
use XPBot\System\Xmpp\Room;

class BasePlugin extends Plugin {
    public function load()
    {
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Clear',   'base', 'clear');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Join',    'base', 'join');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Leave',   'base', 'leave');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Ping',    'base', 'ping');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Say',     'base', 'say');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Users',   'base', 'users');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Version', 'base', 'version');

        Language::loadDir(dirname(__FILE__).'/Languages/');
    }

    public function unload()
    {
        $this->_bot->unregisterCommand('base', 'clear');
        $this->_bot->unregisterCommand('base', 'join');
        $this->_bot->unregisterCommand('base', 'leave');
        $this->_bot->unregisterCommand('base', 'ping');
        $this->_bot->unregisterCommand('base', 'say');
        $this->_bot->unregisterCommand('base', 'users');
        $this->_bot->unregisterCommand('base', 'version');
    }
}