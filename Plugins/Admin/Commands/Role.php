<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\Exceptions\CommandException;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\Room;

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

            if (isset($args['a'])) {
                if (!isset($this->_author->room->configuration->auto))
                    $this->_author->room->configuration->addChild('auto');

                $jid = isset($this->_author->room->users[$args[2]]) ?
                    $this->_author->room->users[$args[2]]->jid :
                    new Jid($args[2]);

                $users = $this->_author->room->configuration->auto->xpath("//user[@jid='{$jid->bare()}']");
                if ($users) {
                    $user = $users[0];
                } else {
                    $user = $this->_author->room->configuration->auto->addChild('user');
                    $user->addAttribute('jid', $jid->bare());
                }

                $user['role'] = $args[1];

                Room::save(); // save config
            }
        } catch (\InvalidArgumentException $exception) {
            if ($exception->getMessage() == 'affiliation')
                throw new commandException('Wrong affiliation.', __('errWrongAffiliation', $this->_lang));
        }
    }
}