<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Base\Commands;

use Kadet\Xmpp\Jid;
use XPBot\Command;
use XPBot\Exceptions\CommandException;

class Say extends Command
{
    public function execute($args)
    {
        if (!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if (isset($args['r'])) {
            if ($this->_author->config->permission->has('base/say-to'))
                throw new CommandException('User has no permission to do that.', __('errNoPermission', 'pl'));

            $jid = isset($this->_author->room->users[$args['r']]) ?
                $this->_author->room->users[$args['r']]->jid :
                $args['r'];

            if (!Jid::isJid($jid))
                throw new CommandException('Given jid is not valid.', __('errJidNotValid', $this->_lang));

            $jid = new Jid($jid);
            $this->_bot->message($jid, $args[1], $jid->isChannel() ? 'groupchat' : 'chat');
        } else
            return $args[1];
    }
}