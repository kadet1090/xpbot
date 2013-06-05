<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Builtin\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Alias extends Command
{
    const PERMISSION = 8;

    public function execute($args)
    {
        if(!isset($args[1]))
            return $this->all();
        else
            return $this->{$args[1]}($args);
    }

    private function set($args)
    {
        if(!isset($args[3]) || !isset($args[2]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if(!preg_match('/^[^\s]{0,32}/si', $args[2]))
            throw new commandException(
                'Alias contains not allowed characters.',
                __('errAliasContainsNotAllowedCharacters', $this->_lang, __CLASS__)
            );

        if(!$this->_bot->commandExists($args[3]))
            throw new commandException('Specified command not exists.', __('errCommandNotExist', $this->_lang));

        $this->_bot->aliases[$args[2]] = $this->_bot->getFullCommandName($args[3]);
    }

    private function remove($args)
    {
        if(!isset($args[2]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if(!isset($this->_bot->aliases[$args[2]]))
            throw new commandException('Specified alias not exists.', __('errAliasNotExist', $this->_lang));

        unset($this->_bot->aliases[$args[2]]);
    }

    private function command($args)
    {
        if(!isset($args[2]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if(!$this->_bot->commandExists($args[2]))
            throw new commandException('Specified command not exists.', __('errCommandNotExist', $this->_lang));

        return __('commandAliases', $this->_lang, __CLASS__, array(
            'command' => $args[2],
            'aliases' => implode(', ', $this->_bot->getCommandAliases($args[2]))
        ));
    }

    private function all()
    {
        $str = '';
        foreach($this->_bot->aliases as $alias => $command)
            $str .= $alias . ' -> ' . $command . PHP_EOL;
        return $str;
    }

    private function add($args) { $this->set($args); }
    private function delete($args) { $this->remove($args); }

    public function __call($name, $arguments)
    {
        throw new commandException('Invalid action.', __('errInvalidAction', $this->_lang));
    }
}