<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet;

use XPBot\Bot\Plugin;
use XPBot\Plugins\Internet\Lib\MsTranslator;
use XPBot\Plugins\Math\Lib\RpnParser;
use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Language;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\User;

class InternetPlugin extends Plugin {
    /**
     * @var MsTranslator
     */
    public static $translator;

    public function getInfo()
    {
        // TODO: Implement getInfo() method.
    }

    public function load()
    {
        $this->_bot->findCommands('Plugins/Internet/Commands/', 'internet', 'XPBot\\Plugins\\Internet\\Commands');
        self::$translator = new MsTranslator('XPBot', 'ShNYca4WSxgaau0eG0wvBCX4ARIIxX5LKhlK3QRWmx8=');
        Language::loadDir('Plugins/Internet/Languages/');
    }

    public function unload()
    {
        // TODO: Implement unload() method.
    }
}