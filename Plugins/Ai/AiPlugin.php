<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */
namespace XPBot\Plugins\Ai;

use XPBot\Bot\Plugin;
use XPBot\Plugins\Ai\Lib\Chatter;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\Stanza\Message;

class AiPlugin extends Plugin
{
    private $chatter;

    public function load()
    {
        $this->chatter = new Chatter($this->_bot);
        $this->chatter->loadDictionary('./Config/default.txt');
        $this->_bot->onMessage->add(array($this, 'parse'));
    }

    public function parse(Message $packet)
    {
        if (isset($packet->xml->subject) || isset($packet->xml->delay['stamp']) || $packet->type == "error") return;
        $user = $packet->sender;

        if ($user->self) return;

        if (rand(0, 100) < $this->_bot->getFromConfig('replyrate', 'ai', 33))
            $packet->reply($this->chatter->generate($packet->body));

        if ($this->_bot->getFromConfig('learning', 'ai', 'true') == 'true')
            $this->chatter->learn($packet->body);

        if ($this->_bot->getFromConfig('autosave', 'ai', 'true') == 'true')
            file_put_contents('./Config/default.txt', "\n" . $packet->body, FILE_APPEND);
    }

    public function unload()
    {
        $this->_bot->onMessage->remove(array($this, 'parse'));
    }
}