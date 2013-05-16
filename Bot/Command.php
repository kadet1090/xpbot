<?php

namespace XPBot\Bot;

use XPBot\System\Xmpp\User;
use XPBot\System\Xmpp\User;
use XPBot\System\Xmpp\XmppClient;
use XPBot\System\Xmpp\XmppClient;

class CommandException extends \Exception
{
    protected $_consoleMessage;

    public function __construct($cmdMsg, $message = '', $code = 0, $previous = null)
    {
        $this->_consoleMessage = $cmdMsg;
        parent::__construct($message, $code, $previous);
    }

    public function getConsoleMessage()
    {
        return $this->_consoleMessage;
    }
}

/**
 * Class Command
 * @package XPBot\Bot
 */
abstract class Command
{
    const PERMISSION = 2;
    const CHAT       = true;
    const GROUPCHAT  = true;

    /**
     * Jabber client.
     * @var \XPBot\System\Xmpp\XmppClient
     */
    protected $_bot;

    /**
     * Packet of message that contains command
     * @var \SimpleXMLElement
     */
    protected $_packet;

    /**
     * Commands language
     * @var string
     */
    protected $_lang;

    /**
     * User who launched this command.
     * @var User
     */
    protected $_author;

    /**
     * Command type, chat or groupchat.
     * @var User
     */
    protected $_type;

    /**
     * @param Bot $client Jabber client.
     * @param User $author User who executed this command.
     * @param string $lang Commands language.
     * @param \SimpleXMLElement $packet Commands packet.
     */
    public function __construct(Bot $client, $author, $lang, $packet)
    {
        $this->_bot    = $client;
        $this->_packet = $packet;
        $this->_lang   = $lang;
        $this->_author = $author;
        $this->_type   = $packet['type'];
    }

    /**
     * Executes command in XMPP.
     * @param $args
     * @throws commandException
     * @return string
     */
    public function execute($args)
    {
        throw new commandException('This command is not assumed to be performed in XMPP');
    }

    /**
     * Gets help string of command.
     * @param string $lang
     * @return string
     */
    public static function getHelp($lang)
    {
        return \__('help', $lang, get_called_class());
    }

    /**
     * Gets short help string of command.
     * @param string $lang
     * @return string
     */
    public static function getShortHelp($lang)
    {
        return \__('shortHelp', $lang, get_called_class());
    }

    /**
     * Checks if user has permission to execute command.
     * @param User $user
     * @return bool
     */
    public static function hasPermission(User $user)
    {
        $class = get_called_class();

        return $user->permission >= $class::PERMISSION;
    }
}