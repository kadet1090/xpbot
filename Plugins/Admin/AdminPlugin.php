<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Admin;


use Kadet\Xmpp\Room;
use Kadet\Xmpp\User;
use Kadet\Xmpp\XmppClient;
use XPBot\Plugin;
use XPBot\Utils\Language;

class AdminPlugin extends Plugin
{
    public function load()
    {
        $this->_bot->registerCommand(__NAMESPACE__ . '\\Commands\\Affiliate', 'admin', 'affiliate');
        $this->_bot->registerCommand(__NAMESPACE__ . '\\Commands\\Ban', 'admin', 'ban');
        $this->_bot->registerCommand(__NAMESPACE__ . '\\Commands\\Kick', 'admin', 'kick');
        $this->_bot->registerCommand(__NAMESPACE__ . '\\Commands\\Role', 'admin', 'role');
        $this->_bot->registerCommand(__NAMESPACE__ . '\\Commands\\Subject', 'admin', 'subject');

        $this->_bot->onJoin->add(array($this, '_auto'));

        Language::loadDir(dirname(__FILE__) . '/Languages/');
    }

    public function unload()
    {
        $this->_bot->unregisterCommand('admin', 'affiliate');
        $this->_bot->unregisterCommand('admin', 'ban');
        $this->_bot->unregisterCommand('admin', 'kick');
        $this->_bot->unregisterCommand('admin', 'role');
        $this->_bot->unregisterCommand('admin', 'subject');

        $this->_bot->onJoin->remove(array($this, '_auto'));
    }

    public function _auto(XmppClient $client, Room $room, User $user, $broadcast)
    {
        if (
            !isset($this->_bot->config->rooms[$room->jid->bare()]->auto) ||
            !isset($this->_bot->config->rooms[$room->jid->bare()]->auto[$user->jid->bare()])
        ) return;

        $auto = $this->_bot->config->rooms[$room->jid->bare()]->auto[$user->jid->bare()];

        if (isset($auto->role)) $room->role($user->nick, $auto->role);
        if (isset($auto->affiliation)) $room->affiliate($user->nick, $auto->affiliation);
    }
}