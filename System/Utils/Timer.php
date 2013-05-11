<?php
namespace XPBot\System\Utils;

class Timer
{
    /**
     * Last run time.
     * @var int
     */
    protected $_last;

    /**
     * Callback to function.
     * @var callable
     */
    protected $_callback;

    /**
     * Callback params.
     * @var array
     */
    protected $_params;

    /**
     * Timer interval.
     * @var int
     */
    public $interval;

    /**
     * @var bool
     */
    private $active = true;

    /**
     * @var bool
     */
    public $oneTime = false;

    /**
     * @var array[]Timer
     */
    private static $_timers = array();

    /**
     * @param int $interval
     * @param callable $function
     * @param array $params
     */
    public function __construct($interval, $function, array $params = array())
    {
        $this->interval = $interval;
        $this->_func    = $function;
        $this->_params  = $params;
        $this->_last    = time();

        self::$_timers[] = $this;
    }

    /**
     * Timer tick.
     */
    public function tick()
    {
        if (!$this->active) return;

        if (time() - $this->_last >= $this->interval) {
            call_user_func_array($this->_func, $this->_params);
            $this->_last = time();

            if ($this->oneTime) $this->stop();
        }
    }

    public static function update()
    {
        foreach (self::$_timers as $timer)
            $timer->tick();
    }

    public function start()
    {
        $this->active = true;
    }

    public function stop()
    {
        $this->active = false;
    }

    public function __destruct()
    {
        foreach (self::$_timers as $id => $timer) {
            if($timer == $this)
                unset(self::$_timers[$id]);
        }
    }
}

?>
