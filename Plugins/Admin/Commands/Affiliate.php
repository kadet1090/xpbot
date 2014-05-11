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
use Kadet\Xmpp\Stanza\Iq;
use XPBot\Command;
use XPBot\Exceptions\CommandException;

class Affiliate extends Command
{
    const PERMISSION = 7;

    public function execute($args)
    {
        if (count($args) < 2)
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if (count($args) == 2)
            $this->_list($args);
        else
            $this->_set($args);
    }

    private function _set($args)
    {
        $args[2] = Jid::isJid($args[2]) ? new Jid($args[2]) : $args[2];

        try {
            $this->_author->room->affiliate($args[2], $args[1], $args[3]);
        } catch (\InvalidArgumentException $exception) {
            if ($exception->getMessage() == 'affiliation')
                throw new commandException('Wrong affiliation.', __('errWrongAffiliation', $this->_lang));

            if ($exception->getMessage() == 'who')
                throw new commandException('Wrong user.', __('errWrongUser', $this->_lang));
        }
    }

    private function _list($args)
    {
        try {
            $this->_author->room->affiliationList($args[1], function (Iq $packet) use ($args) {
                if ($packet->type == 'error') return __('Error', $this->_lang);

                $users = array();
                foreach ($packet->query->item as $user)
                    $users[] = $args['j'] ? $user['jid'] : strstr($user['jid'], '@', true) . (!empty($user->reason) && $args['r'] ? " - {$user->reason}" : '');

                $args['p'] ?
                    $this->_author->room->message(implode(", \n", $users)) :
                    $this->_author->privateMessage(implode(", \n", $users));
            });
        } catch (\InvalidArgumentException $exception) {
            if ($exception->getMessage() == 'affiliation')
                throw new commandException('Wrong affiliation.', __('errWrongAffiliation', $this->_lang));
        }
    }
}