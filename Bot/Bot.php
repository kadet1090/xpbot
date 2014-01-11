<?php
namespace XPBot\Bot;

use XPBot\System\Utils\Ini;
use XPBot\System\Utils\Language;
use XPBot\System\Utils\Logger;
use XPBot\System\Utils\Params;
use XPBot\System\Utils\XmlBranch;
use XPBot\System\Xmpp\Jid;
use XPBot\System\Xmpp\Room;
use XPBot\System\Xmpp\Stanza\Iq;
use XPBot\System\Xmpp\Stanza\Message;
use XPBot\System\Xmpp\User;
use XPBot\System\Xmpp\XmppClient;

/**
 * Class Bot
 * @package XPBot\Bot
 *
 * @todo over 500 LoC, refactor
 */
class Bot extends XmppClient
{
    /**
     * Bot version string.
     */
    const BOT_VERSION = 'Beta 0.6';

    /**
     * Commands list.
     *
     * @var array
     */
    protected $_commands = array();

    /**
     * List with all loaded plugins.
     *
     * @var Plugin[]
     */
    protected $_plugins;

    /**
     * List with macros.
     *
     * @var array
     */
    protected $_macros = array();

    /**
     * Bots configuration.
     *
     * @var \SimpleXMLElement
     */
    public $config;

    /**
     * User database.
     * User database, accessed by users[channel|roster][username].
     *
     * @todo roster support.
     *
     * @var User[string][string]
     */
    public $users;

    /**
     * Command aliases.
     *
     * @var \XPBot\System\Utils\Ini
     */
    public $aliases;

