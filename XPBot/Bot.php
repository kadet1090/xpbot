<?php
namespace XPBot;

use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Room;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Stanza\Message;
use Kadet\Xmpp\User;
use Kadet\Xmpp\Utils\XmlBranch;
use Kadet\Xmpp\XmppClient;
use XPBot\Config\AliasConfig;
use XPBot\Config\Config;
use XPBot\Config\RoomsConfig;
use XPBot\Config\UsersConfig;
use XPBot\Exceptions\CommandAmbiguousException;
use XPBot\Exceptions\CommandException;
use XPBot\Exceptions\NoPermissionException;
use XPBot\Utils\Language;
use XPBot\Utils\Params;

/**
 * Class Bot
 * @package XPBot
 *
 * @todo over 500 LoC, refactor
 */
class Bot extends XmppClient
{
    /**
     * Bot version string.
     */
    const BOT_VERSION = 'Beta 0.8';

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
     * @var Config
     */
    public $config;


    /**
     * @param string $config Config to use in bot.
     */
    public function __construct($config = './Config/Config.xml')
    {
        set_error_handler(array($this, '_errorHandler'));
        register_shutdown_function(array($this, '_shutdownHandler'));
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        $this->config = new Config($config);

        if (!isset($this->config->rooms))
            $this->config->rooms = new RoomsConfig();

        if (!isset($this->config->users))
            $this->config->users = new UsersConfig();

        if (!isset($this->config->aliases))
            $this->config->aliases = new AliasConfig();

        parent::__construct(
            new Jid("{$this->config->xmpp->login}@{$this->config->xmpp->server}/{$this->config->xmpp->resource}"),
            (string)$this->config->xmpp->password,
            (string)$this->config->xmpp->port,
            (string)$this->config->xmpp->timeout
        );

        $this->_loadPlugins();
        $this->onConnect->add(function (XmppClient $client) {
            while ($this->isConnected) {
                $this->process();
                usleep(100);
            }
        });

        $this->onMessage->add(array($this, '_parseMessage'));
        $this->onIq->add(array($this, '_parseIq'));

        $this->onReady->add(array($this, '_joinRooms'));
        $this->onJoin->add(array($this, '_onJoin'));

        $this->registerCommand('XPBot\\Commands\\Alias', 'builtin', 'alias');
        $this->registerCommand('XPBot\\Commands\\Config', 'builtin', 'config');
        $this->registerCommand('XPBot\\Commands\\Help', 'builtin', 'help');
        $this->registerCommand('XPBot\\Commands\\Permission', 'builtin', 'permission');
        $this->registerCommand('XPBot\\Commands\\Plugin', 'builtin', 'plugin');
        $this->registerCommand('XPBot\\Commands\\Quit', 'builtin', 'quit');

        Language::loadDir(dirname(__FILE__) . '/Languages/');

        $this->addMacro('me', array('XPBot\\Bot', 'getNick'));
        $this->addMacro('date', array('XPBot\\Bot', 'getDate'));
        $this->addMacro('time', array('XPBot\\Bot', 'getTime'));
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _onJoin(XmppClient $client, Room $room, User $user, $broadcast)
    {
        $user->jointime = time();
        $user->permission = $this->getAffiliationPermission($user->affiliation);

        if (isset($this->config->users[$user->jid->bare()]))
            $user->permission = $this->config->users[$user->jid->bare()]->permission;

        if (isset($this->logger)) $this->logger->info($user->nick . ' joined to ' . $room->jid->name . ' with permission ' . $user->permission);
    }

    public function _joinRooms(XmppClient $client)
    {
        foreach ($this->config->rooms as $jid => $room) {
            if ($room['autojoin'] != 'true') continue;

            $nick = isset($room['nick']) ? $room->nick : $this->config->xmpp->nickname;
            $room = new Jid($jid);

            $this->join($room, $nick);
        }
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _parseIq(XmppClient $client, Iq $iq)
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
    public function evaluateCommand($command, Message $message)
    {
        if (preg_match('/\`((?:(?>[^`]+)|(?R))*)\`/six', $command))
            $command = preg_replace_callback('/\`((?:(?>[^`]+)|(?R))*)\`/six', function ($matches) use ($message) {
                return '"' . $this->evaluateCommand($matches[1], $message) . '"';
            }, $command);

        $params  = new Params($command);
        $command = $this->getCommand($params[0]);

        if ($command === false) return null;

        if (is_array($command))
            throw new CommandAmbiguousException($params[0], $command);

        // TODO: private commands support.
        if (
            ($message->type == "groupchat" && !$command::GROUPCHAT) ||
            ($message->type == "chat" && !$command::CHAT)
        ) return null;

        if (!$command::hasPermission($message->sender))
            throw new NoPermissionException($params[0]);

        $command = new $command($this, $message->sender, 'pl_PL', $message);

        return $command->execute($params);
    }

    /**
     * @ignore Because it should be private, but it is used in delegate.
     */
    public function _parseMessage(XmppClient $client, Message $message)
    {
        if (!$message->sender) return null;
        if ($message->sender->self == true) return null;
        if (isset($message->sender->room) && $message->sender->room->subject === false) return; // from history

        $prompt = isset($this->config->rooms[$message->sender->room->jid->bare()]->prompt) ?
            $this->config->rooms[$message->sender->room->jid->bare()]->prompt :
            $this->config->storage->get('muc-prompt', 'bot', '#');

        if (substr($message->body, 0, strlen($prompt)) != $prompt) return;

        Language::setGlobalVar('P', $prompt);
        $content = $message->body;

        foreach ($this->_macros as $name => $macro) {
            $content = str_replace('!' . $name, is_callable($macro) ? $macro($message, $this) : $macro, $content);
        }

        try {
            $message->reply($this->evaluateCommand(substr($content, strlen($prompt)), $message));
        } catch (CommandAmbiguousException $e) {
            $str = __('commandAmbiguous', 'pl_PL', 'default', array('command' => $e->getCommand()));

            foreach ($e->getReferences() as $package => $class) {
                $str .= "\t$package-{$e->getCommand()} - $class\n";
            }
            $message->reply($str);
        } catch (CommandException $exception) {
            $message->reply($exception->getMessage());
            if (isset($this->logger)) $this->logger->warning("'{$exception->getConsoleMessage()}' in {$exception->getCommand()} launched by {$message->sender->jid}");
        } catch (NoPermissionException $exception) {
            $message->reply($exception->getMessage());
            if (isset($this->logger)) $this->logger->warning("{$message->from} has no permission to run {$exception->getCommand()} command.");
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
        if ($aliasing && isset($this->config->aliases[$name]))
            $name = $this->config->aliases[$name];

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
        $commands = $this->getCommand($command);

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
        if (isset($this->config->aliases[$command]))
            $command = $this->config->aliases[$command];

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

        return array_keys(array_filter($this->config->aliases->aliases, function ($value) use ($command) {
            return $value == $command;
        }));
    }

    /**
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     * @param null|mixed $default Value to return if variable doesn't exist.
     *
     * @return mixed
     *
     * @deprecated
     */
    public function getFromConfig($var, $namespace, $default = null)
    {
        return $this->config->storage->get($var, $namespace, $default);
    }

    /**
     * Sets variable in config to given value.
     *
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     * @param mixed $value Variable new value
     *
     * @deprecated
     */
    public function setInConfig($var, $namespace, $value)
    {
        $this->config->storage->set($var, $namespace, $value);
    }

    /**
     * Removes configuration value.
     *
     * @param string $var Variable name
     * @param string $namespace Variable namespace
     *
     * @deprecated
     */
    public function removeFromConfig($var, $namespace)
    {
        $this->config->storage->unset($var, $namespace);
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
        /// TODO: Change that shit
        /*$users = $this->users->xpath("//user[@jid='{$jid->bare()}']");
        if ($users && isset($users[0]['permission']))
            $permission = (int)$users[0]['permission'];

        foreach ($this->rooms as $room)
            foreach ($room->users as $user)
                if ($user->jid->bare() == $jid->bare())
                    $user->permission = isset($permission) ?
                        $permission :
                        $this->getAffiliationPermission($user->affiliation);*/
    }

    private function _loadPlugins()
    {
        $iterator = new \RecursiveDirectoryIterator(
            'Plugins/',
            \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::UNIX_PATHS
        );

        foreach ($iterator as $file) {
            if (!file_exists($file->getPathname() . '/manifest.xml')) {
                if (isset($this->logger)) $this->logger->warning('Plugin on path "' . $file->getPathname() . '" hasn\'t manifest, skipping...');
                continue;
            }

            $manifest = simplexml_load_file($file->getPathname() . '/manifest.xml');
            if (!isset($manifest->file)) {
                if (isset($this->logger)) $this->logger->warning('Plugin on path "' . $file->getPathname() . '" hasn\'t set file, skipping...');
                continue;
            }

            include $file->getPathname() . '/' . $manifest->file;
            if (!isset($manifest->class) || !class_exists($manifest->class)) {
                if (isset($this->logger)) $this->logger->warning('Plugin on path "' . $file->getPathname() . '" hasn\'t set class or specified class not exists, skipping...');
                continue;
            }

            if (!is_subclass_of((string)$manifest->class, 'XPBot\\Plugin')) {
                if (isset($this->logger)) $this->logger->warning('Plugin on path "' . $file->getPathname() . '" is not a valid plugin, skipping...');
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
            if (isset($this->logger)) $this->logger->warning('Plugin on path "' . $file->getPathname() . '" hasn\'t manifest, skipping...');

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
     * @param string|callable $macro Function or text represented by macro.
     */
    public function addMacro($name, $macro)
    {
        $this->_macros[$name] = $macro;
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

        if (!isset($this->logger)) return true;

        switch ($level) {
            case E_WARNING:
                $this->logger->warning($message);
                break;
            case E_DEPRECATED:
            case E_NOTICE:
            $this->logger->debug($message);
            break;
            case E_ERROR:
                $this->logger->error($message);

                return false; // backtrace is corrupted.
            default:
                $this->logger->warning($message);
                break;
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

        $this->logger->debug('Cor... Callstack dump: ' . PHP_EOL . implode(PHP_EOL, $backtrace));

        return true;
    }

    public function _shutdownHandler()
    {
        $error = error_get_last();
        if ($error['type'] == E_ERROR) // OMG SO MUCH FAIL
            $this->_errorHandler(E_ERROR, $error['message'], $error['file'], $error['line']);
    }
}