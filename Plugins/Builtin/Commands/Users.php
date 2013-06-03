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

class Users extends Command
{
    const PERMISSION = 4;

    public function execute($args)
    {
        $pattern = (!empty($args[1]) ? $args[1] : $this->_bot->getFromConfig('userPattern', 'default', '%n - %a [%r] %s'));
        $users = array();
        foreach($this->_author->room->users as $user) {
            $replace = array(
                '%n' => $user->nick,
                '%r' => $user->role,
                '%a' => $user->affiliation,
                '%s' => $user->show,
                '%j' => $user->jid,
                '%t' => time() - $user->jointime,
                '%p' => $user->permission,
                '%d' => str_replace("\n", '', $user->status),
                '%f' => date("H:i:s d.m.Y", $user->jointime)
            );

            $users[] = str_replace(array_keys($replace), array_values($replace), $pattern);
        }
        return implode("\n", $users);
    }
}