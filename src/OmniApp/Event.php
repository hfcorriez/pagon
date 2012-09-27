<?php

namespace OmniApp;

abstract class Event
{
    const INIT = 'INIT';
    const RUN = 'RUN';
    const END = 'END';
    const ERROR = 'ERROR';
    const SHUTDOWN = 'SHUTDOWN';

    protected $_events = array();

    /**
     * Register event on $name
     *
     * @static
     * @param $name
     * @param $runner
     */
    public static function on($name, $runner)
    {
        App::$config['event'][$name][] = $runner;
    }

    /**
     * Add event trigger
     *
     * @static
     * @param $name
     */
    public static function fire($name)
    {
        $params = array_slice(func_get_args(), 1);
        if (!empty(App::$config['event'][$name])) {
            foreach (App::$config['event'][$name] as $runner) {
                self::execute($runner, $params);
            }
        }
    }

    /**
     * Execute runner for event point
     *
     * @static
     * @param       $runner
     * @param array $params
     */
    private static function execute($runner, $params = array())
    {
        if (is_string($runner)) {
            call_user_func_array(array(new $runner(), 'run'), $params);
        } else {
            call_user_func_array($runner, $params);
        }
    }

    /**
     * Run method for event runner object
     *  if u register a class name for runner, u must implements run method.
     *
     * @abstract

     */
    abstract function run();
}