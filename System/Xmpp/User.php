<?php
namespace XPBot\System\Xmpp;


class User
{
    public $nick;
    public $affiliation;
    public $role;
    public $jid;
    public $show;
    public $status;

    /**
     * Users chat room.
     * @var Room
     */
    public $room;

    /**
     * @var XmppClient
     */
    private $_client;

    public function __construct($client) {
        $this->_client = $client;
    }

    /**
     * Makes user object from presence packet.
     * @param \SimpleXMLElement $presence
     * @param XmppClient $client
     * @throws \InvalidArgumentException
     * @return User
     */
    public static function fromPresence(\SimpleXMLElement $presence, XmppClient $client)
    {
        if ($presence->getName() != 'presence') throw new \InvalidArgumentException('presence');

        $user              = new User($client);
        $user->nick        = (string)substr(strstr($presence['from'], '/'), 1);
        $user->affiliation = (string)self::_getAffiliation($presence);
        $user->role        = (string)self::_getRole($presence);
        $user->jid         = (string)self::_getJid($presence);
        $user->show        = (isset($presence->show) ? (string)$presence->show : 'available');
        $user->status      = (string)$presence->status;

        return $user;
    }

    /**
     * Helper, gets jid from packet.
     * @param $packet
     * @return Jid
     */
    private static function _getJid($packet)
    {
        $jid = $packet['from'];
        if (isset($packet->x->item['jid'])) $jid = $packet->x->item['jid'];
        elseif (isset($packet->x[0]->item['jid'])) $jid = $packet->x[0]->item['jid']; elseif (isset($packet->x[1]->item['jid'])) $jid = $packet->x[1]->item['jid'];

        return new Jid($jid);
    }

    /**
     * Helper, gets role from packet.
     * @param $packet
     * @return string
     */
    private static function _getRole($packet)
    {
        $role = 'participant';
        if (isset($packet->x->item['role'])) $role = $packet->x->item['role'];
        elseif (isset($packet->x[0]->item['role'])) $role = $packet->x[0]->item['role']; elseif (isset($packet->x[1]->item['role'])) $role = $packet->x[1]->item['role'];

        return $role;
    }

    /**
     * Helper, gets affiliation from packet.
     * @param $packet
     * @return string
     */
    private static function _getAffiliation($packet)
    {
        $aff = 'none';
        if (isset($packet->x->item['affiliation'])) $aff = $packet->x->item['affiliation'];
        elseif (isset($packet->x[0]->item['affiliation'])) $aff = $packet->x[0]->item['affiliation']; elseif (isset($packet->x[1]->item['affiliation'])) $aff = $packet->x[1]->item['affiliation'];

        return $aff;
    }

    public function roomJid() {
        if(!isset($this->room)) return $this->jid;

        return new Jid($this->room->jid->name, $this->room->jid->server, $this->nick);
    }

    public function privateMessage($content) {
        $this->_client->message($this->roomJid(), $content);
    }
}