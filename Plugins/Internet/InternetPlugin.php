<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet;

use XPBot\Plugin;
use XPBot\Plugins\Internet\Lib\MsTranslator;
use XPBot\Utils\Language;

class InternetPlugin extends Plugin {
    /**
     * @var MsTranslator
     */
    public static $translator;

    public function load()
    {
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Google', 'internet', 'google');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Weather', 'internet', 'weather');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Translate', 'internet', 'translate');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Rss', 'internet', 'rss');
        $this->_bot->registerCommand(__NAMESPACE__.'\\Commands\\Wikipedia', 'internet', 'wikipedia');

        self::$translator = new MsTranslator(
            $this->_bot->getFromConfig('internet', 'translatorAppId', 'XPBot'),
            $this->_bot->getFromConfig('internet', 'translatorSecret', 'ShNYca4WSxgaau0eG0wvBCX4ARIIxX5LKhlK3QRWmx8=')
        );
        Language::loadDir(dirname(__FILE__).'/Languages/');
    }

    public function unload()
    {
        $this->_bot->registerCommand('internet', 'google');
        $this->_bot->registerCommand('internet', 'weather');
        $this->_bot->registerCommand('internet', 'translate');
        $this->_bot->registerCommand('internet', 'rss');
        $this->_bot->registerCommand('internet', 'wikipedia');
    }
}