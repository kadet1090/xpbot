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
use Traversable;

class AliasConfig implements
    XmlSerializable,
    \ArrayAccess,
    \IteratorAggregate
{
    public $aliases = [];


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
        return isset($this->aliases[$offset]);
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
        if (!isset($this[$offset])) return $offset;

        return $this->aliases[$offset];
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
        $this->aliases[$offset] = $value;
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

        unset($this->aliases[$offset]);
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
        return new \ArrayIterator($this->aliases);
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
        foreach ($this->aliases as $from => $to) {
            $element = $node->ownerDocument->createElement('alias');
            $element->setAttribute('from', $from);
            $element->setAttribute('to', $to);
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
        $result = new AliasConfig();

        $aliases = $node->getElementsByTagName('alias');
        foreach ($aliases as $alias) {
            if (!($alias instanceof \DOMElement)) continue;

            $result[$alias->getAttribute('from')] = $alias->getAttribute('to');
        }

        return $result;
    }
}