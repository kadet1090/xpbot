<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Builtin\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Clean extends Command
{
    const PERMISSION = 4;

    public function execute($args)
    {
        $count   = min(isset($args[1]) ? parseNumber($args[1]) : $this->_bot->getFromConfig('defaultCleanCount', 'builtin', 5), 10);
        $message = isset($args['m']) ? $args['m'] : $this->_bot->getFromConfig('defaultCleanMsg', 'builtin', "\0");

        for($i = 0; $i < $count; $i++) {
            $this->_author->room->message($message);
            usleep(500000);
        }

        if(!$args['s'])
            return __('done', $this->_lang);
    }
}