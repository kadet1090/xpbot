<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 19.01.14
 * Time: 00:30
 */

namespace XPBot\Commands;

use XPBot\Command;

class Quit extends Command
{
    const PERMISSION = 10;

    public function execute($args)
    {
        if (isset($args['restart']) || isset($args['r'])) {
            if ($args[1])
                if (isset($this->_bot->logger)) $this->_bot->logger->info("Restarting bot because {$args[0]}.");
                else if (isset($this->_bot->logger)) $this->_bot->logger->info("Restarting bot.");

            restart();
        }
        if ($args[1])
            if (isset($this->_bot->logger)) $this->_bot->logger->info("Exiting bot because {$args[0]}.");
            else if (isset($this->_bot->logger)) $this->_bot->logger->info("Exiting bot.");

        exit;
    }
}