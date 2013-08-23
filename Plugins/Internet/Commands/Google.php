<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet\Commands;

use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Google extends Command
{
    const GROUPCHAT = false;

    public function execute($args)
    {
        if(!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $args[1] = urlencode($args[1]);
        $results = json_decode(file_get_contents("http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q={$args[1]}"));

        $return = "";
        foreach ($results->responseData->results as $i => $result) {
            $i++;
            $result->title = strip_tags($result->title);
            $return .= "{$i}. {$result->title} ({$result->url}) \n";
        }

        return $return;
    }
}