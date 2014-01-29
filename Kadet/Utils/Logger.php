<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace Kadet\Utils;


class Logger
{
    /**
     * Directory where logs are stored.
     * @var string
     */
    public static $logsDir = './Logs/';

    /**
     * Prints debug message to output and log.
     * @param string $message Message to print.
     */
    public static function debug($message)
    {
        if (DEBUG_MODE >= 1)
            echo "\033[1;30m[" . date('H:i:s') . " ?] \033[0m" . $message . PHP_EOL;

        self::_addToLog('Debug.log', $message);
    }

    /**
     * Prints warning to output and log.
     * @param string $message Message to print.
     */
    public static function warning($message)
    {
        echo "\033[1;33m[" . date('H:i:s') . " !] \033[0m" . $message . PHP_EOL;
        self::_addToLog('Bot.log', $message);
    }

    /**
     * Prints info to output and log.
     * @param string $message Message to print.
     */
    public static function info($message)
    {
        echo "\033[1;32m[" . date('H:i:s') . " i] \033[0m" . $message . PHP_EOL;
        self::_addToLog('Bot.log', $message);
    }

    /**
     * Prints error message to output and log.
     * @param string $message Message to print.
     */
    public static function error($message)
    {
        echo "\033[1;31m[" . date('H:i:s') . " x] \033[0m" . $message . PHP_EOL;
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