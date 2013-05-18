<?php

namespace XPBot\System\Xmpp;

use XPBot\System\Utils\Event;
use XPBot\System\Xmpp\Jid;

class Room
{
    /**
     * Rooms client.
     * @var XmppClient
     */
    private $_client;

    /**
     * Rooms jid
     * @var \XPBot\System\Xmpp\Jid
     */
    public $jid;

    /**
     * Stores data of room users accessed by nick.
     * @var array[string]User
     */
    public $users = array();

    /**
     * Rooms subject
     * @var string|bool
     */
    public $subject = false;

    /**
     * Stores room configuration with additional client data (ie. join time)
     * @var array
     */
    public $configuration = array();

    protected static $config;

    /**
     * @param XmppClient $client
     * @param Jid $jid
     */
    public function __construct(XmppClient $client, Jid $jid)
    {
        $this->_client          = $client;
        $this->jid              = $jid;

        if (empty(self::$config))
            self::$config = simplexml_load_file('./Config/Rooms.xml');

        $this->configuration = self::$config->xpath("/rooms/room[@jid='{$this->jid->bare()}']");

        if (empty($this->configuration)) {
            self::$config->addChild('room');
            self::$config->room[count(self::$config->room) - 1]->addAttribute('jid', $this->jid->bare());
            $this->configuration = self::$config->room[count(self::$config->room) - 1];
        } else {
            $this->configuration = $this->configuration[0];
        }

        $this->configuration->jointime = time();

        self::$config->saveXML('./Config/Rooms.xml');
    }

    /**
     * Sends message to channel.
     * @param string $content
     */
    public function message($content)
    {
        $this->_client->message($this->jid, $content, 'groupchat');
    }

    /**
     * Kicks out specified user from channel.
     * @param string $nick
     */
    public function kick($nick)
    {
        $this->role($nick, 'none');
    }

    /**
     * Changes specified user role on channel.
     * @param string $nick
     * @param string $role Must be one of: visitor (no voice), none (aka kick), participant (standard role), moderator (can kick out users)
     */
    public function role($nick, $role)
    {
        if (!isset($this->users[$nick])) return; // Exception maybe?
        $this->_client->role($this->jid, $nick, $role);
    }

    /**
     * Bans user on channel.
     * @param Jid|string $who
     * @param string $reason
     */
    public function ban($who, $reason = '')
    {
        $this->affiliate($who, 'outcast', $reason);
    }

    /**
     * Unbans user on channel.
     * @param Jid|string $who
     * @param string $reason
     */
    public function unban($who, $reason = '')
    {
        $this->affiliate($who, 'none', $reason);
    }

    /**
     * Changes user affiliation on channel.
     * @param Jid|string $who
     * @param string $affiliation Must be one of: owner (channels god), admin, outcast (aka ban), member (vip, or something), none (standard)
     * @param string $reason
     * @throws \InvalidArgumentException
     */
    public function affiliate($who, $affiliation, $reason = '')
    {
        if (!($who instanceof Jid)) {
            if (!isset($this->users[$who])) throw new \InvalidArgumentException('who');
            $who = $this->users[$who]->jid;
        }

        $this->_client->affiliate($this->jid, $who, $affiliation, $reason);
    }

    /**
     * Gets out of the room.
     */
    public function leave()
    {
        $this->_client->leave($this->jid);
    }

    /**
     * Adds user to the room.
     * @param User $user
     * @return \XPBot\System\Xmpp\User
     */
    public function addUser(User $user)
    {
        $user->room = $this;

        return $this->users[$user->nick] = $user;
    }

    /**
     * Removes user from the room.
     * @param User $user
     */
    public function removeUser(User $user)
    {
        unset($this->users[$user->nick]);
    }

    /**
     * Sets room subject.
     * @param $subject
     */
    public function setSubject($subject)
    {
        $this->_client->setSubject($this->jid, $subject);
    }

    /**
     * Saves rooms configuration to file.
     */
    public static function save()
    {
        self::$config->asXML('./Config/Rooms.xml');
    }
}