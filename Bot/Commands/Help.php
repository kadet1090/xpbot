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

class Help extends Command
{
    public function execute($args, $groupchat)
    {
        $commands = $this->_bot->getCommands();
        $str      = '';
        foreach ($commands as $name => $package) {
            $str .= "$name: \n";
            foreach ($package as $command => $class) {
                $str .= "\t$command - " . $class::getShortHelp($this->_lang) . "\n";
            }
        }

        $this->_author->room->message($str);
    }
}