<?php

namespace OmniApp;

abstract class Middleware
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
     * @var Middleware
     */
    protected $next;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
    }

    /**
     * Create new controller
     *
     * @param string|\Closure $route
     * @return bool|Route
     */
    public static function build($route, $options = array())
    {
        if (is_string($route) && is_subclass_of($route, __CLASS__, true)) {
            // Only Class name
            return new $route($options);
        } elseif (is_object($route)) {
            return $route;
        }
        return false;
    }

    /**
     * @return mixed
     */
    abstract function call();

    /**
     * @param $input
     * @param $output
     * @param $next
     */
    public function __invoke($input, $output, $next)
    {
        $this->input = $input;
        $this->output = $output;
        $this->app = $input->app;
        $this->next = $next;
        $this->call();
    }

    /**
     *
     */
    public function next()
    {
        call_user_func($this->next);
    }
}
