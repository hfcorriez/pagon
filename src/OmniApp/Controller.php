<?php

namespace OmniApp;

use OmniApp\Exception\Pass;

class Controller
{
    /**
     * @var \OmniApp\Http\Input|\OmniApp\Cli\Input
     */
    protected $input;

    /**
     * @var \OmniApp\Http\Output|\OmniApp\Cli\Output
     */
    protected $output;

    /*
     * @var Controller
     */
    protected $next;

    /**
     * @var callable controller
     */
    private $_origin;

    /**
     * @var string Method for call
     */
    private $_call;

    /**
     * abstract before run
     *
     * @abstract

     */
    function before()
    {
    }

    /**
     * abstract after run
     *
     * @abstract

     */
    function after()
    {
    }

    /**
     * Default run
     */
    function run()
    {
        if ($this->_origin) {
            // Callable will run as the following
            $self = $this;
            call_user_func_array($this->_origin, array($this->input, $this->output, function () use ($self) {
                $self->getNext()->call();
            }));
        } else {
            // Controller Object must have "run" method
            throw new \Exception('Controller "' . get_called_class() . '" must implements "run" method');
        }
    }

    /**
     * Factory new controller
     *
     * @param callable|\Closure $ctrl
     * @return bool|Controller
     */
    public static function factory($ctrl)
    {
        if ($ctrl && is_callable($ctrl)) {
            // array($obj, 'method') array('Ojb', 'method')
            if (is_array($ctrl)) {
                if (is_string($ctrl[0])) {
                    // Check Class string if base Controller
                    if ($ctrl[0] !== __CLASS__ && is_subclass_of($ctrl[0], __CLASS__)) {
                        // Construct new controller and setCall
                        $ctrl = new $ctrl[0]();
                        $ctrl->setCall($ctrl[1]);
                        return $ctrl;
                    }
                } elseif (is_object($ctrl[0]) && is_subclass_of($ctrl[0], __CLASS__)) {
                    // If Object base Controller, set method to it
                    $ctrl[0]->setCall($ctrl[1]);
                    return $ctrl[0];
                }
            } elseif (is_string($ctrl) && strpos($ctrl, '::')) {
                // Controller\Abc::method
                list($_class, $_method) = explode('::', $ctrl);

                // Check controller if base Controller
                if ($_class !== __CLASS__ && is_subclass_of($_class, __CLASS__)) {
                    // Construct new controller and setCall
                    $ctrl = new $_class();
                    $ctrl->setCall($_method);
                    return $ctrl;
                }
            }

            // Construct callable controller
            return new self($ctrl);
        } elseif (is_string($ctrl) && is_subclass_of($ctrl, __CLASS__)) {
            // Only Class name
            $ctrl = new $ctrl();
            $ctrl->setCall('run');
            return $ctrl;
        }
        return false;
    }

    /**
     * @param \Closure|callable $controller
     */
    public function __construct($controller = null)
    {
        if ($controller && is_callable($controller)) $this->_origin = $controller;
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
     * Set next controller
     *
     * @param Controller               $controller
     */
    final public function setNext(Controller $controller)
    {
        $this->next = $controller;
    }

    /**
     * @param App $app
     */
    final public function setApp(App $app)
    {
        $this->input = $app->input;
        $this->output = $app->output;
    }

    /**
     * Get next controller
     *
     * @return Controller
     */
    final public function getNext()
    {
        return $this->next;
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

    /**
     * Call next
     */
    final protected function next()
    {
        if ($this->next) {
            $this->next->call();
        } else {
            throw new Pass();
        }
    }
}

