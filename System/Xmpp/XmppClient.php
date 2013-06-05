<?php
namespace XPBot\System\Xmpp;

use XPBot\System\Network\XmppSocket;
use XPBot\System\Sasl\SaslFactory;
use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Event;
use XPBot\System\Utils\Timer;
use XPBot\System\Utils\XmlBranch;
use XPBot\System\Xmpp\Jid;

/**
 * XmppClient, uberclass.
 * @package XPBot\System\Xmpp
 * @author Kadet <kadet1090@gmai.com>
 */
class XmppClient extends XmppSocket
{
    # events
    /**
     * Event fired when client is authed (or not).
     * Takes one argument of type SimpleXMLElement.
     * @var \XPBot\System\Utils\Event
     */
    public $onAuth;

    /**
     * Event fired when stream is opened and ready to accept data.
     * Takes no arguments.
     * @var \XPBot\System\Utils\Event
     */
    public $onStreamOpen;

    /**
     * Event fired when bot is ready (stream is opened, client is successfully authed and session is registered)
     * Takes no arguments.
     * @var \XPBot\System\Utils\Event
     */
    public $onReady;

    /**
     * Event fired on every loop tick.
     * Takes no arguments.
     * @var \XPBot\System\Utils\Event
     */
    public $onTick;

    /**
     * Event fired when presence packet came.
     * Takes one argument of type SimpleXMLElement.
     * @var \XPBot\System\Utils\Event
     */
    public $onPresence;

    /**
     * Event fired when iq packet came.
     * Takes one argument of type SimpleXMLElement.
     * @var \XPBot\System\Utils\Event
     */
    public $onIq;

    /**
     * Event fired when message packet came.
     * Takes one argument of type SimpleXMLElement.
     * @var \XPBot\System\Utils\Event
     */
    public $onMessage;

    /**
     * Event fired when user joins to room.
     * Takes two arguments:
     * Room $room
     * User $user
     * bool $afterBroadcast
     * @var \XPBot\System\Utils\Event
     */
    public $onJoin;

    /**
     * Event fired when user leaves room.
     * Takes two arguments:
     * Room $room
     * User $user
     */
    public $onLeave;

    /**
     * Jabber account Jid
     * @var Jid
     */
    protected $jid;

    /**
     * Password to jabber account.
     * @var string
     */
    protected $password;

    /**
     * If client is connected and authed is true.
     * @var bool
     */
    public $isReady;

    /**
     * Rooms list.
     * @var Room[]
     */
    public $rooms = array();

