<?php

namespace XPBot;

use XPBot\Exceptions\CommandException;
use XPBot\Utils\Params;
use Kadet\Xmpp\Stanza\Message;
use Kadet\Xmpp\User;

/**
 * Abstract command class.
 *
 * Base of all commands.
 *
 * @package XPBot
 */
abstract class Command
{
    /**
     * Permission needed to run this command.
     */
    const PERMISSION = 2;

    /**
     * Set to true if command can be launched on chat mode.
     */
    const CHAT = true;

    /**
     * Set to true if command can be launched on chat mode.
     */
    const GROUPCHAT = true;

    /**
     * Set to true if reply should be sent on private channel.
     */
    const PRIVREPLY = false;

    /**
     * Jabber client.
     * @var \Kadet\Xmpp\XmppClient
     */
    protected $_bot;

    /**
     * Packet of message that contains command
     * @var \Kadet\Xmpp\Stanza\Message
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
     * @param Bot     $client Jabber client.
     * @param User    $author User who executed this command.
     * @param string  $lang   Commands language.
     * @param Message $packet Commands packet.
     */
    public function __construct(Bot $client, $author, $lang, Message $packet)
    {
        $this->_bot    = $client;
        $this->_packet = $packet;
        $this->_lang   = $lang;
        $this->_author = $author;
        $this->_type   = $packet->type;
    }

    /**
     * Executes command in XMPP.
     *
     * @param Params $args Arguments provided by user.
     *
     * @throws CommandException
     *
     * @return string Response sent to client.
     */
    public function execute($args)
    {
        throw new CommandException('This command is not assumed to be performed in XMPP');
    }

    /**
     * Gets help string of command.
     *
     * @param  string $lang    Language of help localization.
     * @param  string $command Command access string.
     *
     * @return string Localized help string.
     */
    public static function getHelp($lang, $command)
    {
        return \__('help', $lang, get_called_class(), array('command' => $command));
    }

    /**
     * Gets short help string of command.
     *
     * @param string $lang Language of help localization.
     *
     * @return string
     */
    public static function getShortHelp($lang)
    {
        return \__('shortHelp', $lang, get_called_class());
    }

    /**
     * Checks if user has permission to execute command.
     *
     * @param User $user
     *
     * @return bool
     */
    public static function hasPermission(User $user)
    {
        $class = get_called_class();

        return $user->permission >= $class::PERMISSION;
    }
}