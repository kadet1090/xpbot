<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 08.07.13
 * Time: 12:39
 * To change this template use File | Settings | File Templates.
 */

namespace Kadet\Utils;


class AutoLoader {
    /**
     * @var AutoLoader[]
     */
    static protected $_loaders = array();

    /**
     * Registers new autoloader
     *
     * @param AutoLoader $loader
     */
    static protected function _register(AutoLoader $loader) {
        spl_autoload_register(array($loader, 'load'));
    }

    /**
     * Unregisters specified autoloader
     *
     * @param AutoLoader $loader
     */
    static protected function _unregister(AutoLoader $loader) { }

    private $namespace = '';
    private $directory = '';

    /**
     * @param string $namespace Namespace to autoload.
     * @param string $directory Directory where classes of specified namespace are placed.
     */
    public function __construct($namespace, $directory) {
        $this->namespace = $namespace.(substr($namespace, -1) == '\\' ? '' : '\\');
        $this->directory = $directory.(substr($directory, -1) == '/' ? '' : '/');
    }

    /**
     * Autoload function.
     *
     * @param string $class Class to be loaded.
     */
    public function load($class) {
        if(preg_match('#^'.str_replace('\\', '\\\\', $this->namespace).'#si', $class)) {
            $class = preg_replace('#^'.str_replace('\\', '\\\\', $this->namespace).'#si', '', $class);
            include_once $this->directory.str_replace('\\', '/', $class).'.php';
        }
    }

    /**
     * Register autolader.
     */
    public function register() {
        self::_register($this);
    }

    /**
     * Unregister autoloader.
     */
    public function unregister() {
        self::_unregister($this);
    }
}