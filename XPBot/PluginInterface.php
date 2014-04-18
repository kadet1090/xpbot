<?php
namespace XPBot;
/**
 * Interface that plugins must implement.
 *
 * @package XPBot
 */
interface PluginInterface
{
    /**
     * Returns information about plugin.
     * Returns plugin specific information like version or author.
     *
     * Returned array should contain these keys:
     *      author      => Plugins author,
     *      description => Plugins description,
     *      version     => Plugin version,
     *      [mail]      => Mail contact to author,
     *      [xmpp]      => Xmpp contact to author.
     *
     * Keys in [] are optional, but at least one of contact variables should be filled :)
     *
     * @todo Add Summary.xml for plugins.
     *
     * @return array
     */
    public function getInfo();

    /**
     * Loads plugin.
     *
     * @return bool Indicates if plugin loaded properly.
     */
    public function load();

    /**
     * Unloads plugin.
     *
     * @return bool Indicates if plugin unloaded properly.
     */
    public function unload();
}