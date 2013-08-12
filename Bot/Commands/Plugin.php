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
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Plugin extends Command
{
    const PERMISSION = 9;

    public function execute($args) {
        if(!isset($args[1]))
            return $this->all($args);
        else
            return $this->{$args[1]}($args);
    }

    public function all($args) {
        $plugins = $this->_bot->getPlugins();

    }
}