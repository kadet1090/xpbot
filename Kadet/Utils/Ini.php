<?php
/**
 * Created by JetBrains PhpStorm.
 *
 * @author Kadet <kadet1090@gmail.com>
 * @package 
 * @license WTFPL
 */

namespace Kadet\Utils;

use Traversable;

class Ini implements \ArrayAccess, \IteratorAggregate {
    private $_data;
    private $_filename;

    /**
     * Auto save.
     * If set to true, file will be saved when any changes occurs.
     * @var bool
     */
    public $autoSave;

    /**
     * @param string $filename Ini file name.
     * @param bool   $autoSave If set to true, file will be saved when any changes occurs.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($filename, $autoSave = false) {
        $this->autoSave = $autoSave;
        $this->_filename = $filename;

        if(!file_exists($this->_filename)) throw new \InvalidArgumentException('filename');

        $this->_data = parse_ini_file($this->_filename);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if(is_serialized($this->_data[$offset]))
            $this->_data[$offset] = unserialize($this->_data[$offset]);

        return $this->_data[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
        if($this->autoSave)
            $this->save();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
        if($this->autoSave)
            $this->save();
    }

    /**
     * Saves ini file.
     */
    public function save() {
        $str = '';
        foreach($this->_data as $key => $value)
            $str .= $key . ' = "' . (is_array($value) || is_object($value) ? serialize($value) : $value) . '"' . PHP_EOL;

        file_put_contents($this->_filename, trim($str));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_data);
    }

    /**
     * Get data as array.
     *
     * @return array Data as array.
     */
    public function asArray()
    {
        return $this->_data;
    }
}