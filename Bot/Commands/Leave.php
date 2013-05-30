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
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Leave extends Command
{
    const PERMISSION = 9;

    public function execute($args)
    {
        if(!isset($args[1]) && !isset($this->_author->room))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $jid = $args[1] ? new Jid($args[1]) : $this->_author->room->jid;
        if(!$jid->isChannel())
            throw new commandException('Specified JID is not channel.', __('errNotChannel', $this->_lang));

        $this->_bot->leave($jid);
    }
}