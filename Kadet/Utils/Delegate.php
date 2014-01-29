<?php
namespace Kadet\Utils;

/**
 * Class Delegate
 * @package XPBot\System\Utils
 * @deprecated
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
     * Acceptable argument types array.
     * @var array
     */
    private $_arguments;

    /**
     * Strict mode.
     * @var bool
     */
    private $_strict;

    /**
     * @param callable $callback  Function to be called by delegate.
     * @param array    $arguments Accepted arguments list.
     * @param bool     $strict    Strict mode.
     */
    public function __construct($callback, array $arguments = array(), $strict = false)
    {
        $this->setCallback($callback);
        $this->_arguments = $arguments;
        $this->_strict    = $strict;
    }

    /**
     * Run delegate with parameters provided by array.
     *
     * @param array $arguments
     *
     * @throws \RuntimeException
     * @throws \OutOfRangeException
     * @throws \InvalidArgumentException
     *
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
     * Run delegate with given arguments.
     */
    public function run()
    {
        return $this->runArray(func_get_args());
    }

    /**
     * Set callback function.
     *
     * @param callable|null $callback Function to be called by delegate.
     * @throws \InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback) && $callback != null) throw new \InvalidArgumentException('callback');
        $this->_callback = $callback;
    }

    /**
     * Set accepted params.
     *
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