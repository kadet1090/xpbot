<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package
 * @license WTFPL
 */

include '../System/Utils/Language.php';
include '../System/functions.php';

class test
{
    public static function foo()
    {
        return \XPBot\System\Utils\Language::get('test2', 'pl');
    }

    public static function bar()
    {
        return \XPBot\System\Utils\Language::get('test1', 'pl');
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
        \XPBot\System\Utils\Language::load('non-existing file');
    }

    public function testPhrases()
    {
        \XPBot\System\Utils\Language::load('Data/Language.xml');

        $this->assertEquals('good', \XPBot\System\Utils\Language::get('test1', 'pl'));
        $this->assertEquals('#default:test2', \XPBot\System\Utils\Language::get('test2', 'pl')); // non-existing phrase in default namespace
    }

    public function testNamespaces()
    {
        $this->assertEquals('very', \XPBot\System\Utils\Language::get('test2', 'pl', 'test'));
    }

    public function testAutoNamespacing()
    {
        $this->assertEquals('very', test::foo());
        $this->assertEquals('good', test::bar());
    }
}
