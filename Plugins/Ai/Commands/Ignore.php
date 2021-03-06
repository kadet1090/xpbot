<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 08.02.14
 * Time: 17:29
 */

namespace XPBot\Plugins\Ai\Commands;

use XPBot\Command;

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

        }
    }

    private function _list($args)
    {
        try {
            $this->_author->room->affiliationList($args[1], function ($packet) use ($args) {
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