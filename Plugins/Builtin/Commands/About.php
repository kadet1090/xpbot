<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Builtin\Commands;

use XPBot\Bot\Bot;
use XPBot\Bot\Command;

class About extends Command
{
    const PERMISSION = 1;

    public function execute($args)
    {
        return __('about', $this->_lang, 'default', array(
            'version' => Bot::BOT_VERSION,
        ));
    }
}