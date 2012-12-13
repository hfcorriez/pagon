<?php

namespace OmniApp;

class Route extends Middleware
{
    // Method to call
    private $_call;

    /**
     * Create new controller
     *
     * @param string|\Closure $route
     * @return bool|Route
     */
    public static function createWith($route)
    {
        if ($route instanceof \Closure) {
            // Closure call
            return self::createWithClosure($route);
        } elseif (is_string($route)) {
            if (strpos($route, '::')) {
                // Controller\Abc::method
                list($_class,) = explode('::', $route);

                // Check controller if base Controller
                if ($_class !== __CLASS__ && is_subclass_of($_class, __CLASS__)) {
                    // Construct new controller and setCall
                    return self::createWithClosure(function () use ($route) {
                        call_user_func_array($route, func_get_args());
                    });
                }
            } elseif (strpos($route, '->')) {
                // Controller\Abc->method
                list($_class, $_method) = explode('->', $route);

                // Check controller if base Controller
                if ($_class !== __CLASS__ && is_subclass_of($_class, __CLASS__)) {
                    // Construct new controller and setCall
                    $route = new $_class();
                    $route->setCall($_method);
                    return $route;
                }
            } elseif (is_subclass_of($route, __CLASS__, true)) {
                // Only Class name
                $route = new $route();
                $route->setCall('run');
                return $route;
            }
        }
        return false;
    }

    /**
     * abstract before run
     *
     * @abstract
     */
    function before()
    {
        // Implements as you like
    }

    /**
     * abstract after run
     *
     * @abstract
     */
    function after()
    {
        // Implements as you like
    }

    /**
     * Default run
     */
    function run()
    {
        if ($this->_closure) {
            // Callable will run as the following
            $self = $this;
            call_user_func_array($this->_closure, array($this->input, $this->output, function () use ($self) {
                $self->getNext()->call();
            }));
        }
        // Controller Object must have "run" method
        throw new \Exception('Controller "' . get_called_class() . '" must implements "run" method');
    }

    /**
     *  Default call
     */
    public function call()
    {
        if ($this->_call) {
            // Controller flow
            $this->before();
            $this->{$this->_call}();
            $this->after();
        } else {
            // Callable flow
            $this->run();
        }
    }

    /**
     * Set method for call
     *
     * @param $method
     */
    final public function setCall($method)
    {
        $this->_call = $method;
    }
}

