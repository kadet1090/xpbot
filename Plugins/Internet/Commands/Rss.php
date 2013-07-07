<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

namespace XPBot\Plugins\Internet\Commands;

use XPBot\Bot\Bot;
use XPBot\Bot\Command;
use XPBot\Bot\CommandException;
use XPBot\System\Utils\Delegate;
use XPBot\System\Xmpp\Jid;

class Rss extends Command
{
    public function execute($args)
    {
        if(!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $xml = file_get_contents($args[1]);
        if(!preg_match('/<rss /si', $xml))
            throw new commandException('Given url is not a rss channel.', __('errNotRssChannel', $this->_lang));
        $xml = simplexml_load_string($xml);

        $return = $xml->channel->title.' | '.$xml->channel->description.' ('.$xml->channel->link.")\n\n";
        for(
            $i = 0, $max = min($this->_bot->getFromConfig('rssCount', 'internet', 5), count($xml->channel->item));
            $i < $max;
            $i++
        ) {
            $return .= ($i+1).". {$xml->channel->item[$i]->title} ({$xml->channel->item[$i]->link})\n";
            if(!isset($args['to']))
                $return .= html_entity_decode(strip_tags($xml->channel->item[$i]->description))."\n\n";
        }
        return $return;
    }
}