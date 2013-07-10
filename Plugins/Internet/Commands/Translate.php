<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet\Commands;

use XPBot\Bot\Bot;
use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\Plugins\Internet\InternetPlugin;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Translate extends Command
{
    public function execute($args)
    {
        if(!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $to = isset($args['t']) ?
            $args['t'] :
            $this->_bot->getFromConfig('translateLang', 'internet', $this->_lang);

        return InternetPlugin::$translator->translate($args[1], $to, $args['f']);
    }
}