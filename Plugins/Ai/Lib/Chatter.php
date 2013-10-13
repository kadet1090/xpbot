<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 24.07.13
 * Time: 14:43
 * To change this template use File | Settings | File Templates.
 */

namespace XPBot\Plugins\Ai\Lib;


use XPBot\Bot\Bot;

class Chatter
{
    private $_words = array();
    private $_bot;

    public function __construct(Bot $bot)
    {
        $this->_bot = $bot;
    }

    public function learn($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $lines = preg_split("/[a-z]{3,}[\.\?\!] ./", $text);
        foreach ($lines as $line)
            $this->_learn(trim($line));
    }

    private function _learn($line)
    {
        $words = explode(' ', str_replace(array('.', '?', '!', ','), '', $line));

        if (count($words) < 2) return;

        foreach ($words as $pos => $word) {
            if (!isset($this->_words[$word]))
                $this->_words[$word] = array();
            $this->_words[$word][] = array($words, $pos);
        }
    }

    public function generate($line)
    {
        $words = explode(' ', mb_strtolower(str_replace(array('.', '?', '!', ','), '', $line), 'UTF-8'));
        $generated = array($words[array_rand($words)]);

        if (!isset($this->_words[$generated[0]])) return;

        $iterations = 8;
        $range = array(2, 4);

        for ($i = 0; $i < $iterations; $i++) {
            $base = end($generated);

            if (!isset($this->_words[$base])) break;

            $sentence = $this->_words[$base][array_rand($this->_words[$base])];
            for ($j = $sentence[1] + 1, $to = $j + rand($range[0], $range[1]); $j < $to; $j++) {
                if (!isset($sentence[0][$j])) break 2;
                array_push($generated, $sentence[0][$j]);
            }
        }

        for ($i = 0; $i < $iterations; $i++) {
            $base = reset($generated);

            if (!isset($this->_words[$base])) break;
            $sentence = $this->_words[$base][array_rand($this->_words[$base])];

            for ($j = $sentence[1] - 1, $to = $j - rand($range[0], $range[1]); $j > $to; $j--) {
                if (!isset($sentence[0][$j])) break 2;
                array_unshift($generated, $sentence[0][$j]);
            }
        }

        return ucfirst(implode(' ', $generated)) . '.';
    }

    public function loadDictionary($filename)
    {
        $lines = explode("\n", file_get_contents($filename));
        foreach ($lines as $line)
            $this->learn($line);
    }
}