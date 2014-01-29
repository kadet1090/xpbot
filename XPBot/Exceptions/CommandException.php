<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 19.01.14
 * Time: 00:01
 */

namespace XPBot\Exceptions;

/**
 * Exception thrown by commands when some error occurs.
 *
 * @package XPBot\Exceptions
 */
class CommandException extends \Exception
{
    /**
     * Message printed to console (english localized!)
     * @var string
     */
    protected $_consoleMessage;

    protected $_command;

    /**
     * @param string                $cmdMsg   Message that will be added to console and log, in english.
     * @param string|null           $command
     * @param string                $message  Localized message that will be sent to client as response.
     * @param int                   $code     Exception code.
     * @param CommandException|null $previous Previous exception.
     */
    public function __construct($cmdMsg = '', $message = '', $command = null, $code = 0, $previous = null)
    {
        $this->_command        = $command == null ? $command : getCaller();
        $this->_consoleMessage = $cmdMsg;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets english localized message for console and logging.
     *
     * @return string
     */
    public function getConsoleMessage()
    {
        return $this->_consoleMessage;
    }

    public function getCommand()
    {
        return $this->_command;
    }
}