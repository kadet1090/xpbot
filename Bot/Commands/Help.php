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

class Help extends Command
{
    public function execute($args, $groupchat)
    {
        $regex = isset($args['r']) ? $args['r'] : '/.*/';

        if (isset($args[1]))
            return $this->_commandHelp($args[1]);

        if ($args['m'] === true)
            $str = $this->_modulesList();
        elseif (isset($args['m']))
            $str = $this->_commandsInModule($args['m'], $regex); else
            $str = $this->_allCommands($regex);

        return __('commands', $this->_lang, __CLASS__, array('commands' => $str));
    }

    private function _allCommands($regex = '/.*/')
    {
        $modules = array_keys($this->_bot->getCommands());

        $str = '';
        foreach ($modules as $module) {
            $str .= "$module: \n";
            $str .= $this->_commandsInModule($module, $regex);
        }

        return $str;
    }

    private function _commandsInModule($module = 'default', $regex = '/.*/')
    {
        $commands = $this->_bot->getCommands();

        if (!isset($commands[$module]))
            throw new CommandException('Specified module not exists.', __('errModuleNotExists', $this->_lang, __CLASS__));

        $commands = $commands[$module];
        $str      = '';
        foreach ($commands as $command => $class) {
            if (preg_match($regex, $command))
                $str .= "\t$command - " . $class::getShortHelp($this->_lang) . "\n";
        }

        return $str;
    }

    private function _modulesList()
    {
        return __('modules', $this->_lang, __CLASS__, array(
            'modules' => implode(', ', array_keys($this->_bot->getCommands()))
        ));
    }

    private function _commandHelp($command)
    {
        $command = $this->_bot->getCommand($command);
        if ($command === false) return __('commandAmbiguous', $this->_lang);

        if (is_array($command)) {
            $str = __('commandAmbiguous', $this->_lang, 'default', array('command' => $command));
            foreach ($command as $package => $class) {
                $str .= "\t$package-$command - $class\n";
            }

            return $str;
        }

        return $command::getHelp($this->_lang);
    }
}