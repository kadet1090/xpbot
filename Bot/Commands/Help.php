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
    private $_regex = '/.*/';
    private $_all = false;
    private $_permissions = false;

    public function execute($args)
    {
        $this->_regex = isset($args['r']) ?
            $args['r'] :
            $this->_regex;

        $this->_all         = isset($args['a']);
        $this->_permissions = isset($args['l']);

        if (isset($args[1]))
            return $this->_commandHelp($args[1]);

        if ($args['m'] === true)
            $str = $this->_modulesList();
        elseif (isset($args['m']))
            $str = $this->_commandsInModule($args['m']); else
            $str = $this->_allCommands();

        return __('commands', $this->_lang, __CLASS__, array('commands' => $str));
    }

    private function _allCommands($all = false, $permissions = false)
    {
        $modules = array_keys($this->_bot->getCommands());

        $str = '';
        foreach ($modules as $module) {
            $str .= "$module: \n";
            $str .= $this->_commandsInModule($module);
        }

        return $str;
    }

    private function _commandsInModule($module = 'default')
    {
        $commands = $this->_bot->getCommands();

        if (!isset($commands[$module]))
            throw new CommandException('Specified module not exists.', __('errModuleNotExists', $this->_lang));

        $commands = $commands[$module];
        $str      = '';
        foreach ($commands as $command => $class) {
            if (!$this->_all && !$class::hasPermission($this->_author)) continue;

            if (preg_match($this->_regex, $command))
                $str .= "\t" . ($this->_permissions ? '[' . $class::PERMISSION . '] ' : '') . "$command - " . $class::getShortHelp($this->_lang) . "\n";
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
        $result = $this->_bot->getCommand($command);
        if ($result === false)
            throw new CommandException('Specified command not exists.', __('errCommandNotExists', $this->_lang));

        if (is_array($result)) {
            $str = __('commandAmbiguous', $this->_lang, 'default', array('command' => $command));
            foreach ($result as $package => $class) {
                $str .= "\t$package-$command - $class\n";
            }

            return $str;
        }

        return $result::getHelp($this->_lang, $command);
    }
}