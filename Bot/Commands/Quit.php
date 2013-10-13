<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Bot\Commands;

use XPBot\Bot\Command;
use XPBot\System\Utils\Logger;

class Quit extends Command
{
    const PERMISSION = 10;

    public function execute($args)
    {
        if ($args[1]) Logger::info("Exiting bot because {$args[0]}.");
        else Logger::info("Exiting bot.");
        exit;
    }
}