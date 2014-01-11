<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 11.01.14
 * Time: 12:11
 */

namespace XPBot\System\Xmpp\Stanza\Iq;

use XPBot\System\Utils\Property;

class Query
{
    use Property;

    public $xml;

    public function __construct(\SimpleXMLElement $xml)
    {
        $this->xml = $xml;
    }

    /**
     * @internal
     */
    public function _get_namespace()
    {
        preg_match('/xmlns=(?:"|\')(.*?)(?:"|\')/si', $this->xml->asXML(), $match);
        return $match[1];
    }

    public function _get($name)
    {
        return $this->xml->$name;
    }
} 