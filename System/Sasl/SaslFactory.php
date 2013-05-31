<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace XPBot\System\Sasl;


class SaslFactory {
    protected static $_mechanisms = array(
        'PLAIN' => 'XPBot\\System\\Sasl\\Plain'
    );

    /**
     * @param $mechanism
     * @return MechanismInterface
     */
    public static function get($mechanism) {
        if(isset(self::$_mechanisms[strtoupper($mechanism)])) return new self::$_mechanisms[strtoupper($mechanism)];
        else return null;
    }
}