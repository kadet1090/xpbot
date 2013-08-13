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

class Wikipedia extends Command
{
    public function execute($args)
    {
        if(!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        if(!isset($args['l']))
            $args['l'] = $this->_bot->getFromConfig('wikipediaLang', 'internet', 'en');

        $args[1] = urlencode($args[1]);
        $url = "http://{$args['l']}.wikipedia.org/w/api.php?action=parse&page={$args[1]}&format=json&prop=text&section=0";
        $ch = curl_init($url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'XPBot/'.Bot::BOT_VERSION.' (kadet1090@gmail.com) ');
        $content = json_decode(curl_exec($ch));
        $text = $content->parse->text->{'*'};

        preg_match_all('/<p>(.*?)<\/p>/si', $text, $matches);
        $nop = isset($args['p']) ?
            $args['p'] :
            $this->_bot->getFromConfig('wikiNoParagraphs', 'internet', 1);

        $paragraph = '';
        for($i = 0; $i < min($nop, count($matches[1])); $i++)
            $paragraph .= $matches[1][$i]."\n";
        $paragraph = strip_tags(trim($paragraph));
        $paragraph = preg_replace('/\[[0-9]+\]/s', '', $paragraph);

        return $paragraph;
    }
}