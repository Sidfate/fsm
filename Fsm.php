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
     * Life cycle observes
     * @var array
     */
    protected $observes = [];

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
     * Observe const.
     */
    const observeBefore = "before";
    const observeLeave = "leave";
    const observeEnter = "enter";
    const observeAfter = "after";

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
            /**
             * Life cycle 
             * 
             * onBeforeTransition 
             * onBefore[Event]
             * onLeaveState
             * onLeave[From]
             * onEnter[To]          = on[To]
             * onEnterState       
             * onAfter[Event]       = on[Event]
             * onAfterTransition 
             */
            $this->dispatch('transition', static::observeBefore);
            $this->dispatch($event, static::observeBefore);
            $this->dispatch('state', static::observeLeave);
            $this->dispatch($from, static::observeLeave);
            $this->state = $to;
            $this->history[] = $to;
            $this->dispatch($to, static::observeEnter);
            $this->dispatch('state', static::observeEnter);
            $this->dispatch($event, static::observeAfter);  
            $this->dispatch('transition', static::observeAfter);           
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
     * Observe the life cycle
     * @param $name
     * @param \Closure $closure
     */
    public function observe($name, \Closure $closure)
    {
        $bind = $closure->bindTo($this, __CLASS__);

        preg_match("/^on([A-Z][a-z]*)(.*)/", $name, $matches);
        if(!$matches) {
            return ;
        }
        
        $matches = array_slice($matches, 1);
        array_walk($matches, function(&$item) {
            $item = strlen($item) > 1  ? strtolower($item) : $item;
        });
        if(!$matches[1]) {
            $target = $matches[0];
            if(in_array($target, $this->transitions)) {
                $status = static::observeAfter;
            }else if(in_array($target, $this->states)) {
                $status = static::observeEnter;
            }else {
                return ;
            }
        }else {
            list($status, $target) = $matches;
        }

        $this->observes[$status][$target] = $bind;
    }

    /**
     * Dispatch observe action
     * @param  $name
     * @param  $status
     */
    public function dispatch($name, $status)
    {
        if(isset($this->observes[$status][$name])) {
            $method = $this->observes[$status][$name];
            $method && call_user_func($method);
        }
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
        $from = $this->state;
        if(!isset($this->events[$from])) {
            return null;
        }

        return array_keys($this->events[$from]);
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
        $sign = substr($name, 0, 2);
        if($sign == 'on') {
            $this->observe($name, $arguments[0]);
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