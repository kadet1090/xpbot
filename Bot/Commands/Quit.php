<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 19.01.14
 * Time: 00:30
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