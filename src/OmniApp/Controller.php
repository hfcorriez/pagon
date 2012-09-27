<?php

namespace OmniApp;

abstract class Controller
{
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
     * @param       $controller
     * @param array $params
     * @return mixed
     */
    final public static function factory($controller, $params = array())
    {
        $controller = new $controller();

        $controller->before();
        call_user_func_array(array($controller, 'run'), $params);
        $controller->after();

        return $controller;
    }
}

