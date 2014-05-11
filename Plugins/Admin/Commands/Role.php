<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin\Commands;

use Kadet\Xmpp\Jid;
use XPBot\Command;
use XPBot\Config\UsersConfig;
use XPBot\Exceptions\CommandException;

class Role extends Command
{
    const PERMISSION = 6;

    public function execute($args)
    {
        if (count($args) < 3)
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if (!isset($this->_author->room->users[$args[2]]) && !isset($args['a']))
            throw new commandException('This user is not present on that channel.', __('errUserNotPresent', $this->_lang));

        try {
            $this->_author->room->role($args[2], $args[1], $args[3]);

            if (isset($args['a']))
                $this->auto($args[1], $args[2]);

        } catch (\InvalidArgumentException $exception) {
            if ($exception->getMessage() == 'role')
                throw new commandException('Wrong role.', __('errWrongRole', $this->_lang));
        }
    }

    private function auto($role, $user)
    {
        if (!isset($this->_bot->config->rooms[$this->_author->room->jid->bare()]->auto))
            $this->_bot->config->rooms[$this->_author->room->jid->bare()]->auto = new UsersConfig();

        $auto = $this->_bot->config->rooms[$this->_author->room->jid->bare()]->auto;

        $jid = isset($this->_author->room->users[$user]) ?
            $this->_author->room->users[$user]->jid :
            new Jid($user);

        $auto[$jid->bare()]->role = $role;

        $this->_bot->config->save();
    }
}