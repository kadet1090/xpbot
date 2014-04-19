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


use Kadet\XmlSerializer\XmlDeserializer;
use Kadet\XmlSerializer\XmlSerializable;
use Kadet\XmlSerializer\XmlSerializer;

class UsersConfig implements XmlSerializable, \ArrayAccess, \IteratorAggregate
{
    protected $_users = [];


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
        return isset($this->_users[$offset]);
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
        if (!isset($this[$offset]))
            $this[$offset] = new ConfigModule();

        return $this->_users[$offset];
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
        $this->_users[$offset] = $value;
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
        if (!isset($this[$offset])) return;

        unset($this->_users[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *       <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_users);
    }

    /**
     * Serializes object to XML node.
     *
     * @param \DOMElement   $node       Node to fill with serialized data.
     * @param XmlSerializer $serializer XML serializer instance.
     *
     * @return \DOMElement Serialized node.
     */
    public function toXml(\DOMElement $node, XmlSerializer $serializer)
    {
        foreach ($this->_users as $name => $data) {
            $element = $node->ownerDocument->createElement('user');
            $element->setAttribute('name', $name);
            $element = $serializer->serializeElement($data, $element);

            $node->appendChild($element);
        }

        return $node;
    }

    /**
     * Deserializes object from XML node.
     *
     * @param \DOMElement     $node         Node to deserialize.
     * @param XmlDeserializer $deserializer Xml Deserializer instance.
     *
     * @return mixed Deserialized object.
     */
    public static function fromXml(\DOMElement $node, XmlDeserializer $deserializer)
    {
        $result = new UsersConfig();

        $users = $node->getElementsByTagName('user');
        foreach ($users as $user) {
            if (!($user instanceof \DOMElement)) continue;

            $result->_users[$user->getAttribute('name')] = $deserializer->deserializeElement($user);
        }

        return $result;
    }
}