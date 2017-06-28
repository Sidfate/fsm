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
     * State transitions history
     * @var array
     */
    protected $history = [];

    /**
     * Fsm constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $init = $this->arrGet($data, 'init', '');
        $events = $this->arrGet($data, 'events', []);

        $this->initialize($init);
        $this->setEvents($events);
    }

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
            $item = [
                'from'=> $event['from'],
                'to'=> $event['to'],
                'method'=> null
            ];
            $this->events[$event['name']] = $item;
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
     * @param $to
     */
    protected function transform($to)
    {
        $event = $this->searchEvent($to);

        if($event) {
            $method = $this->arrGet($event, 'method');
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
        foreach ($this->events as $event) {
            if($from == $event['from'] && $to == $event['to']) {
                return $event;
            }
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
        $this->events[$name]['method'] = $closure->bindTo($this, __CLASS__);
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
            if(isset($this->events[$name])) {
                $this->transform($this->events[$name]['to']);
            }
        }
    }
}