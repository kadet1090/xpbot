<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author  Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Commands;

use XPBot\Command;
use XPBot\Config\ConfigModule;
use XPBot\Exceptions\CommandException;
use XPBot\Permissions\CascadingPermissionSystem;

class Permission extends Command
{
    const PERMISSION = 10;

    public function execute($args)
    {
        if (!isset($args[1]))
            throw new CommandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if (!method_exists($this, $args[1]))
            throw new CommandException('Invalid action.', __('errInvalidAction', $this->_lang));

        return $this->{$args[1]}($args);
    }

    public function get($args)
    {
        if (!isset($this->_author->room->users[$args[2]]))
            throw new commandException('This user is not present on that channel.', __('errUserNotPresent', $this->_lang));

        $permissions = $this->_author->room->users[$args[2]]->config->permissions->get();
        uksort($permissions, function ($a, $b) {
            $ac = substr_count($a, '/');
            $bc = substr_count($b, '/');

            if ($ac != $bc)
                return ($ac < $bc) ? -1 : 1;

            $ac = strlen($a);
            $bc = strlen($b);

            if ($ac != $bc)
                return ($ac < $bc) ? -1 : 1;

            return 0;
        });

        $result = '';
        foreach ($permissions as $permission => $granted)
            $result .= ($granted ? '' : ' ~ ') . $permission . "\n";

        return $result;
    }

    public function grant($args)
    {
        $permissions = $this->getSystem($args[2]);
        $permissions->grant($args[3]);
        $this->_bot->config->save();
    }

    public function revoke($args)
    {
        $permissions = $this->getSystem($args[2]);
        $permissions->revoke($args[3]);

        $this->_bot->config->save();
    }

    public function has($args)
    {
        $permissions = $this->getSystem($args[2]);
        if ($permissions->has($args[3])) return __("yes", $this->_lang);
        else return __("no", $this->_lang);
    }

    private function getSystem($user)
    {
        if (isset($this->_author->room->users[$user]))
            return $this->_author->room->users[$user]->config->permissions;
        else {
            if (!isset($this->_bot->config->users[$user]->permissions)) {
                if (!isset($this->_bot->config->users[$user]))
                    $this->_bot->config->users[$user] = new ConfigModule();

                $this->_bot->config->users[$user]->permissions = new CascadingPermissionSystem();
            }

            return $this->_bot->config->users[$user]->permissions;
        }
    }
}