<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin\Commands;

use XPBot\Bot\Command;

class Subject extends Command
{
    const PERMISSION = 6;

    public function execute($args)
    {
        if ($this->_type != 'groupchat') return;

        if (isset($args[1]))
            $this->_author->room->setSubject($args[1]);
        elseif (isset($args['a']) && is_string($args['a']))
            $this->_author->room->setSubject($this->_author->room->subject . ' | ' . $args['a']);
        elseif (isset($args['p']) && is_string($args['p']))
            $this->_author->room->setSubject($args['p'] . ' | ' . $this->_author->room->subject);
        else
            return $this->_author->room->subject;
    }
}