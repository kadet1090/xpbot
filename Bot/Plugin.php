<?php
namespace XPBot\Bot;

abstract class Plugin implements PluginInterface {
    /**
     * Bot instance.
     * @var Bot
     */
    protected $_bot;

    /**
     * Indicates if plugin is loaded or not.
     * @var bool
     */
    protected $_loaded;

    /**
     * @param Bot $bot Bot instance.
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