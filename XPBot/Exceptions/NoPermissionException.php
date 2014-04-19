<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Exceptions;

use Exception;

class NoPermissionException extends \Exception
{
    private $_command;

    public function __construct($command)
    {
        $this->_command = $command;
        parent::__construct('User has no permission to run this command.');
    }

    public function getCommand()
    {
        return $this->_command;
    }
}