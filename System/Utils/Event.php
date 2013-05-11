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
     * @var array[int]\XPBot\System\Utils\Delegate
     */
    private $_delegates = array();

    /**
     * @param array $arguments
     */
    public function __construct($arguments = array())
    {
        $this->_arguments = $arguments;
    }

    /**
     * @param Delegate $delegate
     * @throws \InvalidArgumentException
     */
    public function add(Delegate $delegate)
    {
        if (!$delegate->acceptParams($this->_arguments)) throw new \InvalidArgumentException('That delegate don\'t accept this event arguments.');

        $this->_delegates[] = $delegate;
    }

    /**
     * @param $arguments
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
            $delegate->runArray($arguments);
    }

    /**
     * Runs event.
     */
    public function run()
    {
        $this->runArray(func_get_args());
    }
}