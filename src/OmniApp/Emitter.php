<?php

namespace OmniApp;

class Emitter
{
    /**
     * @var array Events listeners
     */
    protected $_listeners = array();

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

        if (!empty($this->_listeners[$event])) {
            if ($args !== null) {
                // Check arguments, set inline args more than 1
                $args = array_slice(func_get_args(), 1);
            } else {
                $args = array();
            }

            // Loop listeners for callback
            foreach ($this->_listeners[$event] as $listener) {
                // Closure Listener
                call_user_func_array($listener, $args);
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
        $this->_listeners[strtolower($event)][] = $listener;
    }

    /**
     * Detach a event listener
     *
     * @static
     * @param string   $event
     * @param \Closure $listener
     */
    public function off($event, \Closure $listener)
    {
        $event = strtolower($event);
        if (!empty($this->_listeners[$event])) {
            // Find Listener index
            if (($key = array_search($listener, $event)) !== false) {
                // Remove it
                unset($this->_listeners[$event][$key]);
            }
        }
    }

}