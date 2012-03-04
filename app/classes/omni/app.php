<?php
/**
 * OmniApp Framework
 *
 * @package OmniApp
 * @author Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license http://www.apache.org/licenses/LICENSE-2.0
 *
 */

namespace Omni;

const VERSION = '0.1';

const EVENT_INIT = 'init';
const EVENT_RUN = 'run';
const EVENT_ERROR = 'error';
const EVENT_EXCEPTION = 'exception';
const EVENT_SHUTDOWN = 'shutdown';
const EVENT_AUTOLOAD = 'autoload';

/**
 * App Class
 */
class App
{
    public static $config;

    public static $is_cli;
    public static $is_win;
    public static $start_time;
    public static $start_memory;

    private static $_init = false;

    public static function init($config = array())
    {
        self::$is_cli = PHP_SAPI == 'cli';
        self::$is_win = substr(PHP_OS, 0, 3) == 'WIN';
        self::$start_time = $_SERVER['REQUEST_TIME'];
        self::$start_memory = memory_get_usage();
        self::$config = $config;
        self::$_init = true;
        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));
        Event::add(EVENT_INIT, $config);
    }

    public static function isInit()
    {
        return self::$_init;
    }

    public static function run()
    {
        Event::add(EVENT_RUN);
    }

    public static function register_error_handlers()
    {
        set_error_handler(array(__CLASS__, '__error'));
        set_exception_handler(array(__CLASS__, '__exception'));
    }

    public static function restore_error_handlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public static function __autoload($class_name)
    {
        Event::add(EVENT_AUTOLOAD, $class_name);
    }

    public static function __error($type, $message, $file, $line)
    {
        Event::add(EVENT_ERROR, $type, $message, $file, $line);
    }

    public static function __exception(\Exception $e)
    {
        Event::add(EVENT_EXCEPTION, $e);
    }

    public static function __shutdown()
    {
        Event::add(EVENT_SHUTDOWN);
    }
}

class ArrayObject extends \ArrayObject
{
    public function __set($name, $val)
    {
        $this[$name] = $val;
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this)) $ret = &$this[$name];
        else $ret = null;
        return $ret;
    }

    public function __isset($name)
    {
        return isset($this[$name]);
    }

    public function __unset($name)
    {
        unset($this[$name]);
    }
}

class Instance
{
    private static $_instance = array();

    public static function instance()
    {
        $class = get_called_class();
        if (!isset(self::$_instance[$class])) self::$_instance[$class] = new $class();
        return self::$_instance[$class];
    }
}

abstract class Event
{
    private static $_events = array();

    public static function init($config)
    {
        foreach ($config as $name => $runners)
        {
            self::$_events[$name] = array_merge(self::$_events[$name], $runners);
        }
    }

    public static function on($name, $runner)
    {
        self::$_events[$name][] = $runner;
    }

    public static function add($name)
    {
        $params = array_slice(func_get_args(), 1);
        if (!empty(self::$_events[$name]))
        {
            foreach (self::$_events[$name] as $runner)
            {
                self::_excute($runner, $params);
            }
        }
    }

    private static function _excute($runner, $params = array())
    {
        if (is_string($runner))
        {
            $event = new $runner();
            $event->run();
        }
        else
        {
            $a = call_user_func_array($runner, $params);
        }
    }

    abstract function run();
}