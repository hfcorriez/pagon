<?php

namespace OmniApp;

/**
 * Event
 */
class Event
{
    // Events
    protected static $_events = array();

    // Events Listeners
    protected static $_events_listeners = array();

    // Event name
    protected $_name = null;

    // Event Is stop propagation
    private $_stopPropagation = false;

    /**
     * Alias for attach
     *
     * @static
     * @param array|string $name
     * @param \Closure     $listener
     */
    public static function on($name, $listener)
    {
        self::attach($name, $listener);
    }

    /**
     * Attach one or more event
     *
     * @static
     * @param array|string $name
     * @param \Closure     $listener
     */
    public static function attach($name, $listener)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                self::attachEventListener($n, $listener);
            }
        } elseif (is_string($name)) {
            self::attachEventListener($name, $listener);
        }
    }

    /**
     * Alias for detach
     *
     * @param array|string $name
     * @param \Closure     $listener
     */
    public static function off($name, $listener)
    {
        self::detach($name, $listener);
    }

    /**
     * Detach one or more event listener
     *
     * @static
     * @param array|string $name
     * @param \Closure     $listener
     */
    public static function detach($name, $listener)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                self::detachEventListener($n, $listener);
            }
        } elseif (is_string($name)) {
            self::detachEventListener($name, $listener);
        }
    }

    /**
     * Alias for fireEvent
     *
     * @static
     * @internal param $name
     */
    public static function emit()
    {
        call_user_func_array(__CLASS__ . '::fireEvent', func_get_args());
    }

    /**
     * Alias for fireEvent
     *
     * @static
     * @internal param $name
     */
    public static function trigger()
    {
        call_user_func_array(__CLASS__ . '::fireEvent', func_get_args());
    }

    /**
     * Alias for fireEvent
     *
     * @static
     * @internal param $name
     */
    public static function fire()
    {
        call_user_func_array(__CLASS__ . '::fireEvent', func_get_args());
    }

    /**
     * fire event
     *
     * @static
     * @param       $name
     * @param Event $e
     */
    public static function fireEvent($name, $e = null)
    {
        $name = strtolower($name);

        if (!empty(self::$_events_listeners[$name])) {

            // Set default arguments
            $args = array($e);

            // Only trigger when event listeners exists
            if (!$e instanceof Event || !$e) {
                // Get event instance
                $args[0] = $e = self::instance($name);

                if (func_num_args() > 1) {
                    // Check arguments, set inline args more than 1
                    $args += array_slice(func_get_args(), 1, null, true);
                }
            }

            // Loop listeners for callback
            foreach (self::$_events_listeners[$name] as $listener) {
                if (is_object($listener) && $listener instanceof \Closure) {
                    // Closure Listener
                    call_user_func_array($listener, $args);
                } elseif (is_string($listener) && class_exists($listener)) {
                    // Listener name
                    $listener = new $listener();
                    // The Object can be implements `run`
                    if (method_exists($listener, 'run')) call_user_func_array(array($listener, 'run'), $args);
                }
                // Stop propagation
                if ($e->hasStopPropagation()) break;
            }
        }
    }

    /**
     * Attach a event listener
     *
     * @static
     * @param array|string $name
     * @param \Closure     $listener
     */
    protected static function attachEventListener($name, $listener)
    {
        self::$_events_listeners[strtolower($name)][] = $listener;
    }

    /**
     * Detach a event listener
     *
     * @static
     * @param $name
     * @param $listener
     */
    protected static function detachEventListener($name, $listener)
    {
        $name = strtolower($name);
        if (!empty(self::$_events_listeners[$name])) {
            // Find Listener index
            if (($key = array_search($listener, $name)) !== false) {
                // Remove it
                unset(self::$_events_listeners[$name][$key]);
            }
        }
    }

    /**
     * Instance event by name
     *
     * @param string $name
     * @return Event
     */
    public static function instance($name)
    {
        if (!isset(self::$_events[$name])) {
            self::$_events[$name] = self::factory($name);
        }
        return self::$_events[$name];
    }

    /**
     * Create new Event
     *
     * @static
     * @param string $name
     * @return Event
     */
    public static function factory($name = null)
    {
        $class = get_called_class();
        return new $class($name);
    }

    /**
     * Create event
     *
     * @param       $name
     */
    public function __construct($name = null)
    {
        if (!$this->_name && $name) $this->_name = $name;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Setter for event
     *
     * @param      $name
     * @param null $value
     * @return Event
     */
    public function set($name, $value = null)
    {
        if (is_array($name) && $value = null) {
            foreach ($name as $k => $v) {
                // Protect private and protected property
                if ($k{0} != '_') $this->{$k} = $v;
            }
        } elseif (is_string($name)) {
            // Protect private and protected property
            if ($name{0} != '_') $this->{$name} = $value;
        }
        return $this;
    }

    /**
     * Getter for event
     *
     * @param      $name
     * @param null $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if ($name{0} == '_' || !property_exists($this, $name)) return $default;

        return $this->{$name};
    }

    /**
     * Is Event stop propagation
     *
     * @return bool
     */
    public function hasStopPropagation()
    {
        return $this->_stopPropagation;
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation()
    {
        $this->_stopPropagation = true;
    }
}