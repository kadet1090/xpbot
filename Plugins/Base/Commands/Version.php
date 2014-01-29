<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Base\Commands;

use XPBot\Command;
use XPBot\Exceptions\CommandException;
use Kadet\Xmpp\Jid;

class Version extends Command
{
    public function execute($args)
    {
        if (!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $jid = isset($this->_author->room->users[$args[1]]) ?
            $this->_author->room->users[$args[1]]->jid :
            $args[1];

        if (!Jid::isJid($jid))
            throw new CommandException('Given jid is not valid.', __('errJidNotValid', $this->_lang));

        $jid = new Jid($jid);
        $this->_bot->version($jid, function ($reply) use ($args) {
            if ($reply['type'] != 'result') return;

            $this->_author->room->message(__('reply', $this->_lang, __CLASS__, array(
                'name'    => $reply->query->name,
                'version' => $reply->query->version,
                'os'      => (isset($reply->query->os) ? $reply->query->os : ''),
                'user'    => $args[1]
            )));
        });
    }
}