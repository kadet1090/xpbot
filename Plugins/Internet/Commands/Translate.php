<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet\Commands;

use XPBot\Command;
use XPBot\Exceptions\CommandException;
use XPBot\Plugins\Internet\InternetPlugin;

class Translate extends Command
{
    public function execute($args)
    {
        if (!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $to = isset($args['t']) ?
            $args['t'] :
            $this->_bot->getFromConfig('translateLang', 'internet', strstr($this->_lang, '_', true));

        return InternetPlugin::$translator->translate($args[1], $to, $args['f']);
    }
}