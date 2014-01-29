<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Commands;

use XPBot\Command;
use XPBot\Exceptions\CommandException;
use Kadet\Xmpp\Jid;

class Permission extends Command
{
    const PERMISSION = 10; // ONLY FOR MASTERZ

    public function execute($args)
    {
        if (!Jid::isJid($args[1]) && !isset($this->_author->room->users[$args[1]]))
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
        foreach ($this->_bot->users->user as $user) {
            if (isset($user['permission']))
                $result[] = "{$user['jid']} - {$user['permission']}";
        }

        return implode(PHP_EOL, $result);
    }

    public function get($jid)
    {
        $users = $this->_bot->users->xpath("//user[@jid='{$jid}']");
        if ($users && isset($users[0]['permission']))
            return (int)$users[0]['permission'];
        else
            return __('errSpecifiedUserNotKnown', $this->_lang, __CLASS__);
    }

    public function set($jid, $permission = -1)
    {
        $users = $this->_bot->users->xpath("//user[@jid='{$jid}']");
        $user  = $users ? $users[0] : $this->_bot->users->addChild('user');

        if (!isset($user["jid"])) $user->addAttribute('jid', $jid);

        if ($permission != -1)
            $user['permission'] = $permission;
        else
            unset($user['permission']);

        $this->_bot->updatePermission(new Jid($jid));
        $this->_bot->users->asXML('./Config/Users.xml');
    }
}