    /**
     * @param string $config Config to use in bot.
     */
    public function __construct($config = './Config/Config.xml')
    {
        set_error_handler(array($this, '_errorHandler'));
        register_shutdown_function(array($this, '_shutdownHandler'));
        ini_set('display_errors', 0);
        error_reporting(E_ERROR);

        $this->config = simplexml_load_file($config);
        $this->users = simplexml_load_file('./Config/Users.xml');
        $this->aliases = new Ini('./Config/Aliases.ini', true);

        parent::__construct(
            new Jid("{$this->config->xmpp->login}@{$this->config->xmpp->server}/{$this->config->xmpp->resource}"),
            (string)$this->config->xmpp->password,
            (string)$this->config->xmpp->port,
            (string)$this->config->xmpp->timeout
        );

        $this->_loadPlugins();
        $this->onMessage->add(array($this, '_parseCommand'));
        $this->onIq->add(array($this, '_parseIq'));

        $this->onReady->add(array($this, '_joinRooms'));
        $this->onJoin->add(array($this, '_onJoin'));

        $this->registerCommand('XPBot\\Bot\\Commands\\Alias', 'builtin', 'alias');
        $this->registerCommand('XPBot\\Bot\\Commands\\Config', 'builtin', 'config');
        $this->registerCommand('XPBot\\Bot\\Commands\\Help', 'builtin', 'help');
        $this->registerCommand('XPBot\\Bot\\Commands\\Permission', 'builtin', 'permission');
        $this->registerCommand('XPBot\\Bot\\Commands\\Plugin', 'builtin', 'plugin');
        $this->registerCommand('XPBot\\Bot\\Commands\\Quit', 'builtin', 'quit');
        $this->registerCommand('XPBot\\Bot\\Commands\\Quit', 'builtin', 'quit');

        Language::loadDir(dirname(__FILE__) . '/Languages/');

        $this->addMacro('me', array('XPBot\\Bot\\Bot', 'getNick'));
        $this->addMacro('date', array('XPBot\\Bot\\Bot', 'getDate'));
        $this->addMacro('time', array('XPBot\\Bot\\Bot', 'getTime'));
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _onJoin(Room $room, User $user, $broadcast)
    {
        $user->jointime = time();
        $user->permission = $this->getAffiliationPermission($user->affiliation);

        $users = $this->users->xpath("//user[@jid='{$user->jid->bare()}']");
        if ($users && isset($users[0]['permission']))
            $user->permission = (int)$users[0]['permission'];

        Logger::debug($user->nick . ' joined to ' . $room->jid->name . ' with permission ' . $user->permission);
    }

    public function _joinRooms()
    {
        foreach ($this->config->channels->channel as $channel) {
            $nick = isset($channel['nick']) ? $channel->nick : $this->config->xmpp->nickname;
            $channel = new Jid($channel['name'], $channel['server']);

            $this->join($channel, $nick);
        }
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _parseIq(Iq $iq)
    {
        if ($iq->type == 'get' && $iq->query != null && $iq->query->namespace == 'jabber:iq:version') {
            $xml = new XmlBranch('iq');
            $xml->addAttribute('from', $this->jid->__toString())
                ->addAttribute('to', $iq->from->__toString())
                ->addAttribute('type', 'result')
                ->addAttribute('id', $iq->id);

            $xml->addChild(new XmlBranch('query'))->addAttribute('xmlns', 'jabber:iq:version');
            $xml->query[0]->addChild(new XmlBranch('name'))->setContent('Xmpp Php Bot');
            $xml->query[0]->addChild(new XmlBranch('version'))->setContent(self::BOT_VERSION);
            $xml->query[0]->addChild(new XmlBranch('os'))->setContent(php_uname('s') . ' ' . php_uname('m') . ' ' . php_uname('v') . ' with PHP ' . PHP_VERSION);

            $this->write($xml->asXml());
        }
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _parseCommand(Message $message)
    {
        if (!$message->sender) return null;
        if ($message->sender->self == true) return null;
        if (isset($message->sender->room) && $message->sender->room->subject === false) return; // from history

        $prompt = !empty($message->sender->room->configuration->prompt) ?
            $message->sender->room->configuration->prompt :
            $this->config->MUCPrompt;

        Language::setGlobalVar('P', $prompt);
        $content = $message->body;

        foreach ($this->_macros as $macro => $func)
            $content = str_replace('!' . $macro, $func($message, $this), $message->body);

        if (substr($message->body, 0, strlen($prompt)) == $prompt) {
            $content = substr($content, strlen($prompt));
            $params = new Params($content);

            $command = $this->getCommand($params[0]);

            if ($command === false) return;

            if (is_array($command)) {
                $str = __('commandAmbiguous', 'pl_PL', 'default', array('command' => $params[0]));
                foreach ($command as $package => $class) {
                    $str .= "\t$package-{$params[0]} - $class\n";
                }
                $message->reply($str);
                return;
            }

            // TODO: private commands support.
            if ($command) {
                if (
                    ($message->type == "groupchat" && !$command::GROUPCHAT) ||
                    ($message->type == "chat" && !$command::CHAT)
                ) return;
                $commandName = $command;

                try {
                    if (!$command::hasPermission($message->sender))
                        throw new CommandException(
                            'User has no permission to run this command.',
                            __('errNoPermission', 'pl_PL')
                        );

                    $command = new $command($this, $message->sender, 'pl_PL', $message);
                    if ($result = $command->execute($params))
                        $command::PRIVREPLY ? $message->sender->privateMessage($result) : $message->reply($result);

                } catch (CommandException $exception) {
                    $message->reply($exception->getMessage());
                    Logger::warning("'{$exception->getConsoleMessage()}' in $commandName launched by {$message->sender->jid}");
                }
            }
        }
    }

    /**
     * Registers all commands in directory.
     *
     * @deprecated
     *
     * @param string $dir Dir to search.
     * @param string $package Commands package.
     * @param string $namespace Commands namespace.
     */
    public function findCommands($dir, $package, $namespace)
    {
        $iterator = new \RecursiveDirectoryIterator(
            $dir,
            \RecursiveDirectoryIterator::SKIP_DOTS || \RecursiveDirectoryIterator::UNIX_PATHS
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $class = strstr($file->getFilename(), '.', true);
            $this->registerCommand($namespace . '\\' . $class, $package);
        }
    }

    /**
     * Gets command list.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->_commands;
    }

    /**
     * Gets command class.
     *
     * @param string $name Command name.
     * @param bool $aliasing Check aliases too?
     *
     * @return array|bool|string Command class list if name is ambiguous or class.
     */
    public function getCommand($name, $aliasing = true)
    {
        if ($aliasing && isset($this->aliases[$name]))
            $name = $this->aliases[$name];

        $name = explode('-', $name, 2);
        if (count($name) == 2) {
            if (!isset($this->_commands[$name[0]]))
                return false;

            $search = $this->_commands[$name[0]];
            $name = $name[1];
        } else {
            $search = $this->_commands;
            $name = $name[0];
        }

        return arrayDeepSearch($search, $name);
    }

    /**
     * Checks if specified command exists and its name is unambiguous.
     *
     * @param string $command Command name.
     *
     * @return bool
     */
    public function commandExists($command)
    {
        $commands = $this->getCommand($command, false);
        return ($commands && !is_array($commands));
    }

    /**
     * Gets fully qualified command name.
     *
     * @param string $command Command name.
     *
     * @return bool|string Fully qualified command name.
     */
    public function getFullyQualifiedCommand($command)
    {
        if (strstr($command, '-')) return $command; // Command is already fully qualified command.

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($this->_commands), \RecursiveIteratorIterator::SELF_FIRST
        );

        $results = array();
        $parent = '';
        foreach ($iterator as $key => $value) {
            if ($iterator->callHasChildren()) {
                $parent = $key;
                continue;
            }

            if ($key == $command)
                $results[$parent] = $value;
        }

        if (count($results) == 1)
            return $parent . '-' . $command;

        return false;
    }

    /**
     * Gets list of command aliases.
     *
     * @param string $command Command name.
     *
     * @return array Command aliases list.
     */
    public function getCommandAliases($command)
    {
        $command = $this->getFullyQualifiedCommand($command);

        return array_keys(array_filter($this->aliases->asArray(), function ($value) use ($command) {
            return $value == $command;
        }));
    }

    /**
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     * @param null|mixed $default Value to return if variable doesn't exist.
     *
     * @return mixed
     */
    public function getFromConfig($var, $namespace, $default = null)
    {
        $result = $this->config->xpath("//plugins/var[@name='$var' and @namespace='$namespace']");
        if ($result) return (string)$result[0];
        else return $default;
    }

    /**
     * Sets variable in config to given value.
     *
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     * @param mixed $value Variable new value
     */
    public function setInConfig($var, $namespace, $value)
    {
        if (!isset($this->config->plugins)) $this->config->addChild('plugins');

        $result = $this->config->xpath("//plugins/var[@name='$var' and @namespace='$namespace']");

        if ($result) {
            $result[0]->{0} = $value;
        } else {
            $result = $this->config->plugins->addChild('var', $value);
            $result->addAttribute('name', $var);
            $result->addAttribute('namespace', $namespace);
        }

        $this->config->asXML('./Config/Config.xml');
    }

    /**
     * Removes configuration value.
     *
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     */
    public function removeFromConfig($var, $namespace)
    {
        $result = $this->config->xpath("//plugins/var[@name='$var' and @namespace='$namespace']");

        if ($result) {
            unset($result[0][0]);
            $this->config->asXML('./Config/Config.xml');
        }
    }

    /**
     * Registers command in bot.
     *
     * @param string $class Class name with namespace.
     * @param string $package Command package (eg builtin)
     * @param string|null $command Command name, if null class name will be used.
     *
     * @throws \InvalidArgumentException
     */
    public function registerCommand($class, $package, $command = null)
    {
        if (!class_exists($class))
            throw new \InvalidArgumentException('class');

        if (empty($command)) {
            $chunks = explode('\\', $class);
            $command = end($chunks);
        }

        $this->_commands[$package][strtolower($command)] = $class;
    }

    /**
     * Unregisters command in bot.
     *
     * @param string $package Command package (eg builtin)
     * @param string $command Command name, if null class name will be used.
     */
    public function unregisterCommand($package, $command)
    {
        unset($this->_commands[$package][strtolower($command)]);
        if (empty($this->_commands[$package]))
            unset($this->_commands[$package]);
    }

    /**
     * Gets proper permission level according to affiliation.
     *
     * @param string $affiliation
     *
     * @return int Permission according to given affiliation.
     */
    private function getAffiliationPermission($affiliation)
    {
        switch ($affiliation) {
            case 'owner':
                return 8;
            case 'admin':
                return 6;
            case 'member':
                return 4;
            case 'none':
                return 2;
        }
    }

    /**
     * Updates permission array in bot after permission change.
     *
     * @param Jid $jid Jid to refresh.
     *
     * @return null
     */
    public function updatePermission(Jid $jid)
    {
        $users = $this->users->xpath("//user[@jid='{$jid->bare()}']");
        if ($users && isset($users[0]['permission']))
            $permission = (int)$users[0]['permission'];

        foreach ($this->rooms as $room)
            foreach ($room->users as $user)
                if ($user->jid->bare() == $jid->bare())
                    $user->permission = isset($permission) ?
                        $permission :
                        $this->getAffiliationPermission($user->affiliation);
    }

    private function _loadPlugins()
    {
        $iterator = new \RecursiveDirectoryIterator(
            'Plugins/',
            \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::UNIX_PATHS
        );

        foreach ($iterator as $file) {
            if (!file_exists($file->getPathname() . '/manifest.xml')) {
                Logger::warning('Plugin on path "' . $file->getPathname() . '" hasn\'t manifest, skipping...');
                continue;
            }

            $manifest = simplexml_load_file($file->getPathname() . '/manifest.xml');
            if (!isset($manifest->file)) {
                Logger::warning('Plugin on path "' . $file->getPathname() . '" hasn\'t set file, skipping...');
                continue;
            }

            include $file->getPathname() . '/' . $manifest->file;
            if (!isset($manifest->class) || !class_exists($manifest->class)) {
                Logger::warning('Plugin on path "' . $file->getPathname() . '" hasn\'t set class or specified class not exists, skipping...');
                continue;
            }

            if (!is_subclass_of((string)$manifest->class, 'XPBot\\Bot\\Plugin')) {
                Logger::warning('Plugin on path "' . $file->getPathname() . '" is not a valid plugin, skipping...');
                continue;
            }

            $plugin = (string)$manifest->class;
            $plugin = new $plugin($this, $manifest);
            $plugin->load();
            $this->_plugins[(string)$manifest->name] = $plugin;
        }
    }

    private function _loadPlugin($file)
    {
        if (!file_exists($file->getPathname() . '/manifest.xml')) {
            Logger::warning('Plugin on path "' . $file->getPathname() . '" hasn\'t manifest, skipping...');
            return false;
        }
    }

    public function getPlugins()
    {
        return $this->_plugins;
    }

    /**
     * Adds new macro to bot.
     *
     * @param string $name Macros name
     * @param callable $delegate Delegate to macros function.
     */
    public function addMacro($name, callable $delegate)
    {
        $this->_macros[$name] = $delegate;
    }

    /**
     * Removes macro from bot.
     *
     * @param string $name Macros name.
     */
    public function removeMacro($name)
    {
        unset($this->_macros[$name]);
    }

    // MACROS
    /**
     * @param Message $packet
     * @param Bot $bot
     * @return bool|string
     *
     * @ignore
     */
    public static function getNick($packet, Bot $bot)
    {
        if ($packet->sender) return $packet->sender->nick;
        return false;
    }

    /**
     * @param \SimpleXMLElement $packet
     * @param Bot $bot
     * @return bool|string
     *
     * @ignore
     */
    public static function getDate($packet, Bot $bot)
    {
        return date('d.m.Y');
    }

    /**
     * @param \SimpleXMLElement $packet
     * @param Bot $bot
     * @return bool|string
     *
     * @ignore
     */
    public static function getTime($packet, Bot $bot)
    {
        return date('H:i:s');
    }

    public function _errorHandler($level, $message, $file = '', $line = 0)
    {
        $message = 'PHP: ' . $message . ' in ' . str_replace(getcwd(), '.', $file) . ' on line ' . $line;

        switch ($level) {
            case E_WARNING:
                Logger::warning($message);
                break;
            case E_DEPRECATED:
            case E_NOTICE:
                Logger::debug($message);
                break;
            case E_ERROR:
                Logger::error($message);
                return false; // backtrace is corrupted.
        }

        $callstack = debug_backtrace();
        $backtrace = [];

        foreach ($callstack as $no => $call) {
            $args = array();
            foreach ($call['args'] as $argno => $arg) {
                if (is_string($arg)) $args[$argno] = "'" . (strlen($arg) > 50 ? substr($arg, 0, 50) . '...' : $arg) . "'";
                if (is_bool($arg)) $args[$argno] = $arg ? 'true' : 'false';
                if (is_array($arg)) $args[$argno] = 'array';
                if (is_object($arg)) $args[$argno] = get_class($arg);
            }

            $str = '#' . ($no + 1) . ' ';
            if (isset($call['file']) && isset($call['line'])) $str .= $call['file'] . '@' . $call['line'] . ' ';
            $str .= (!empty($call['class']) ? $call['class'] . $call['type'] : '') . $call['function'] . '(' . implode(', ', $args) . ')';
            $backtrace[] = $str;
        }

        Logger::debug('Cor... Callstack dump: ' . PHP_EOL . implode(PHP_EOL, $backtrace));
        return true;
    }

    public function _shutdownHandler()
    {
        $error = error_get_last();
        if ($error['type'] = E_ERROR)
            $this->_errorHandler(E_ERROR, $error['message'], $error['file'], $error['line']);
    }

    public function restart()
    {

    }
}