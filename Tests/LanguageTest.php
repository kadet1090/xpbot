<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

include '../XPBot/Utils/Language.php';
include '../XPBot/functions.php';

class test
{
    public static function foo()
    {
        return \XPBot\Utils\Language::get('test2', 'en');
    }

    public static function bar()
    {
        return \XPBot\Utils\Language::get('test1', 'en');
    }
}

class LanguageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage filename
     */
    public function testNonExistingFile()
    {
        \XPBot\Utils\Language::load('non-existing file');
    }

    public function testPhrases()
    {
        \XPBot\Utils\Language::loadDir('Data/Languages');

        $this->assertEquals('good', \XPBot\Utils\Language::get('test1', 'en'));
        $this->assertEquals('#default:test2', \XPBot\Utils\Language::get('test2', 'en')); // non-existing phrase in default namespace
    }

    public function testNamespaces()
    {
        $this->assertEquals('very', \XPBot\Utils\Language::get('test2', 'en', 'test'));
    }

    public function testAutoNamespacing()
    {
        $this->assertEquals('very', test::foo());
        $this->assertEquals('good', test::bar());
    }

    public function testVariables()
    {
        $this->assertEquals('Hello Jan!', \XPBot\Utils\Language::get('hello', 'en', 'default', array('name' => 'Jan')));
        $this->assertEquals('Hello John!', \XPBot\Utils\Language::get('hello', 'en', 'default', array('name' => 'John')));

        $this->assertEquals('Hello {%name}!', \XPBot\Utils\Language::get('hello', 'en'));
    }

    public function testGlobalVariables()
    {
        $this->assertEquals('The Prompt is {%prompt}.', \XPBot\Utils\Language::get('prompt', 'en'));
        \XPBot\Utils\Language::setGlobalVar('prompt', '!');
        $this->assertEquals('The Prompt is !.', \XPBot\Utils\Language::get('prompt', 'en'));
    }

    public function testMultiLanguages()
    {
        $this->assertEquals('Hello John!', \XPBot\Utils\Language::get('hello', 'en', 'default', array('name' => 'John')));
        $this->assertEquals('Cześć John!', \XPBot\Utils\Language::get('hello', 'pl', 'default', array('name' => 'John')));
    }
}
