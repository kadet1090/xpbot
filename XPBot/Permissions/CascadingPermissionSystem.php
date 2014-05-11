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

class CascadingPermissionSystem implements XmlSerializable, PermissionSystemInterface
{
    /**
     * @var PermissionSystemInterface[]
     */
    protected static $_sets = [];

    /**
     * @var array
     */
    protected $_inherits = [
        true  => [],
        false => []
    ];

    protected $_perms = [];
    protected $_name = null;

    public function __construct($name = null)
    {
        $this->_name = $name;
        if ($this->_name != null)
            self::$_sets[$this->_name] = $this;
    }

    /** {@inheritdoc} */
    public function toXml(\DOMElement $node, XmlSerializer $serializer)
    {
        if (empty($this->_perms)) return false;

        if (isset($this->_name))
            $node->setAttribute('name', $this->_name);

        foreach ($this->_inherits[false] as $inherit) {
            $element = $node->ownerDocument->createElement('inherit');
            if ($inherit instanceof CascadingPermissionSystem)
                $element->setAttribute('name', $inherit->_name);
            else
                $element = $serializer->serializeElement($inherit, $element);

            $node->appendChild($element);
        }

        foreach ($this->_perms as $name => $access) {
            $element = $node->ownerDocument->createElement($access ? 'grant' : 'revoke');
            $element->setAttribute('permission', $name);
            $node->appendChild($element);
        }

        return $node;
    }

    /** {@inheritdoc} */
    public static function fromXml(\DOMElement $node, XmlDeserializer $deserializer)
    {
        if ($node->hasAttribute('name')) {
            if (isset(self::$_sets[$node->getAttribute('name')]))
                $permissions = self::$_sets[$node->getAttribute('name')];
            else
                $permissions = new CascadingPermissionSystem($node->getAttribute('name'));
        } else
            $permissions = new CascadingPermissionSystem();

        foreach ($node->getElementsByTagName('inherit') as $inherit) {
            if ($inherit->hasAttribute('from'))
                $permissions->inherit(self::get($inherit->getAttribute('from')));
        }

        foreach ($node->getElementsByTagName('grant') as $grant) {
            if ($grant->hasAttribute('permission'))
                $permissions->grant($grant->getAttribute('permission'));
        }

        foreach ($node->getElementsByTagName('revoke') as $revoke) {
            if ($revoke->hasAttribute('permission'))
                $permissions->revoke($revoke->getAttribute('permission'));
        }

        return $permissions;
    }

    public function inherit(PermissionSystemInterface $parent, $runtime = false)
    {
        $this->_inherits[(bool)$runtime][] = $parent;
    }

    public function grant($permission)
    {
        $this->_perms = array_filter_keys($this->_perms, function ($perm) use ($permission) {
            return !fnmatch($permission, $perm);
        });

        $this->_perms[$permission] = true;
    }

    public function revoke($permission)
    {
        $this->_perms = array_filter_keys($this->_perms, function ($perm) use ($permission) {
            return !fnmatch($permission, $perm);
        });

        $this->_perms[$permission] = false;
    }

    public function remove($permission)
    {
        unset($this->_perms[$permission]);
    }

    public function has($permission)
    {
        $result = arrayGetMatching($this->_perms, $permission);
        if ($result !== null) return $result;

        foreach (array_merge($this->_inherits[true], $this->_inherits[false]) as $inherited)
            if ($inherited->has($permission)) return true;

        return null;
    }

    public function get()
    {
        $perms = [];
        /** @var PermissionSystemInterface $inherited */
        foreach (array_merge($this->_inherits[true], $this->_inherits[false]) as $inherited)
            $perms[] = $inherited->get();

        $perms = call_user_func_array('array_merge', $perms);
        $perms = array_filter_keys($perms, function ($perm) {
            foreach (array_keys($this->_perms) as $current)
                if (fnmatch($current, $perm)) return false;

            return true;
        });

        return $perms + $this->_perms;
    }

    public static function getSet($name)
    {
        return self::$_sets[$name];
    }

    public static function add(CascadingPermissionSystem $permissions)
    {
        self::$_sets[$permissions->_name] = $permissions;
    }
}