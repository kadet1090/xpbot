<?php
require_once '../System/Utils/Ini.php';

class IniTest extends PHPUnit_Framework_TestCase {
    public function resetContent()
    {
        file_put_contents('Data/Ini/Test.ini', file_get_contents('Data/Ini/Pattern.ini'));
    }

    public function __construct() {
        $this->resetContent();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNonExistingFile()
    {
        new \XPBot\System\Utils\Ini('non existing file');
    }

    public function testIterator()
    {
        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini');
        $res = array();
        foreach($ini as $key => $value) {
            $res[$key] = $value;
        }

        $this->assertEquals(
            $res,
            array(
                'foo' => 'bar',
                'bar' => 'foo'
            )
        );
    }

    public function testGettingData() {
        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini');
        $this->assertEquals('bar', $ini['foo']);
        $this->assertEquals('foo', $ini['bar']);
    }

    public function testIsset() {
        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini');
        $this->assertTrue(isset($ini['foo']));
        $this->assertFalse(isset($ini['new']));
    }

    public function testUnset() {
        $this->resetContent();

        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini');
        unset($ini['bar']);

        $this->assertFalse(isset($ini['bar']));

        $ini->save();
        $this->assertFileEquals(
            'Data/Ini/Test.ini',
            'Data/Ini/AfterUnset.ini'
        );
    }

    public function testSave() {
        $this->resetContent();

        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini');
        $ini['bar'] = 'bar';
        $ini['new'] = 'blah';

        $this->assertFileEquals(
            'Data/Ini/Test.ini',
            'Data/Ini/Pattern.ini'
        );

        $ini->save();

        $this->assertFileEquals(
            'Data/Ini/Test.ini',
            'Data/Ini/AfterEdit.ini'
        );
    }

    public function testAutoSave() {
        $this->resetContent();

        $ini = new \XPBot\System\Utils\Ini('Data/Ini/Test.ini', true);
        $ini['bar'] = 'bar';
        $ini['new'] = 'blah';

        $this->assertFileEquals(
            'Data/Ini/Test.ini',
            'Data/Ini/AfterEdit.ini'
        );
    }
}
