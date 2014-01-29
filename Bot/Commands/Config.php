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
use XPBot\Bot\Exceptions\CommandException;

class Config extends Command
{
    const PERMISSION = 9;

    public function execute($args)
    {
        if(!isset($args[1]))
            return $this->all();
        elseif(!isset($args[2]))
            return $this->get($args);
        else
            $this->set($args);
    }

    private function set($args)
    {
        if(!preg_match('/^([a-zA-Z][a-zA-Z0-9\-\_]{0,31}):([a-zA-Z][a-zA-Z0-9\-\_]{0,31})/si', $args[1], $matches))
            throw new commandException(
                'Variable name contains not allowed characters.',
                __('errVarContainsNotAllowedCharacters', $this->_lang, __CLASS__)
            );

        if($args[2] == 'default')
            $this->_bot->removeFromConfig($matches[2], $matches[1]);
        else
            $this->_bot->setInConfig($matches[2], $matches[1], $args[2]);
    }

    private function get($args)
    {
        if(!preg_match('/^([a-zA-Z][a-zA-Z0-9\-\_]{0,31}):([a-zA-Z][a-zA-Z0-9\-\_]{0,31})/si', $args[1], $matches))
            throw new commandException(
                'Variable name contains not allowed characters.',
                __('errVarContainsNotAllowedCharacters', $this->_lang, __CLASS__)
            );
        return $this->_bot->getFromConfig($matches[2], $matches[1], __('errVariableNotExists', $this->_lang, __CLASS__));
    }

    private function all()
    {
        if(!isset($this->_bot->config->plugins->var))
            return __('errNoVariables', $this->_lang, __CLASS__);

        $str = '';
        foreach($this->_bot->config->plugins->var as $var)
            $str .= "{$var['namespace']}:{$var['name']} = $var" . PHP_EOL;
        return $str;
    }
}