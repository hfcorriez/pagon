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
    /**
     * App config
     * @var
     */
    public static $config;

    // is init?
    private static $_init = false;

    /**
     * App init
     * @static
     * @param array $config
     */
    public static function init($config = array())
    {
        self::$config = $config;
        self::$_init = true;
        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));
        Event::add(EVENT_INIT, $config);
    }

    /**
     * Is init?
     *
     * @static
     * @return bool
     */
    public static function isInit()
    {
        return self::$_init;
    }

    /**
     * App run
     *
     * @static
     *
     */
    public static function run()
    {
        Event::add(EVENT_RUN);
    }

    /**
     * Register error and exception handlers
     *
     * @static
     *
     */
    public static function register_error_handlers()
    {
        set_error_handler(array(__CLASS__, '__error'));
        set_exception_handler(array(__CLASS__, '__exception'));
    }

    /**
     * Restore error and exception hanlders
     *
     * @static
     *
     */
    public static function restore_error_handlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Auto load class
     *
     * @static
     * @param $class
     */
    public static function __autoload($class)
    {
        Event::add(EVENT_AUTOLOAD, $class);
    }

    /**
     * Error handler for app
     *
     * @static
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    public static function __error($type, $message, $file, $line)
    {
        Event::add(EVENT_ERROR, $type, $message, $file, $line);
    }

    /**
     * Exception handler for app
     *
     * @static
     * @param \Exception $e
     */
    public static function __exception(\Exception $e)
    {
        Event::add(EVENT_EXCEPTION, $e);
    }

    /**
     * Shutdown handler for app
     *
     * @static
     *
     */
    public static function __shutdown()
    {
        Event::add(EVENT_SHUTDOWN);
    }
}

/**
 * Omni ArrayObject implements object get and set.
 */
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

/**
 * Base instance
 */
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

/**
 * Event
 */
abstract class Event
{
    /**
     * @var array events list
     */
    private static $_events = array();

    /**
     * Event init for event list
     *
     * @static
     * @param $config
     */
    public static function init($config)
    {
        foreach ($config as $name => $runners)
        {
            self::$_events[$name] = array_merge(self::$_events[$name], $runners);
        }
    }

    /**
     * Register event on $name
     *
     * @static
     * @param $name
     * @param $runner
     */
    public static function on($name, $runner)
    {
        self::$_events[$name][] = $runner;
    }

    /**
     * Add event trigger
     *
     * @static
     * @param $name
     */
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

    /**
     * Excute runner for event point
     *
     * @static
     * @param $runner
     * @param array $params
     */
    private static function _excute($runner, $params = array())
    {
        if (is_string($runner))
        {
            $event = new $runner();
            $event->run();
        }
        else
        {
            call_user_func_array($runner, $params);
        }
    }

    /**
     * Run method for event runner object
     *
     *  if u reigseter a class name for runner, u must implements run method.
     *
     * @abstract
     *
     */
    abstract function run();
}