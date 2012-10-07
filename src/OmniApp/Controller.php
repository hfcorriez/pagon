<?php

namespace OmniApp;

abstract class Controller
{
    /**
     * @var \OmniApp\Http\Request|Null
     */
    protected $request;

    /**
     * @var \OmniApp\Http\Response|\OmniApp\CLI\Output
     */
    protected $response;

    /**
     * abstract before run
     *
     * @abstract

     */
    abstract function before();

    /**
     * abstract after run
     *
     * @abstract

     */
    abstract function after();

    /**
     * Factory a controller
     *
     * @static
     * @param string|array $controller
     * @param array        $io
     * @param array        $args
     * @return mixed
     */
    final public static function factory($controller, $io, $args = array())
    {
        $method = 'run';

        if (strpos($controller, '::')) {
            // Support `Web\Index::post`
            list($controller, $method) = explode('::', $controller);
        } elseif (is_array($controller)) {
            // Support array('Web\Index', 'post')
            list($controller, $method) = $controller;
        }

        // Check controller is base Controller
        if ($controller = self::valid($controller)) {
            $controller = new $controller($io[0], $io[1]);

            $controller->before();
            call_user_func_array(array($controller, $method), $args);
            $controller->after();
            return $controller;
        }

        return false;
    }

    /**
     * Valid if class base controller
     *
     * @param string $class
     * @return bool|string
     */
    final public static function valid($class)
    {
        if (is_string($class) && is_subclass_of($class, __CLASS__)) {
            return $class;
        }
        return false;
    }
}

