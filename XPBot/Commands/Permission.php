<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Commands;

use Kadet\Xmpp\Jid;
use XPBot\Command;
use XPBot\Exceptions\CommandException;

class Permission extends Command
{
    const PERMISSION = 10; // ONLY FOR MASTERZ

    public function execute($args)
    {
        if (isset($args[1]) && !Jid::isJid($args[1]) && !isset($this->_author->room->users[$args[1]]))
            throw new commandException('This user is not present on that channel.', __('errUserNotPresent', $this->_lang));

        if (isset($args[1]))
            $args[1] = Jid::isJid($args[1]) ? $args[1] : $this->_author->room->users[$args[1]]->jid->bare();

        if (!isset($args[1]))
            return $this->all();
        elseif (!isset($args[2]))
            return $this->get($args[1]);
        else
            $this->set($args[1], (int)$args[2]);
    }

    public function all()
    {
        $result = array();
        foreach ($this->_bot->config->users as $name => $user) {
            if (isset($user->permission))
                $result[] = "{$name} - {$user->permission}";
        }

        return implode(PHP_EOL, $result);
    }

    public function get($jid)
    {
        if (isset($this->_bot->config->users[$jid]) && isset($this->_bot->config->users[$jid]->permission))
            return (int)$this->_bot->config->users[$jid]->permission;
        else
            return __('errSpecifiedUserNotKnown', $this->_lang, __CLASS__);
    }

    public function set($jid, $permission = -1)
    {
        $user = $this->_bot->config->users[$jid];

        if ($permission != -1)
            $user->permission = $permission;
        else
            unset($user->permission);

        $this->_bot->updatePermission(new Jid($jid));
        $this->_bot->config->save();
    }
}