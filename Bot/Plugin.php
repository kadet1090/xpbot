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
     * Plugins manifest.
     * @var \SimpleXMLElement
     */
    protected $_manifest;

    /**
     * @param Bot               $bot      Bot instance.
     * @param \SimpleXMLElement $manifest Plugins manifest file.
     */
    public function __construct(Bot $bot, $manifest)
    {
        $this->_bot = $bot;
        $this->_manifest = $manifest;
    }

    /**
     * Toggles plugin state.
     */
    public function toggle()
    {
        $this->_loaded ? $this->unload() : $this->load(); // Best code line EVAH.
    }
}