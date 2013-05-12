<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\System\Utils;


class Logger
{
    public static $logsDir = './Logs/';

    public static function debug($message)
    {
        if (DEBUG_MODE >= 1)
            echo '[' . date('H:i:s') . ' d] ' . $message . PHP_EOL;

        self::_addToLog('Debug.log', $message);
    }

    public static function warning($message)
    {
        echo '[' . date('H:i:s') . ' !] ' . $message . PHP_EOL;
        self::_addToLog('Bot.log', $message);
    }

    public static function info($message)
    {
        echo '[' . date('H:i:s') . ' i] ' . $message . PHP_EOL;
        self::_addToLog('Bot.log', $message);
    }

    public static function error($message)
    {
        echo '[' . date('H:i:s') . ' x] ' . $message . PHP_EOL;
        self::_addToLog('Bot.log', $message);
    }

    private static function _addToLog($file, $message)
    {
        file_put_contents(
            self::$logsDir . $file,
            '[' . date('H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }
}