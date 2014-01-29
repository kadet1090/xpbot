<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 18.01.14
 * Time: 21:40
 */

namespace XPBot\Exceptions;


use Exception;

class CommandAmbiguousException extends \Exception
{
    private $_command;
    private $_references;

    public function __construct($command, array $references, Exception $previous = null)
    {
        parent::__construct('Command ' . $command . ' is ambiguous.', 1005, $previous);

        $this->_command    = $command;
        $this->_references = $references;
    }

    public function getCommand()
    {
        return $this->_command;
    }

    public function getReferences()
    {
        return $this->_references;
    }
}