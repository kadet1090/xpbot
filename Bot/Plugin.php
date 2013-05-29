<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Bot;


abstract class Plugin implements PluginInterface {
    protected $_bot;
    protected $_loaded;

    /**
     * @param Bot $bot
     */
    public function __construct(Bot $bot)
    {
        $this->_bot = $bot;
    }

    /**
     * Toggles plugin state.
     */
    public function toggle()
    {
        $this->_loaded ? $this->unload() : $this->load(); // Best code line EVAH.
    }
}