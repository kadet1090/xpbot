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
        private static $_variables = array();
        private static $_phrases = array();

        /**
         * Load language file.
         *
         * @param string $filename Name of language file.
         * @throws \InvalidArgumentException
         * @throws \Exception
         */
        public static function load($filename)
        {
            if (!file_exists($filename)) throw new \InvalidArgumentException('filename');

            $xml = simplexml_load_file($filename);

            if (!isset($xml['lang']) || empty($xml['lang'])) throw new \Exception('Language has'); //todo: Exception type for parse errors.

            $lang = (string)$xml['lang'];
            foreach ($xml->phrase as $phrase) {
                $namespace = isset($phrase['ns']) ? $phrase['ns'] : 'default';
                $name      = $phrase['id'];

                self::$_phrases[$lang][$namespace . ':' . $name] = multilineTrim((string)$phrase);
            }
        }

        public static function loadDir($dir)
        {
            if (!file_exists($dir)) throw new \InvalidArgumentException('filename');

            $iterator = new \RecursiveDirectoryIterator(
                $dir,
                \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::UNIX_PATHS
            );

            foreach ($iterator as $file) {
                if($file->isDir()) continue;
                self::load($file->getPathname());
            }
        }

        public static function get($phrase, $lang, $namespace = 'default', $arguments = array())
        {
            $prepared  = array();
            $arguments = array_merge($arguments, self::$_variables);

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

        static function setGlobalVar($name, $value)
        {
            self::$_variables[$name] = $value;
        }
    }
}