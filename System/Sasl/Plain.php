<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\System\Sasl;


class Plain implements MechanismInterface
{
    public function challenge($packet)
    {
        // Plain has no challenge
    }

    public function get($jid, $password)
    {
        return base64_encode("\0{$jid->name}\0$password");
    }
}