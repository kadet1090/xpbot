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

namespace XPBot\Config;


use Kadet\Utils\Property;
use Kadet\XmlSerializer\XmlDeserializer;
use Kadet\XmlSerializer\XmlSerializable;
use Kadet\XmlSerializer\XmlSerializer;

class ConfigModule implements \ArrayAccess, XmlSerializable, \Countable
{
    protected $_offsets = [];
    protected $_children = [];

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->_offsets[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->_offsets[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_offsets[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_offsets[$offset]);
    }

    public function __isset($var)
    {
        return isset($this->_children[$var]);
    }

    public function __unset($var)
    {
        if (isset($this->$var))
            unset($this->_children[$var]);
    }

    public function __get($var)
    {
        if (!isset($this->_children[$var]))
            $this->_children[$var] = new ConfigModule();

        return $this->_children[$var];
    }

    public function __set($var, $value)
    {
        $this->_children[$var] = $value;
    }

    public function toXml(\DOMElement $node, XmlSerializer $serializer)
    {
        foreach ($this->_offsets as $offset => $value)
            $node->setAttribute($offset, $value);

        foreach ($this->_children as $name => $child) {
            $element = $node->ownerDocument->createElement($name);
            $element = $serializer->serializeElement($child, $element);
            if ($element !== false)
                $node->appendChild($element);
        }

        return $node;
    }

    public static function fromXml(\DOMElement $node, XmlDeserializer $deserializer, &$result = null)
    {
        if (!is_object($result) || get_class($result) !== __CLASS__)
            $result = new ConfigModule();

        foreach ($node->attributes as $attribute) {
            if (strpos($attribute->nodeName, ':') === false)
                $result[$attribute->nodeName] = $attribute->nodeValue;
        }

        foreach ($node->childNodes as $child)
            $result->{$child->nodeName} = $deserializer->deserializeElement($child);

        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *       </p>
     *       <p>
     *       The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->_children) + count($this->_offsets);
    }
}