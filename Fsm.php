<?php

/**
 * Class Fsm
 * @package Sidfate
 */
class Fsm
{
    /**
     * Current state
     * @var string
     */
    protected $state = '';

    /**
     * Events collection
     * @var array
     */
    protected $events = [];

    /**
     * Events method
     * @var array
     */
    protected $methods = [];

    /**
     * All states
     * @var array
     */
    protected $states = [];

    /**
     * All transitions
     * @var array
     */
    protected $transitions = [];

    /**
     * State transitions history
     * @var array
     */
    protected $history = [];

    /**
     * Arbitrary data
     * @var array
     */
    protected $data = [];

    /**
     * Factory option
     * @var mixed|null
     */
    protected static $option = null;

    /**
     * Factory for creating the same option Fsm object
     * @param  $option
     * @return string
     */
    public static function factory($option)
    {
        static::$option = $option;
        return __CLASS__;
    }


    /**
     * Fsm constructor.
     * @param $data
     */
    public function __construct($creator = null)
    {
        if($creator === null) {
            $creator = static::$option;
        }
        $init = $this->arrGet($creator, 'init', '');
        $data = $this->arrGet($creator, 'data', []);
        $events = $this->arrGet($creator, 'events', []);

        $this->initialize($init);
        $this->setEvents($events);
        $this->setData($data);
    }

    /**
     * Initialize state
     * @param  $init
     */
    protected function initialize($init)
    {
        $this->state = $init;
        $this->history[] = $init;
    }

    /**
     * Set events
     * @param $events
     */
    protected function setEvents($events)
    {
        foreach ($events as $event) {
            $from = $this->arrGet($event, 'from');
            $to = $this->arrGet($event, 'to');
            $eName = $this->arrGet($event, 'name');

            if($from && $to && $eName) {
                $this->transitions[] = $eName;
                if(is_array($from)) {
                    foreach ($from as $f) {
                        $this->events[$f][$to] = $eName;
                        $this->states[] = $f;
                    }
                    $this->states[] = $to;
                    continue;
                }
                if(is_array($to)) {
                    foreach ($to as $t) {
                        $this->events[$from][$t] = $eName;
                        $this->states[] = $t;
                    }
                    $this->states[] = $from;
                    continue;
                }
                
                $this->states[] = $from;
                $this->states[] = $to;
                $this->events[$from][$to] = $eName;
            }
        }

        $this->transitions = array_values(array_unique($this->transitions));
        $this->states = array_values(array_unique($this->states));
    }

    /**
     * Set data
     * @param array
     */
    protected function setData($data) {
        foreach ($data as $key => $value) {
            if(!is_numeric($key)) {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Get the array value
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function arrGet($array, $key, $default = null)
    {
        if(is_array($array) && isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * State transform
     * @param $event
     */
    protected function transform($event)
    {
        $from = $this->state;
        if(!isset($this->events[$from])) {
            return ;
        }
        $to = array_search($event, $this->events[$from]);

        if($to) {
            $method = $this->arrGet($this->methods, $event);
            $method && call_user_func($method);
            $this->state = $to;
            $this->history[] = $to;
        }
    }

    /**
     * Search the event from the current state to the given state
     * @param  $to
     * @return mixed|null
     */
    protected function searchEvent($to)
    {
        $from = $this->state;

        if(isset($this->events[$from]) && isset($this->events[$from][$to])) {
            return $this->events[$from][$to];
        }

        return null;
    }

    /**
     * Bind method on event
     * @param $name
     * @param \Closure $closure
     */
    public function on($name, \Closure $closure)
    {
        $this->methods[$name] = $closure->bindTo($this, __CLASS__);
    }

    /**
     * Get the current state
     * @return mixed|string
     */
    public function now()
    {
        return $this->state;
    }

    /**
     * Get history log
     * @return array
     */
    public function log()
    {
        return $this->history;
    }

    /**
     * Judge if the current state can transform to the given state
     * @param  $to
     * @return boolean
     */
    public function can($to)
    {
        return $this->searchEvent($to) ? true : false;
    }

    /**
     * Judge if the given state is the current state
     * @param  $state
     * @return boolean
     */
    public function is($state)
    {
        return $state === $this->state ? true : false;
    }

    /**
     * Get all possible transitions from the current state
     * @return array
     */
    public function trans()
    {
        $trans = [];
        $from = $this->state;
        foreach ($this->events as $eName=> $event) {
            if($from == $event['from']) {
                $trans[] = $eName;
            }
        }

        return $trans;
    }

    /**
     * Get all transitions
     * @return array
     */
    public function allTrans()
    {
        return $this->transitions;
    }

    /**
     * Get all states
     * @return array
     */
    public function allStates()
    {
        return $this->states;
    }

    /**
     * Call Function
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $function = substr($name, 0, 2);
        if($function == 'on') {
            $funName = strtolower(substr($name, 2));
            $this->on($funName, $arguments[0]);
        }else {
            $this->transform($name);
        }
    }

    /**
     * Get data
     * @param  $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }
    }
}