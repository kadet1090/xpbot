<?php
namespace XPBot\System\Xmpp;

use XPBot\System\Network\XmppSocket;
use XPBot\System\Sasl\SaslFactory;
use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Event;
use XPBot\System\Utils\Timer;
use XPBot\System\Utils\XmlBranch;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\Stanza\Message;
use XPBot\System\Xmpp\Stanza\Presence;
use XPBot\System\Xmpp\Stanza\Stanza;

/**
 * XmppClient class provides basic XMPP/Jabber functionality.
 *
 * @package XPBot\System\Xmpp
 * @author Kadet <kadet1090@gmail.com>
 */
class XmppClient extends XmppSocket
{
    # events
    /**
     * Event fired when authorization process ends.
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
     * @param Jid    $jid      Clients JID
     * @param string $password Account Password
     * @param int    $port     Server port (default 5222)
     * @param int    $timeout  Clients timeout in seconds (default 30)
     */
    public function __construct(Jid $jid, $password, $port = 5222, $timeout = 30)
    {
        parent::__construct($jid->server, $port, $timeout);
        $this->jid      = $jid;
        $this->password = $password;
        $this->onConnect->add(array($this, '_onConnect'));

        $this->onAuth       = new Event();
        $this->onStreamOpen = new Event();
        $this->onReady      = new Event();
        $this->onTick       = new Event();
        $this->onPresence   = new Event();
        $this->onMessage    = new Event();
        $this->onIq         = new Event();
        $this->onJoin       = new Event();
        $this->onLeave      = new Event();

        $this->onAuth->add(array($this, '_onAuth'));
        $this->onStreamOpen->add(array($this, '_onStreamOpen'));
        $this->onReady->add(array($this, '_onReady'));
        $this->onPresence->add(array($this, '_onPresence'));
        $this->onMessage->add(array($this, '_onMessage'));
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @ignore
     */
    public function _onConnect()
    {
        $this->startStream();
        $this->wait('features', '', array($this->onStreamOpen, 'run'));
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
        $this->write(XmlBranch::XML . "\n" . str_replace('/>', '>', $stream->asXml()));
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @ignore
     */
    public function _onStreamOpen()
    {
        if(isset($this->_features->mechanisms)) $this->auth();
    }

    /**
     * Authorizes client on server using SASL
     *
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
     * @param Stanza $result
     * @throws \RuntimeException
     *
     * @ignore
     */
    public function _onAuth($result)
    {
        if($result->xml->getName() == 'success') {
            $this->startStream();
            $this->_bind();
        }
        else
            throw new \RuntimeException('Authorization failed.');
    }

    /**
     * Binds resource.
     *
     * @ignore
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
        $this->wait('iq', $id, array($this, '_bindResult'));
    }

    /**
     * Resource binding result.
     * @param $packet
     * @throws \RuntimeException
     *
     * @ignore
     */
    public function _bindResult($packet) {
        if($packet['type'] == 'result') {
            $iq = new xmlBranch("iq");
            $iq->addAttribute("type", "set");
            $iq->addAttribute("id", uniqid('sess_'));
            $iq->addChild(new xmlBranch("session"))->addAttribute("xmlns", "urn:ietf:params:xml:ns:xmpp-session");
            $this->write($iq->asXml());
            $this->isReady = true;
            $this->onReady->run();
        }
        else
            throw new \RuntimeException('Resource binding error.');
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @ignore
     */
    public function _onReady()
    {
        $this->keepAliveTimer->start();
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @ignore
     */
    public function keepAliveTick()
    {
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid->__toString());
        $xml->addAttribute("to", $this->jid->server);
        $xml->addAttribute("id", uniqid('ping_'));
        $xml->addAttribute("type", "get");
        $xml->addChild(new xmlBranch("ping"))->addAttribute("xmlns", "urn:xmpp:ping");

        $this->write($xml->asXml());
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param \SimpleXMLElement $packet
     *
     * @ignore
     */
    public function _onPacket(\SimpleXMLElement $packet)
    {
        parent::_onPacket($packet);

        $stanza = Stanza::factory($this, $packet);
        switch ($packet->getName()) {
            case 'presence':
                $this->onPresence->run($stanza);
                break;
            case 'iq':
                $this->onIq->run($stanza);
                break;
            case 'message':
                $this->onMessage->run($stanza);
                break;

            # SASL
            case 'success':
            case 'failure':
                $this->onAuth->run($stanza);
                break;
        }
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param Presence $packet
     *
     * @ignore
     */
    public function _onPresence(Presence $packet)
    {
        $channelJid = $packet->from->bare();
        $jid        = new Jid($channelJid);

        if(!$jid->isChannel()) return;

        if ($packet->type != 'unavailable') {
            $user = $this->rooms[$channelJid]->addUser(User::fromPresence($packet, $this));
            if(
                $user->jid->bare() == $this->jid->bare() ||
                $this->rooms[$channelJid]->nick == $packet->from->resource
            ) $user->self = true;

            $this->onJoin->run($this->rooms[$channelJid], $user, $this->rooms[$channelJid]->subject === false);
        } else {
            $user = $this->rooms[$channelJid]->users[$packet->from->resource];
            $this->onLeave->run($this->rooms[$channelJid], $user);
            $this->rooms[$channelJid]->removeUser($user);
        }
    }

    /**
     * Should be private, but... php sucks!
     * DO NOT RUN IT, TRUST ME.
     *
     * @param Message $packet
     *
     * @ignore
     */
    public function _onMessage(Message $packet)
    {
        if ($packet->type != 'groupchat' || !isset($this->rooms[$packet->from->bare()])) return;

        if (isset($packet->subject))
            $this->rooms[$packet->from->bare()]->subject = $packet->subject;
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
     *
     * @param bool $blocking If set to true client is blocked before any packet came.
     */
    public function connect($blocking = false)
    {
        parent::connect($blocking);
    }

    /**
     * Gets user data by given jid.
     *
     * @param Jid $user Users jid
     *
     * @return User|null Users data.
     */
    public function getUserByJid(Jid $user)
    {
        if (!$user->fromChannel()) return null;
        return $this->rooms[$user->bare()]->users[$user->resource];
    }

    /**
     * Sends message to specified jid.
     *
     * You could use it to send message to groupchat, but it is highly not recommended.
     *
     * @param Jid    $jid     Receiver jid
     * @param string $message Message content
     * @param string $type    Message type: char or groupchat.
     */
    public function message(Jid $jid, $message, $type = 'chat')
    {
        $msg = new XmlBranch('message');
        $msg->addAttribute('from', $this->jid->__toString())
            ->addAttribute('to', $jid->__toString())
            ->addAttribute('type', $type);
        $msg->addChild(new XmlBranch('body'))->setContent($message);
        $this->write($msg->asXml());
    }

    /**
     * Changes client status on server.
     *
     * @param string $show   New show status for client, one of these:
     *                       chat, available, away, xa, dnd, unavailable.
     *                       (default available)
     * @param string $status Additional text status.
     */
    public function presence($show = "available", $status = "")
    {
        $xml = new xmlBranch("presence");
        $xml->addAttribute("from", $this->jid->__toString())
            ->addAttribute("id", uniqid());
        $xml->addChild(new xmlBranch("show"))->setContent($show);
        $xml->addChild(new xmlBranch("status"))->setContent($status);
        $xml->addChild(new xmlBranch("priority"))->setContent(50);

        $this->write($xml->asXml());
    }

    /**
     * Checks client version.
     *
     * @param Jid      $jid      Users jid.
     * @param callable $delegate Delegate to be executed after proper packet came.
     *                           Delegate takes one argument (packet) of type SimpleXMLElement.
     */
    public function version(Jid $jid, callable $delegate)
    {
        $id  = uniqid('osversion_');
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid)
            ->addAttribute("to", $jid)
            ->addAttribute("type", "get")
            ->addAttribute("id", $id);

        $xml->addChild(new xmlBranch("query"))->addAttribute("xmlns", "jabber:iq:version");
        $this->write($xml->asXml());

        $this->wait('iq', $id, $delegate);
    }

    /**
     * Pings user.
     * @param Jid      $jid      User jid.
     * @param callable $delegate Delegate to be executed after proper packet came.
     *                           Delegate takes one argument (packet) of type SimpleXMLElement.
     */
    public function ping(Jid $jid, callable $delegate)
    {
        $id  = uniqid('ping_');
        $xml = new xmlBranch("iq");
        $xml->addAttribute("from", $this->jid)
            ->addAttribute("to", $jid)
            ->addAttribute("type", "get")
            ->addAttribute("id", $id);

        $xml->addChild(new xmlBranch("ping"))->addAttribute("xmlns", "urn:xmpp:ping");
        $this->write($xml->asXml());

        $this->wait('iq', $id, $delegate);
    }

    /**
     * Joins to the room.
     *
     * @param Jid    $room Room to join, full jid.
     * @param string $nick Nick on room.
     *
     * @return Room Room data.
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
        $this->write($xml->asXml());

        return $this->rooms[$room->__toString()] = new Room($this, $room, $nick);
    }

    /**
     * Leaves room.
     *
     * @param Jid $room Jid of room to leave.
     *
     * @internal Plugins should use Room::leave() instead of that.
     *
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
        $this->write($xml->asXml());

        unset($this->rooms[$room->bare()]);
    }

    /**
     * Changes user role on room.
     *
     * @param Jid    $room   Jid of room.
     * @param string $nick   Nick of user.
     * @param string $role   New users role.
     *                       visitor, none, participant or moderator.
     * @param string $reason Reason of changing role. (default empty)
     *
     * @internal Plugins should use Room::role() instead of that.
     *
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

        $this->write($xml->asXml());
    }

    /**
     * Changes user affiliation.
     *
     * @param Jid    $room        Jid of room.
     * @param Jid    $user        Users Jid.
     * @param string $affiliation New affiliation for user.
     *                            none, outcast, member, admin, owner
     * @param string $reason      Reason of changing user affiliation.
     *
     * @internal Plugins should use Room::affiliation() instead of that.
     *
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

        $this->write($xml->asXml());
    }

    /**
     * Sets room (or conversation) subject.
     *
     * @param Jid    $jid     Jid to send subject msg.
     * @param string $subject New subject.
     *
     * @internal Plugins should use Room::subject() instead of that.
     */
    public function setSubject(Jid $jid, $subject) {
        $msg = new XmlBranch('message');
        $msg->addAttribute('from', $this->jid->__toString())
            ->addAttribute('to', $jid->__toString())
            ->addAttribute('type', $jid->isChannel() ? 'groupchat' : 'chat');
        $msg->addChild(new XmlBranch('subject'))->setContent($subject);
        $this->write($msg->asXml());
    }

    /**
     * Gets user affiliation list.
     *
     * @param Jid      $room        Jid of room to query.
     * @param string   $affiliation Affiliation type.
     * @param callable $delegate    Delegate to run after proper packet came.
     *                              Delegate takes one argument (packet) of type SimpleXMLElement.
     *
     * @internal Plugins should use Room::affiliationList() instead of that.
     *
     * @throws \InvalidArgumentException
     */
    public function affiliationList(Jid $room, $affiliation, callable $delegate) {
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
        $this->write($xml->asXml());

        $this->wait('iq', $id, $delegate);
    }
}