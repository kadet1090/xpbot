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

class Weather extends Command
{
    public function execute($args)
    {
        if(!isset($args[1]))
            throw new commandException('Too few arguments.', __('errTooFewArguments', $this->_lang));

        $arguments = array(
            'format=json',
            'q='.urldecode($args[1]),
            'key=e5e9daptrk56vqe6k9vm74d6',
            'num_of_days='.(isset($args['n']) ? $args['n'] : $this->_bot->getFromConfig('numOfDays', 'internet', 1)),
        );
        $url = 'http://api.worldweatheronline.com/free/v1/weather.ashx?'.implode('&', $arguments);
        echo $url;
        $result = json_decode(file_get_contents($url));
        var_dump($result->data->current_condition[0]);
        return __('weather', $this->_lang, __CLASS__, array(
            'city' => $args[1],
            'temp' => $result->data->current_condition[0]->temp_C,
            'desc' => $result->data->current_condition[0]->weatherDesc[0]->value,
            'winddirdeg' => $result->data->current_condition[0]->winddirDegree,
            'winddir16' => $result->data->current_condition[0]->winddir16Point,
            'windspeed' => $result->data->current_condition[0]->windspeedKmph,
            'humidity' => $result->data->current_condition[0]->humidity,
            'pressure' => $result->data->current_condition[0]->pressure,
            'visibility' => $result->data->current_condition[0]->visibility,
            'cloudcover' => $result->data->current_condition[0]->cloudcover,
            'precipitation' => $result->data->current_condition[0]->percipMM
        ));
    }
}