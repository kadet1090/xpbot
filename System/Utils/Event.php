<?php
namespace XPBot\System\Utils;

class Event
{
    /**
     * Arguments types array.
     * @var array
     */
    private $_arguments;

    /**
     * Delegates array.
     * @var callable[int]
     */
    private $_delegates = array();

    /**
     * @param array $arguments Argument what delegate must accept.
     */
    public function __construct($arguments = array())
    {
        $this->_arguments = $arguments;
    }

    /**
     * @param callable $delegate Delegate to run when event is fired.
     *
     * @throws \InvalidArgumentException
     */
    public function add(callable $delegate)
    {
        $this->_delegates[] = $delegate;
    }

    /**
     * @param array $arguments Arguments provided to delegates.
     *
     * @throws \OutOfRangeException
     * @throws \InvalidArgumentException
     */
    public function runArray($arguments)
    {
        if (count($arguments) < count($this->_arguments)) throw new \OutOfRangeException();

        # check argument types
        foreach ($this->_arguments as $no => $type) {
            if (gettype($arguments[$no]) != $type)
                throw new \InvalidArgumentException("Argument $no is not $type.");
        }

        foreach ($this->_delegates as $delegate)
            call_user_func_array($delegate, $arguments);
    }

    /**
     * Run event.
     */
    public function run()
    {
        $this->runArray(func_get_args());
    }

    /**
     * Run event.
     */
    public function __invoke() {
        $this->runArray(func_get_args());
    }
}