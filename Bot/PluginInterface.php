<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\Bot;


interface PluginInterface {
    public function getInfo();
    public function load();
    public function unload();
}