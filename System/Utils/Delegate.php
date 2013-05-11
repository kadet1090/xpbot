<?php
namespace XPBot\System\Utils;

/**
 * Class Delegate
 * @package XPBot\System\Utils
 * @author Kadet <kadet1090@gmail.com>
 */
class Delegate
{
    /**
     * Callback.
     * @var callable|null
     */
    private $_callback;

    /**
     * Arguments types array.
     * @var array
     */
    private $_arguments;

    /**
     * @var bool
     */
    private $_strict;

    /**
     * @param callable $callback
     * @param array $arguments
     * @param bool $strict
     */
    public function __construct($callback, array $arguments = array(), $strict = false)
    {
        $this->setCallback($callback);
        $this->_arguments = $arguments;
        $this->_strict    = $strict;
    }

    /**
     * @param $arguments array
     * @throws \RuntimeException
     * @throws \OutOfRangeException
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function runArray(array $arguments)
    {
        if (
            (count($arguments) < count($this->_arguments) && !$this->_strict) &&
            (count($arguments) != count($this->_arguments) && $this->_strict)
        ) throw new \OutOfRangeException();

        # check argument types
        foreach ($this->_arguments as $no => $type) {
            if (gettype($arguments[$no]) != $type)
                throw new \InvalidArgumentException("Argument $no is not $type, is a " . gettype($arguments[$no]));
        }

        # is callback callable?
        if (!is_callable($this->_callback))
            throw new \RuntimeException('Given function is not valid callback.');

        return call_user_func_array($this->_callback, $arguments);
    }

    /**
     * Runs delegate with given arguments.
     */
    public function run()
    {
        return $this->runArray(func_get_args());
    }

    /**
     * @param $callback
     * @throws \InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback) && $callback != null) throw new \InvalidArgumentException('callback');
        $this->_callback = $callback;
    }

    /**
     * @param array $types
     * @return bool
     */
    public function acceptParams(array $types)
    {
        if ($this->_strict)
            return $this->_arguments == $types;
        else
            return !count(array_diff($this->_arguments, $types));
    }
}