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

class AdminPlugin extends Plugin{

    public function getInfo()
    {
        // TODO: Implement getInfo() method.
    }

    public function load()
    {
        $this->_bot->findCommands('Plugins/Admin/Commands/', 'admin', 'XPBot\\Plugins\\Admin\\Commands');
    }

    public function unload()
    {
        // TODO: Implement unload() method.
    }
}