    /**
     * @param Jid $jid
     * @param string $password
     * @param int $port
     * @param int $timeout
     */
    public function __construct(Jid $jid, $password, $port = 5222, $timeout = 30)
    {
        parent::__construct($jid->server, $port, $timeout);
        $this->jid      = $jid;
        $this->password = $password;
        $this->onConnect->add(new Delegate(array($this, '_onConnect')));

        $this->onAuth       = new Event();
        $this->onStreamOpen = new Event();
        $this->onReady      = new Event();
        $this->onTick       = new Event();
        $this->onPresence   = new Event();
        $this->onMessage    = new Event();
        $this->onIq         = new Event();
        $this->onJoin       = new Event();
        $this->onLeave      = new Event();

        $this->onAuth->add(new Delegate(array($this, '_onAuth')));
        $this->onStreamOpen->add(new Delegate(array($this, '_onStreamOpen')));
        $this->onReady->add(new Delegate(array($this, '_onReady')));
        $this->onPresence->add(new Delegate(array($this, '_onPresence')));
        $this->onMessage->add(new Delegate(array($this, '_onMessage')));
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     */
    public function _onConnect()
    {
        $this->startStream();
        $this->wait('features', '', new Delegate(array($this->onStreamOpen, 'run')));
        $this->work();
    }

    /**
     * Starts stream
     */
    private function startStream() {
        $stream = new XmlBranch('stream:stream');
        $stream
            ->addAttribute('to', $this->jid->server)
            ->addAttribute('xmlns', 'jabber:client')
            ->addAttribute('version', '1.0')
            ->addAttribute('xmlns:stream', 'http://etherx.jabber.org/streams');
        $this->write(XmlBranch::XML . "\n" . str_replace('/>', '>', $stream->asXML()));
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     */
    public function _onStreamOpen()
    {
        if(isset($this->_features->mechanisms)) $this->auth();
    }

    /**
     * Auths client on server using SASL
     * @throws \RuntimeException
     */
    private function auth() {

        $xml = new XmlBranch('auth');
        $xml->addAttribute('xmlns', 'urn:ietf:params:xml:ns:xmpp-sasl');

        $mechanism = null;
        foreach($this->_features->mechanisms->mechanism as $current) {
            if($mechanism = SaslFactory::get((string)$current))
                break;
        }

        if(!$mechanism)
            throw new \RuntimeException('This client is not supporting any of server auth mechanisms.');

        $xml->addAttribute('mechanism', $current);
        $xml->setContent($mechanism->get($this->jid, $this->password));

        $this->write($xml);
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param \SimpleXMLElement $result
     * @throws \RuntimeException
     */
    public function _onAuth($result)
    {
        if($result->getName() == 'success') {
            $this->startStream();
            $this->_bind();
        }
        else
            throw new \RuntimeException('Authorization failed.');
    }

    /**
     * Binds resource.
     */
    private function _bind() {
        $xml = new XmlBranch('iq');
        $id = uniqid('bind_');
        $xml->addAttribute('id', $id)
            ->addAttribute('type', 'set');

        $xml->addChild(new XmlBranch('bind'))
            ->addAttribute('xmlns', 'urn:ietf:params:xml:ns:xmpp-bind')
            ->addChild(new XmlBranch('resource'))
            ->setContent($this->jid->resource);

        $this->write($xml);
        $this->wait('iq', $id, new Delegate(array($this, '_bindResult')));
    }

    /**
     * Resource binding result.
     * @param $packet
     * @throws \RuntimeException
     */
    public function _bindResult($packet) {
        if($packet['type'] == 'result') {
            $iq = new xmlBranch("iq");
            $iq->addAttribute("type", "set");
            $iq->addAttribute("id", uniqid('sess_'));
            $iq->addChild(new xmlBranch("session"))->addAttribute("xmlns", "urn:ietf:params:xml:ns:xmpp-session");
            $this->write($iq->asXML());
            $this->isReady = true;
            $this->onReady->run();
        }
        else
            throw new \RuntimeException('Resource binding error.');
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     */
    public function _onReady()
    {
        $this->keepAliveTimer->start();
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     */
    public function keepAliveTick()
    {
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid->__toString());
        $xml->addAttribute("to", $this->jid->server);
        $xml->addAttribute("id", uniqid('ping_'));
        $xml->addAttribute("type", "get");
        $xml->addChild(new xmlBranch("ping"))->addAttribute("xmlns", "urn:xmpp:ping");

        $this->write($xml->asXML());
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param \SimpleXMLElement $packet
     */
    public function _onPacket(\SimpleXMLElement $packet)
    {
        parent::_onPacket($packet);

        switch ($packet->getName()) {
            case 'presence':
                $this->onPresence->run($packet);
                break;
            case 'iq':
                $this->onIq->run($packet);
                break;
            case 'message':
                $this->onMessage->run($packet);
                break;

            # SASL
            case 'success':
            case 'failure':
                $this->onAuth->run($packet);
                break;
        }
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param \SimpleXMLElement $packet
     */
    public function _onPresence(\SimpleXMLElement $packet)
    {
        $channelJid = strstr($packet['from'], '/', true);
        $jid        = new Jid($channelJid);

        if (!$jid->isChannel() || !isset($this->rooms[$channelJid])) return;

        if ($packet['type'] != 'unavailable') {
            $user = $this->rooms[$channelJid]->addUser(User::fromPresence($packet, $this));
            $this->onJoin->run($this->rooms[$channelJid], $user, $this->rooms[$channelJid]->subject === false);
        } else {
            $user = $this->rooms[$channelJid]->users[substr(strstr($packet['from'], '/'), 1)];
            $this->onLeave->run($this->rooms[$channelJid], $user);
            $this->rooms[$channelJid]->removeUser($user);
        }
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param \SimpleXMLElement $packet
     */
    public function _onMessage(\SimpleXMLElement $packet)
    {
        $jid = new Jid($packet['from']);
        if ($packet['type'] != 'groupchat' || !isset($this->rooms[$jid->bare()])) return;

        if (isset($packet->subject))
            $this->rooms[$jid->bare()]->subject = $packet->subject;
    }

    /**
     * Starts bot reading loop.
     * @todo [PHP 5.5] write it using coroutines.
     */
    private function work()
    {
        while (true) {
            if ($this->isReady)
                $this->onTick->run();

            Timer::update();
            $this->read();
            usleep(100);
        }
    }

    /**
     * Connects client to the server.
     */
    public function connect($blocking = false)
    {
        parent::connect($blocking);
    }

    /**
     * @param Jid $user
     * @return User|null
     */
    public function getUserByJid(Jid $user)
    {
        if (!$user->fromChannel()) return null;

        return $this->rooms[$user->bare()]->users[$user->resource];
    }

    /**
     * Sends message to specified jid. You could use it to send message to groupchat, but it is highly not recommended.
     * @param Jid $jid
     * @param string $message
     * @param string $type chat or groupchat
     */
    public function message(Jid $jid, $message, $type = 'chat')
    {
        $msg = new XmlBranch('message');
        $msg->addAttribute('from', $this->jid->__toString())
            ->addAttribute('to', $jid->__toString())
            ->addAttribute('type', $type);
        $msg->addChild(new XmlBranch('body'))->setContent($message);
        $this->write($msg->asXML());
    }

    /**
     * Changes bot status on server.
     * @param string $show
     * @param string $status
     */
    public function presence($show = "available", $status = "")
    {
        $xml = new xmlBranch("presence");
        $xml->addAttribute("from", $this->jid->__toString())
            ->addAttribute("id", uniqid());
        $xml->addChild(new xmlBranch("show"))->setContent($show);
        $xml->addChild(new xmlBranch("status"))->setContent($status);
        $xml->addChild(new xmlBranch("priority"))->setContent(50);

        $this->write($xml->asXML());
    }

    /**
     * Checks client version.
     * @param Jid $jid user jid.
     * @param Delegate $delegate Delegate to be executed after proper packet came.
     */
    public function version(Jid $jid, Delegate $delegate)
    {
        $id  = uniqid('osversion_');
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid)
            ->addAttribute("to", $jid)
            ->addAttribute("type", "get")
            ->addAttribute("id", $id);

        $xml->addChild(new xmlBranch("query"))->addAttribute("xmlns", "jabber:iq:version");
        $this->write($xml->asXML());

        $this->wait('iq', $id, $delegate);
    }

    /**
     * Pings user.
     * @param Jid $jid user jid.
     * @param Delegate $delegate Delegate to be executed after proper packet came.
     */
    public function ping(Jid $jid, Delegate $delegate)
    {
        $id  = uniqid('ping_');
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid)
            ->addAttribute("to", $jid)
            ->addAttribute("type", "get")
            ->addAttribute("id", $id);

        $xml->addChild(new xmlBranch("ping"))->addAttribute("xmlns", "urn:xmpp:ping");
        $this->write($xml->asXML());

        $this->wait('iq', $id, $delegate);
    }

    /**
     * Joins to room.
     * @param Jid $room
     * @param $nick
     * @return Room
     * @throws \InvalidArgumentException
     */
    public function join(Jid $room, $nick)
    {
        if (!$room->isChannel()) throw new \InvalidArgumentException('room'); // YOU SHALL NOT PASS

        $xml = new xmlBranch("presence");
        $xml->addAttribute("from", $this->jid->__toString())
            ->addAttribute("to", $room->bare() . '/' . $nick)
            ->addAttribute("id", uniqid('mucjoin_'));
        $xml->addChild(new xmlBranch("x"))->addAttribute("xmlns", "http://jabber.org/protocol/muc");
        $this->write($xml->asXML());

        return $this->rooms[$room->__toString()] = new Room($this, $room);
    }

    /**
     * Leaves room.
     * @param Jid $room
     * @throws \InvalidArgumentException
     */
    public function leave(Jid $room)
    {
        if (!$room->isChannel() || !isset($this->rooms[$room->bare()])) throw new \InvalidArgumentException('room');

        $xml = new xmlBranch("presence");
        $xml->addAttribute("from", $this->jid->__toString())
            ->addAttribute("to", $room->bare())
            ->addAttribute("id", uniqid('mucout_'))
            ->addAttribute("type", 'unavailable');
        $xml->addChild(new xmlBranch("x"))->addAttribute("xmlns", "http://jabber.org/protocol/muc");
        $this->write($xml->asXML());

        unset($this->rooms[$room->bare()]);
    }

    /**
     * Changes user role.
     * @param Jid $room
     * @param $nick
     * @param $role
     * @param string $reason
     * @throws \InvalidArgumentException
     */
    public function role(Jid $room, $nick, $role, $reason = '')
    {
        if (!in_array($role, array('visitor', 'none', 'participant', 'moderator')))
            throw new \InvalidArgumentException('role');

        $xml = new xmlBranch("iq");
        $xml->addAttribute("type", "set")
            ->addAttribute("to", $room->__toString())
            ->addAttribute("id", uniqid('role_'));

        $xml->addChild(new xmlBranch("query"));
        $xml->query[0]->addAttribute("xmlns", "http://jabber.org/protocol/muc#admin");
        $xml->query[0]->addChild(new xmlBranch("item"));
        $xml->query[0]->item[0]->addAttribute("nick", $nick);
        $xml->query[0]->item[0]->addAttribute("role", $role);

        if (!empty($reason)) $xml->query[0]->item[0]->addChild(new xmlBranch("reason"))->setContent($reason);

        $this->write($xml->asXML());
    }

    /**
     * Changes user affiliation.
     * @param Jid $room
     * @param Jid $user
     * @param $affiliation
     * @param string $reason
     * @throws \InvalidArgumentException
     */
    public function affiliate(Jid $room, Jid $user, $affiliation, $reason = '')
    {
        if (!in_array($affiliation, array('none', 'outcast', 'member', 'admin', 'owner')))
            throw new \InvalidArgumentException('affiliation');

        $xml = new xmlBranch("iq");
        $xml->addAttribute("type", "set")
            ->addAttribute("to", $room->__toString())
            ->addAttribute("id", uniqid('affiliate_'));

        $xml->addChild(new xmlBranch("query"));
        $xml->query[0]->addAttribute("xmlns", "http://jabber.org/protocol/muc#admin");
        $xml->query[0]->addChild(new xmlBranch("item"));
        $xml->query[0]->item[0]->addAttribute("jid", $user->bare());
        $xml->query[0]->item[0]->addAttribute("affiliation", $affiliation);

        if (!empty($reason)) $xml->query[0]->item[0]->addChild(new xmlBranch("reason"))->setContent($reason);

        $this->write($xml->asXML());
    }

    /**
     * Sets room (or conversation) subject.
     * @param Jid $jid
     * @param $subject
     */
    public function setSubject(Jid $jid, $subject) {
        $msg = new XmlBranch('message');
        $msg->addAttribute('from', $this->jid->__toString())
            ->addAttribute('to', $jid->__toString())
            ->addAttribute('type', $jid->isChannel() ? 'groupchat' : 'chat');
        $msg->addChild(new XmlBranch('subject'))->setContent($subject);
        $this->write($msg->asXML());
    }

    /**
     * Gets user affiliation list.
     * @param Jid $room
     * @param $affiliation
     * @param Delegate $delegate
     * @throws \InvalidArgumentException
     */
    public function affiliationList(Jid $room, $affiliation, Delegate $delegate) {
        if (!in_array($affiliation, array('none', 'outcast', 'member', 'admin', 'owner')))
            throw new \InvalidArgumentException('affiliation');

        $xml = new xmlBranch("iq");
        $id = uniqid('affiliate_');
        $xml->addAttribute("type", "get")
            ->addAttribute('from', $this->jid->__toString())
            ->addAttribute("to", $room->__toString())
            ->addAttribute("id", $id);
        $xml->addChild(new xmlBranch("query"));
        $xml->query[0]->addAttribute("xmlns", "http://jabber.org/protocol/muc#admin");
        $xml->query[0]->addChild(new xmlBranch("item"));
        $xml->query[0]->item[0]->addAttribute("affiliation", $affiliation);
        $this->write($xml->asXML());

        $this->wait('iq', $id, $delegate);
    }
}