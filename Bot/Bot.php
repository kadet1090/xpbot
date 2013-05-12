<?php
namespace XPBot\Bot;

use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Language;
use XPBot\System\Utils\Logger;
use XPBot\System\Utils\Params;
use XPBot\System\Utils\XmlBranch;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\XmppClient;

class Bot extends XmppClient
{
    const BOT_VERSION = '0.1 Beta';

    protected $_commands = array();
    public $config;

    public function __construct($config = './Config/Config.xml')
    {
        $this->config = simplexml_load_file($config);

        parent::__construct(
            new Jid("{$this->config->xmpp->login}@{$this->config->xmpp->server}/{$this->config->xmpp->resource}"),
            (string)$this->config->xmpp->password,
            (string)$this->config->xmpp->port,
            (string)$this->config->xmpp->timeout
        );

        $this->findCommands('./Bot/Commands/', 'builtin', 'XPBot\\Bot\\Commands');
        $this->onMessage->add(new Delegate(array($this, '_parseCommand')));
        $this->onIq->add(new Delegate(array($this, '_parseIq')));

        $this->onReady->add(new Delegate(array($this, '_joinRooms')));

        Language::loadDir('Languages');
    }

    public function _joinRooms()
    {
        foreach ($this->config->channels->channel as $channel) {
            $nick    = isset($channel['nick']) ? $channel->nick : $this->config->xmpp->nickname;
            $channel = new Jid($channel['name'], $channel['server']);

            $this->join($channel, $nick);

            Logger::info('Joined to ' . $channel->bare() . ' as ' . $nick . '.');
        }
    }

    public function _parseIq($query)
    {
        if (preg_match('/xmlns=("|\')jabber:iq:version("|\')/si', $query->asXML())) {
            $xml = new XmlBranch('iq');
            $xml->addAttribute('from', $this->jid->__toString())
                ->addAttribute('to', $query['from'])
                ->addAttribute('type', 'result')
                ->addAttribute('id', $query['id']);

            $xml->addChild(new XmlBranch('query'));
            $xml->query[0]->addChild(new XmlBranch('name'))->setContent('Xmpp Php Bot');
            $xml->query[0]->addChild(new XmlBranch('version'))->setContent(self::BOT_VERSION);
            $xml->query[0]->addChild(new XmlBranch('os'))->setContent(php_uname('s') . ' ' . php_uname('m') . ' ' . php_uname('v') . ' with PHP ' . PHP_VERSION);

            $this->write($xml->asXML());
        }
    }

    public function _parseCommand($message)
    {
        if (isset($message->delay['stamp'])) return; // message is from history, forgot about it.
        $author = $this->getUserByJid(new Jid($message['from']));
        $prompt = !empty($author->room->configuration->prompt) ?
            $author->room->configuration->prompt :
            $this->config->MUCPrompt;

        Language::setGlobalVar('P', $prompt);

        if (substr($message->body, 0, strlen($prompt)) == $prompt) {
            $content = substr($message->body, strlen($prompt));
            $params  = new Params($content);

            $command = $this->getCommand($params[0]);

            if ($command === false) return;

            if (is_array($command)) {
                $str = __('commandAmbiguous', $this->_lang, 'default', array('command' => $params[0]));
                foreach ($command as $package => $class) {
                    $str .= "\t$package-{$params[0]} - $class\n";
                }
                $author->room->message($str);

                return;
            }

            // TODO: private commands support.
            if ($command) {
                $commandName = $command;
                $command     = new $commandName($this, $author, 'pl', $message);
                try {
                    $result = $command->execute($params, true);

                    if ($result !== null) {
                        $author->room->message($result);
                    }
                } catch (CommandException $exception) {
                    $author->room->message($exception->getMessage());
                    Logger::warning("'{$exception->getConsoleMessage()}' in $commandName launched by {$author->jid}");
                }
            }
        }
    }

    public function findCommands($dir, $package, $namespace)
    {
        $iterator = new \RecursiveDirectoryIterator(
            $dir,
            \RecursiveDirectoryIterator::SKIP_DOTS || \RecursiveDirectoryIterator::UNIX_PATHS
        );

        foreach ($iterator as $file) {
            $this->_commands[$package][strtolower(strstr($file->getFilename(), '.', true))] = $namespace . '\\' . strstr($file->getFilename(), '.', true);
        }
    }

    public function getCommands()
    {
        return $this->_commands;
    }

    public function getCommand($name)
    {
        $name = explode('-', $name, 2);
        if (count($name) == 2) {
            if (!isset($this->_commands[$name[0]]))
                return false;

            $search = $this->_commands[$name[0]];
            $name   = $name[1];
        } else {
            $search = $this->_commands;
            $name   = $name[0];
        }

        return arrayDeepSearch($search, $name);
    }
}