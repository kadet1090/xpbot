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
use Traversable;

/**
 * Class SimpleConfig
 *
 * @package XPBot\Config
 * @xml-tag storage
 */
class SimpleConfig implements
    XmlSerializable,
    \IteratorAggregate,
    \Countable
{
    protected $_variables = [];

    public function set($variable, $namespace, $value)
    {
        if (!isset($this->_variables[$namespace]))
            $this->_variables[$namespace] = [];

        $this->_variables[$namespace][$variable] = $value;
    }

    public function get($variable, $namespace, $default = null)
    {
        if (!isset($this->_variables[$namespace][$variable]))
            return $default;

        return $this->_variables[$namespace][$variable];
    }

    public function remove($variable, $namespace)
    {
        if (isset($this->_variables[$namespace][$variable]))
            unset($this->_variables[$namespace][$variable]);
    }

    public function toXml(\DOMElement $node, XmlSerializer $serializer)
    {
        foreach ($this->_variables as $name => $vars) {
            if (empty($vars)) continue;

            $namespace = $node->ownerDocument->createElement('namespace');
            $namespace->setAttribute('name', $name);
            $node->appendChild($namespace);

            foreach ($vars as $vname => $var) {
                $element = $node->ownerDocument->createElement('var', $var);
                $element->setAttribute('name', $vname);
                $namespace->appendChild($element);
            }
        }

        return $node;
    }

    public static function fromXml(\DOMElement $node, XmlDeserializer $deserializer)
    {
        $result = new SimpleConfig();

        $namespaces = $node->getElementsByTagName('namespace');
        foreach ($namespaces as $namespace) {
            if (!($namespace instanceof \DOMElement)) continue;

            $ns                      = $namespace->getAttribute('name');
            $result->_variables[$ns] = [];
            $variables               = $namespace->getElementsByTagName('var');
            foreach ($variables as $var) {
                if (!($var instanceof \DOMElement)) continue;

                $result->_variables[$ns][$var->getAttribute('name')] = $var->nodeValue;
            }
        }

        return $result;
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
        return new \ArrayIterator($this->_variables);
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
        return count($this->_variables, COUNT_RECURSIVE);
    }
}