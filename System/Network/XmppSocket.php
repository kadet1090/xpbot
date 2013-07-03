<?php
namespace XPBot\System\Network;

use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Event;
use XPBot\System\Utils\Logger;
use XPBot\System\Utils\Timer;

abstract class XmppSocket extends BaseSocket
{
    public $onPacket;

    private $_waiting = array();
    protected $_features;
    protected $_stream;

    /**
     * @param $address
     * @param $port
     * @param int $timeout
     */
    public function __construct($address, $port = 5222, $timeout = 30)
    {
        parent::__construct($address, $port, $timeout);

        $this->onPacket       = new Event();
        $this->keepAliveTimer = new Timer(15, array($this, 'keepAliveTick'));
        $this->keepAliveTimer->stop(); // We don't want to run this before connection is finalized.
        $this->onPacket->add(new Delegate(array($this, '_onPacket')));
    }

    public function read()
    {
        $result = '';
        do {
            $content = stream_get_contents($this->_socket);
            $result .= $content;
        } while (!preg_match("/('\/|\"\/|iq|ge|ce|am|es|se|ss|ge|re|.'|.\")>$/", substr($result, -3)) && !empty($result));
        if(!empty($result)) Logger::debug($result);
        $this->_parse(trim($result));
    }

    /**
     * @param string $xml
     */
    private function _parse($xml)
    {
        $packets = preg_split("/<(\/stream:stream|stream|iq|presence|message|proceed|failure|challenge|response|success)/", $xml, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1, $c = count($packets); $i < $c; $i += 2) {
            $xml = "<" . $packets[$i] . $packets[($i + 1)];

            if (strpos($xml, '<stream:stream') !== false) $xml .= '</stream:stream>';
            $this->onPacket->run(@simplexml_load_string($xml));
        }
    }

    /**
     * @param string $type
     * @param int $id
     * @param Delegate $delegate
     */
    public function wait($type, $id, Delegate $delegate)
    {
        $this->_waiting[] = array(
            'tag'      => $type,
            'id'       => $id,
            'delegate' => $delegate
        );
    }

    /**
     * @param \SimpleXMLElement $packet
     */
    public function _onPacket(\SimpleXMLElement $packet)
    {
        if($packet->getName() == 'features')
            $this->_features = $packet;
        elseif($packet->getName() == 'stream')
            $this->_stream = $packet;

        foreach ($this->_waiting as &$wait) {
            if (
                (empty($wait['tag']) || $packet->getName() == $wait['tag']) &&
                (empty($wait['id']) || $packet['id'] == $wait['id'])
            ) {
                $wait['delegate']->run($packet);
            }
        }
    }

    public function keepAliveTick()
    {
    }
}