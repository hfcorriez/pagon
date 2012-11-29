<?php

namespace OmniApp;

class Middleware
{
    const _CLASS_ = __CLASS__;

    /**
     * @var Http\Input|Cli\Input
     */
    protected $input;

    /**
     * @var Http\Output|Cli\Output
     */
    protected $output;

    /**
     * @var App
     */
    protected $app;

    /**
     * @var array Default options
     */
    protected $options = array();

    /**
     * @var \Closure middleware
     */
    protected $_closure;

    /**
     * @var Middleware
     */
    private $_next;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
    }

    /**
     *  Default call
     *
     * @throws \Exception
     * @return bool
     */
    public function call()
    {
        if ($this->_closure) {
            $self = $this;
            call_user_func_array($this->_closure, array($this->input, $this->output, function () use ($self) {
                $self->getNext()->call();
            }));
            return true;
        }
        throw new \Exception(get_called_class() . ' middleware must implements "call" method');
    }

    /**
     * Create with closure
     *
     * @param callable $closure
     * @return Middleware
     */
    final static public function createWithClosure(\Closure $closure)
    {
        $middleware = new self();
        $middleware->setClosure($closure);
        return $middleware;
    }

    /**
     * Set next middleware
     *
     * @param Middleware $middleware
     */
    final public function setNext(Middleware $middleware)
    {
        $this->_next = $middleware;
    }

    /**
     * Only used to create closure middleware
     *
     * @param callable $closure
     * @throws \BadMethodCallException
     */
    final public function setClosure(\Closure $closure)
    {
        if (get_called_class() !== __CLASS__) {
            throw new \BadMethodCallException(__CLASS__ . '::' . __FUNCTION__ . ' only called when caller is the base Middleware');
        }
        $this->_closure = $closure;
    }

    /**
     * Set App
     *
     * @param App $app
     */
    final public function setApp(App $app)
    {
        $this->app = $app;
        $this->input = $app->input;
        $this->output = $app->output;
    }


    /**
     * Get next middleware
     *
     * @return Middleware
     */
    final public function getNext()
    {
        return $this->_next;
    }

    /**
     * Call next
     */
    final protected function next()
    {
        if (!$this->_next) {
            return;
        }
        $this->_next->call();
    }
}
