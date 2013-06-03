<?php
namespace XPBot\Bot;

use XPBot\System\Utils\Delegate;
use XPBot\System\Utils\Ini;
use XPBot\System\Utils\Language;
use XPBot\System\Utils\Logger;
use XPBot\System\Utils\Params;
use XPBot\System\Utils\XmlBranch;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\User;
use XPBot\System\Xmpp\XmppClient;

class Bot extends XmppClient
{
    const BOT_VERSION = 'Beta 0.3';

    protected $_commands = array();
    protected $_plugins;
    protected $_macros = array();

    public $config;
    public $users;
    public $aliases;

    public function __construct($config = './Config/Config.xml')
    {
        $this->config = simplexml_load_file($config);
        $this->users  = simplexml_load_file('./Config/Users.xml');
        $this->aliases = new Ini('./Config/Aliases.ini', true);

        parent::__construct(
            new Jid("{$this->config->xmpp->login}@{$this->config->xmpp->server}/{$this->config->xmpp->resource}"),
            (string)$this->config->xmpp->password,
            (string)$this->config->xmpp->port,
            (string)$this->config->xmpp->timeout
        );

        $this->_loadPlugins();
        $this->onMessage->add(new Delegate(array($this, '_parseCommand')));
        $this->onIq->add(new Delegate(array($this, '_parseIq')));

        $this->onReady->add(new Delegate(array($this, '_joinRooms')));
        $this->onJoin->add(new Delegate(array($this, '_onJoin')));

        $this->addMacro('me'  , new Delegate('XPBot\\Bot\\Bot::getNick'));
        $this->addMacro('date', new Delegate('XPBot\\Bot\\Bot::getDate'));
        $this->addMacro('time', new Delegate('XPBot\\Bot\\Bot::getTime'));

        Language::loadDir('Languages');
    }

    public function _onJoin(Room $room, User $user, $broadcast)
    {
        $user->jointime = time();

        switch ($user->affiliation) {
            case 'owner':
                $user->permission = 8;
                break;
            case 'admin':
                $user->permission = 6;
                break;
            case 'member':
                $user->permission = 4;
                break;
            case 'none':
                $user->permission = 2;
                break;
        }

        $users = $this->users->xpath("//user[@jid='{$user->jid->bare()}']");
        if ($users && isset($users[0]['permission']))
            $user->permission = (int)$users[0]['permission'];

        Logger::debug($user->nick . ' joined to ' . $room->jid->name . ' with permission ' . $user->permission);
    }

    public function _joinRooms()
    {
        foreach ($this->config->channels->channel as $channel) {
            $nick    = isset($channel['nick']) ? $channel->nick : $this->config->xmpp->nickname;
            $channel = new Jid($channel['name'], $channel['server']);

            $this->join($channel, $nick);
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
        if(!$author) return null;

        $prompt = !empty($author->room->configuration->prompt) ?
            $author->room->configuration->prompt :
            $this->config->MUCPrompt;

        Language::setGlobalVar('P', $prompt);

        foreach($this->_macros as $macro => $func)
            $message->body = str_replace('!'.$macro, $func->run($message, $this), $message->body);

        $reply = function ($msg) use ($message, $author) {
            $message['type'] == 'groupchat' ?
                $author->room->message($msg) :
                $this->message(new Jid($message['from']), $msg);
        };

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
                $reply($str);
                return;
            }

            // TODO: private commands support.
            if ($command) {
                $commandName = $command;
                try {

                    if (!$command::hasPermission($author))
                        throw new CommandException(
                            'User has no permission to run this command.',
                            __('errNoPermission', 'pl')
                        );

                    $command = new $commandName($this, $author, 'pl', $message);

                    if ($result = $command->execute($params))
                        $reply($result);

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

    public function getCommand($name, $aliasing = true)
    {
        if($aliasing && isset($this->aliases[$name]))
            $name = $this->aliases[$name];

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

    public function commandExists($command) {
        $commands = $this->getCommand($command, false);
        return ($commands && !is_array($commands));
    }

    public function getFullCommandName($command) {
        if(strstr($command, '-')) return $command; // Command is already fully named.

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($this->_commands), \RecursiveIteratorIterator::SELF_FIRST
        );

        $results = array();
        $parent  = '';
        foreach ($iterator as $key => $value) {
            if ($iterator->callHasChildren()) {
                $parent = $key;
                continue;
            }

            if ($key == $command)
                $results[$parent] = $value;
        }

        if (count($results) == 1)
            return $parent.'-'.$command;

        return false;
    }

    public function getCommandAliases($command) {
        $command = $this->getFullCommandName($command);

        return array_keys(array_filter($this->aliases->asArray(), function ($value) use ($command) {
            return $value == $command;
        }));
    }

    public function getFromConfig($var, $namespace, $default = nulll) {
        $result = $this->config->xpath("//plugins/var[@name='$var' and @namespace='$namespace']");
        if($result) return (string)$result[0];
        else return $default;
    }

    private function _loadPlugins() {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            'Plugins/',
            \RecursiveDirectoryIterator::SKIP_DOTS || \RecursiveDirectoryIterator::UNIX_PATHS
        ));

        foreach ($iterator as $file) {
            if(preg_match('/(.*Plugin).php$/', $file->getFilename(), $matches)) {
                $class = 'XPBot\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $file->getPath()).'\\'.$matches[1];
                $this->_plugins[$matches[1]] = new $class($this);
                $this->_plugins[$matches[1]]->load();
            }
        }
    }

    public function addMacro($name, Delegate $delegate) {
        $this->_macros[$name] = $delegate;
    }

    public function removeMacro($name) {
        unset($this->_macros[$name]);
    }

    // MACROS
    public static function getNick($packet, Bot $bot)
    {
        $user = $bot->getUserByJid(new Jid($packet['from']));
        if($user) return $user->nick;
        return false;
    }

    public static function getDate($packet, Bot $bot)
    {
        return date('d.m.Y');
    }

    public static function getTime($packet, Bot $bot)
    {
        return date('H:i:s');
    }
}