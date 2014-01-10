<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\System\Sasl;


use XPBot\System\Xmpp\Jid;

class SaslFactory {
    protected static $_mechanisms = array(
        'PLAIN' => 'XPBot\\System\\Sasl\\Plain',
        'DIGEST-MD5' => 'XPBot\\System\\Sasl\\DigestMd5',
    );

    /**
     * @param $mechanism
     * @return MechanismInterface
     */
    public static function get($mechanism, Jid $jid, $password)
    {
        if (isset(self::$_mechanisms[strtoupper($mechanism)])) return new self::$_mechanisms[strtoupper($mechanism)]($jid, $password);
        else return null;
    }
}