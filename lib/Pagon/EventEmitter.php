<?php

namespace Pagon;

class EventEmitter extends Fiber
{
    /**
     * @var array Events listeners
     */
    protected $listeners = array();

    /**
     * fire event
     *
     * @static
     * @param string $event
     * @param mixed  $args
     */
    public function emit($event, $args = null)
    {
        $event = strtolower($event);

        if (!empty($this->listeners[$event])) {
            if ($args !== null) {
                // Check arguments, set inline args more than 1
                $args = array_slice(func_get_args(), 1);
            } else {
                $args = array();
            }

            // Loop listeners for callback
            foreach ($this->listeners[$event] as $i => $listener) {
                if ($listener instanceof \Closure) {
                    // Closure Listener
                    call_user_func_array($listener, $args);
                } elseif (is_array($listener) && $listener[0] instanceof \Closure) {
                    // Closure Listener
                    call_user_func_array($listener[0], $args);

                    // Process option
                    $_option = (array)$listener[1];

                    // If emit once
                    if ($_option['once']) {
                        // Remove from listeners
                        unset($this->listeners[$event][$i]);
                    }
                }
            }
        }
    }

    /**
     * Attach a event listener
     *
     * @static
     * @param array|string $event
     * @param \Closure     $listener
     */
    public function on($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->listeners[strtolower($e)][] = $listener;
            }
        } else {
            $this->listeners[strtolower($event)][] = $listener;
        }
    }

    /**
     * Attach a listener to emit once
     *
     * @param array|string $event
     * @param callable     $listener
     */
    public function once($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->listeners[strtolower($e)][] = array($listener, array('once' => true));
            }
        } else {
            $this->listeners[strtolower($event)][] = array($listener, array('once' => true));
        }
    }

    /**
     * Alias for removeListener
     *
     * @param array|string $event
     * @param callable     $listener
     */
    public function off($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->off($e, $listener);
            }
        } else {
            $event = strtolower($event);
            if (!empty($this->listeners[$event])) {
                // Find Listener index
                if (($key = array_search($listener, $event)) !== false) {
                    // Remove it
                    unset($this->listeners[$event][$key]);
                }
            }
        }
    }

    /**
     * Get listeners of given event
     *
     * @param string $event
     * @return array
     */
    public function listeners($event)
    {
        if (!empty($this->listeners[$event])) {
            return $this->listeners[$event];
        }
        return array();
    }

    /**
     * Attach a event listener
     *
     * @static
     * @param array|string $event
     * @param \Closure     $listener
     */
    public function addListener($event, \Closure $listener)
    {
        $this->on($event, $listener);
    }

    /**
     * Detach a event listener
     *
     * @static
     * @param string   $event
     * @param \Closure $listener
     */
    public function removeListener($event, \Closure $listener)
    {
        $this->off($event, $listener);
    }

    /**
     * Remove all listeners of given event
     *
     * @param string $event
     */
    public function removeAllListeners($event)
    {
        $this->listeners[$event] = array();
    }

    /**
     * Remove all of all listeners
     */
    public function removeAllOfAllListeners()
    {
        $this->listeners = array();
    }
}