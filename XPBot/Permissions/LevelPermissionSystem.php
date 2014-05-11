<?php
/**
 * Copyright (C) 2014, Some right reserved.
 *
 * @author  Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace XPBot\Permissions;

use Kadet\XmlSerializer\XmlDeserializer;
use Kadet\XmlSerializer\XmlSerializable;
use Kadet\XmlSerializer\XmlSerializer;

/**
 * Class LevelPermissionSystem
 *
 * @package XPBot\Permissions
 * @xml-tag level
 */
class LevelPermissionSystem implements XmlSerializable, PermissionSystemInterface
{
    /**
     * @var array
     */
    protected static $_perms = [];

    protected $_level = 2;

    public function __construct($level)
    {
        $this->_level = $level;
    }

    /** {@inheritdoc} */
    public function toXml(\DOMElement $node, XmlSerializer $serializer)
    {
        $node->setAttribute('level', $this->_level);

        return $node;
    }

    /** {@inheritdoc} */
    public static function fromXml(\DOMElement $node, XmlDeserializer $deserializer)
    {
        return new LevelPermissionSystem($node->getAttribute('level'));
    }

    public function grant($permission)
    {
        throw new \LogicException('You can\'t grant any additional permissions to this permission system.');
    }

    public function revoke($permission)
    {
        throw new \LogicException('You can\'t revoke any permissions in this permission system.');
    }

    public function has($permission)
    {
        $result = arrayGetMatching(self::$_perms, $permission);
        if ($result !== null)
            return $this->_level >= $result;

        return false;
    }

    public static function set($permission, $level)
    {
        self::$_perms[$permission] = $level;
    }

    public function get()
    {
        return array_map(function ($required) {
            return $this->_level >= $required;
        }, self::$_perms);
    }
}