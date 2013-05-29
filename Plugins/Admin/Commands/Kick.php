<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Kick extends Command
{
    const PERMISSION = 6;

    public function execute($args)
    {
        if(count($args) < 2)
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if(!isset($this->_author->room->users[$args[1]]))
            throw new commandException('This user is not present on that channel.', __('errUserNotPresent', $this->_lang));

        $this->_author->room->kick($args[1], $args[2]);
    }
}