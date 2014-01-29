<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Base\Commands;

use XPBot\Command;

class Clear extends Command
{
    const PERMISSION = 4;

    public function execute($args)
    {
        $count = min(isset($args[1]) ? parseNumber($args[1]) : $this->_bot->getFromConfig('defaultClearCount', 'builtin', 5), 10);
        $message = isset($args['m']) ? $args['m'] : $this->_bot->getFromConfig('defaultClearMessage', 'builtin', "\0");

        //todo: Timers
        for ($i = 0; $i < $count; $i++) {
            $this->_author->room->message($message);
            usleep(500000);
        }

        if (!$args['s'])
            return __('done', $this->_lang);
    }
}