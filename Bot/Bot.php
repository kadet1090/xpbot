<?php
namespace XPBot\Bot;

use XPBot\System\Utils\Delegate;
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

        if (substr($message->body, 0, strlen($prompt)) == $prompt) {
            $content = substr($message->body, strlen($prompt));
            $params  = new Params($content);

            $command = arrayDeepSearch($this->_commands, strtolower($params[0]));

            if ($command === false) return;

            if (is_array($command)) {
                $str = "Command {$params[0]} is ambiguous!\n";
                foreach ($command as $package => $class) {
                    $str .= "\t$package-{$params[0]} refers to $class\n";
                }
                $author->room->message($str);

                return;
            }

            if ($command) {
                $command = new $command($this, $author, 'pl', $message);
                $command->execute($params, true);
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
}