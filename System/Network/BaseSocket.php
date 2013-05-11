<?php
namespace XPBot\System\Network;
use XPBot\System\Utils\Event;

/**
 * Class BaseSocket
 * @package XPBot\System\Network
 */
abstract class BaseSocket
{
    /**
     * @var string
     */
    protected $_address;

    /**
     * @var int
     */
    protected $_port;

    /**
     * @var int
     */
    protected $_timeout;

    # events
    /**
     * @var \XPBot\System\Utils\Event
     */
    public $onConnect;

    /**
     * @var \XPBot\System\Utils\Event
     */
    public $onDisconnect;

    /**
     * @var \XPBot\System\Utils\Event
     */
    public $onError;

    /**
     * @var bool
     */
    public $isConnected;

    /**
     * @var Resource
     */
    protected $_socket;

    /**
     * @var array
     */
    protected $_error = array(
        'string' => '',
        'code'   => 0
    );

    /**
     * @param $address
     * @param $port
     * @param int $timeout
     */
    public function __construct($address, $port, $timeout = 30)
    {
        $this->_address = $address;
        $this->_port    = $port;
        $this->_timeout = $timeout;

        $this->onConnect    = new Event();
        $this->onDisconnect = new Event();
        $this->onError      = new Event(array('int', 'string'));
    }

    /**
     * @param bool $blocking
     */
    public function connect($blocking = true)
    {
        $this->_socket = stream_socket_client("tcp://{$this->_address}:{$this->_port}", $this->_error['code'], $this->_error['string'], $this->_timeout);
        if (!$this->_socket)
            $this->raiseError();

        stream_set_blocking($this->_socket, $blocking);

        $this->isConnected = true;
        $this->onConnect->run();
    }

    /**
     * @param $text string
     */
    public function write($text)
    {
        if (!fwrite($this->_socket, $text))
            $this->raiseError();
    }

    /**
     * @return string
     */
    public function read()
    {
        $result = '';
        do {
            $content = stream_get_contents($this->socket);
            $result .= $content;
        } while (!empty($content) && !empty($result));

        return trim($result);
    }

    /**
     * @throws NetworkException
     */
    private function raiseError()
    {
        $this->onError->run((int)$this->_error['code'], $this->_error['string']);
        throw new NetworkException($this->_error['string'], $this->_error['code']);
    }
}