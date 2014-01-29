<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Base\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\Exceptions\CommandException;
use XPBot\System\Xmpp\Jid;

class Join extends Command
{
    const PERMISSION = 9;

    public function execute($args)
    {
        if (!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $jid = new Jid($args[1]);
        if (!$jid->isChannel())
            throw new commandException('Specified JID is not channel.', __('errNotChannel', $this->_lang));

        $this->_bot->join($jid, $args[2] ? $args[2] : $this->_bot->config->xmpp->nickname);
    }
}