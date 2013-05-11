<?php
namespace XPBot\Bot;

use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Params;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\XmppClient;

class Bot extends XmppClient {
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
    }

    public function _parseCommand($message)
    {
        if(isset($message->delay['stamp'])) return; // message is from history, forgot about it.
        $author = $this->getUserByJid(new Jid($message['from']));
        $prompt = !empty($author->room->configuration->prompt) ?
            $author->room->configuration->prompt :
            $this->config->MUCPrompt;

        if(substr($message->body, 0, strlen($prompt)) == $prompt) {
            $content = substr($message->body, strlen($prompt));
            $params = new Params($content);

            $command = arrayDeepSearch($this->_commands, strtolower($params[0]));

            if($command === false) return;

            if(is_array($command)) {
                $str = "Command {$params[0]} is ambiguous!\n";
                foreach ($command as $package => $class) {
                    $str .= "\t$package-{$params[0]} refers to $class\n";
                }
                $author->room->message($str);
                return;
            }

            if($command) {
                $command = new $command($this, $author, 'pl', $message);
                $command->execute($params, true);
            }
        }
    }

    public function findCommands($dir, $package, $namespace) {
        $iterator = new \RecursiveDirectoryIterator(
            $dir,
            \RecursiveDirectoryIterator::SKIP_DOTS || \RecursiveDirectoryIterator::UNIX_PATHS
        );

        foreach($iterator as $file) {
            $this->_commands[$package][strtolower(strstr($file->getFilename(), '.', true))] = $namespace.'\\'.strstr($file->getFilename(), '.', true);
        }
    }

    public function getCommands() {
        return $this->_commands;
    }
}