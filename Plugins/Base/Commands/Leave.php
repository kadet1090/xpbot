<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Base\Commands;
;

use XPBot\Command;
use XPBot\Exceptions\CommandException;
use Kadet\Xmpp\Jid;

class Leave extends Command
{
    const PERMISSION = 8;

    public function execute($args)
    {
        if (!isset($args[1]) && !isset($this->_author->room))
            throw new CommandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $jid = $args[1] ? new Jid($args[1]) : $this->_author->room->jid;
        if (!$jid->isChannel())
            throw new CommandException('Specified JID is not channel.', __('errNotChannel', $this->_lang));

        $this->_bot->leave($jid);
    }
}