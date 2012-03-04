<?php

namespace Omni;

require 'app.php';

class Runtime
{
    public static function init(array $configs)
    {
        foreach ($configs as $module => $config)
        {
            $module = '\\' . __NAMESPACE__ . '\\' . ucfirst($module);
            $module::init($config);
        }
    }

    public static function run()
    {
        App::run();
    }
}

Event::on(EVENT_INIT, function(array $config)
{
    iconv_set_encoding("internal_encoding", "UTF-8");
    mb_internal_encoding('UTF-8');
    if ($config['timezone']) date_default_timezone_set($config['timezone']);
    if (!$config['apppath']) throw new \Exception('config["apppath"] must be set before.');
    if ($config['error']) App::register_error_handlers();
});

Event::on(EVENT_RUN, function()
{
    $path = App::$is_cli ? join('/', array_slice($GLOBALS['argv'], 1)) : parse_url($_SERVER['REQUEST_URI'],
                                                                                   PHP_URL_PATH);
    list($controller, $route, $params) = Route::parse($path);

    if (is_string($controller))
    {
        Controller::factory($controller, $params);
    }
    else
    {
        call_user_func_array($controller, $params);
    }
});

Event::on(EVENT_AUTOLOAD, function($class)
{
    $class = ltrim($class, '\\');
    $file = App::$config['classpath'] . '/' . strtolower(str_replace('\\', '/', $class) . '.php');
    is_file($file) && require $file;
});

Event::on(EVENT_EXCEPTION, function($e)
{
    echo $e;
    exit(1);
});

Event::on(EVENT_ERROR, function($type, $message, $file, $line)
{
    if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
});

Event::on(EVENT_SHUTDOWN, function()
{
    if (!App::isInit()) return;

    if (App::$config['error'] &&
        ($error = error_get_last()) &&
        in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
    )
    {
        ob_get_level() and ob_clean();
        try
        {
            print new View(App::$config['route']['error']);
        }
        catch (\Exception $e)
        {
            App::__exception(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
        }
        exit(1);
    }
});


abstract class Model
{
    public static function factory($model)
    {
        return new $model;
    }
}

abstract class Controller
{
    abstract function before();

    abstract function after();

    final public static function factory($controller, $params = array())
    {
        $controller = new $controller();

        $controller->before();
        call_user_func_array(array($controller, 'run'), $params);
        $controller->after();

        return $controller;
    }
}

class View
{
    private $_file = NULL;

    final public static function factory($view)
    {
        return new $view();
    }

    public function __construct($view)
    {
        if (!App::$config['viewpath']) throw new Exception('config["viewpath"] not set.');
        $this->_file = App::$config['viewpath'] . '/' . strtolower(trim($view, '/')) . '.php';
        if (!is_file($this->_file)) throw new Exception('View file not exist: ' . $this->_file);
    }

    public function set($array)
    {
        foreach ($array as $key => $value) $this->$key = $value;
        return $this;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function __toString()
    {
        ob_start();
        extract((array)$this);
        include($this->_file);
        return ob_get_clean();
    }
}
