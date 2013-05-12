<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\System\Utils {
    class Language
    {
        private static $_phrases = array();

        static function load($filename)
        {
            if (!file_exists($filename)) throw new \InvalidArgumentException('filename');

            $xml = simplexml_load_file($filename);

            if (!isset($xml['lang']) || empty($xml['lang'])) throw new \Exception(''); //todo: Exception type for parse errors.

            $lang                  = (string)$xml['lang'];
            self::$_phrases[$lang] = array();

            foreach ($xml->phrase as $phrase) {
                $namespace = isset($phrase['ns']) ? $phrase['ns'] : 'default';
                $name      = $phrase['id'];

                self::$_phrases[$lang][$namespace . ':' . $name] = (string)$phrase;
            }
        }

        static function loadDir($dir)
        {
            if (!file_exists($dir)) throw new \InvalidArgumentException('filename');

            $iterator = new \RecursiveDirectoryIterator(
                $dir,
                \RecursiveDirectoryIterator::SKIP_DOTS || \RecursiveDirectoryIterator::UNIX_PATHS
            );

            foreach ($iterator as $file) {
                self::load($file->getPathname());
            }
        }

        static function get($phrase, $lang, $namespace = 'default', $arguments = array())
        {
            $prepared = array();
            foreach ($arguments as $name => $value)
                $prepared['{%' . $name . '}'] = $value;

            if ($namespace == 'default')
                $namespace = getCaller();

            if (!isset(self::$_phrases[$lang][$namespace . ':' . $phrase]))
                $namespace = 'default';

            if (isset(self::$_phrases[$lang][$namespace . ':' . $phrase]))
                return str_replace(array_keys($prepared), array_values($prepared), self::$_phrases[$lang][$namespace . ':' . $phrase]);
            else return '#' . $namespace . ':' . $phrase;
        }
    }
}