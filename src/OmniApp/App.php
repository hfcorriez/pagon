<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace OmniApp;

const VERSION = '0.1';

/*********************
 * core app
 ********************/

/**
 * App Class
 */
class App
{
    public static $config;
    public static $modules = array();

    private static $start_time = null;

    private static $_init = false;

    /**
     * App init
     *
     * @static
     * @param array $config
     */
    public static function init($config = array())
    {
        Event::fire(Event::INIT, $config);
        foreach (array('route', 'event') as $m) {
            if (!isset($config[$m])) $config[$m] = array();
        }

        self::$config = $config;

        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));

        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        if (!empty($config['timezone'])) date_default_timezone_set($config['timezone']);
        if (!isset($config['error']) || $config['error'] === true) self::registerErrorHandler();

        self::$start_time = microtime(true);

        self::$_init = true;
    }

    /**
     * Load Modules
     *
     * @static
     * @param array $modules
     */
    public static function modules($modules)
    {
        foreach ($modules as $k=> $v) {
            $config = null;
            if (is_int($k)) {
                $module = $v;
            } else {
                $module = $k;
                $config = $v;
            }
            if ($module{0} != '\\') $module = __NAMESPACE__ . '\\' . $module;
            if (in_array($module, self::$modules)) continue;
            // Module must implements static init function.
            $module::init($config);
            self::$modules[] = $module;
        }
    }

    /**
     * Config get or set after init
     *
     * @param      $key
     * @param null $value
     * @return null
     */
    public static function config($key, $value = null)
    {
        if ($value === null) {
            return self::$config[$key];
        } else {
            return self::$config[$key] = $value;
        }
    }

    /**
     * Check if cli
     *
     * @static
     * @return bool
     */
    public static function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * Check if windows, if not, it must be *unix
     *
     * @static
     * @return bool
     */
    public static function isWin()
    {
        static $is_win = null;
        if ($is_win === null) {
            $is_win = substr(PHP_OS, 0, 3) == 'WIN';
        }
        return $is_win;
    }

    /**
     * Get app start time
     *
     * @static
     * @return int
     */
    public static function startTime()
    {
        return self::$start_time;
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

     */
    public static function run()
    {
        Event::fire(Event::RUN);
        $path = self::isCli() ? join('/', array_slice($GLOBALS['argv'], 1)) : Http\Request::path();
        list($controller, $route, $params) = Route::parse($path);

        if (is_string($controller)) {
            Controller::factory($controller, $params);
        } else {
            call_user_func_array($controller, $params);
        }
        Event::fire(Event::END);
    }

    /**
     * Register error and exception handlers
     *
     * @static

     */
    public static function registerErrorHandler()
    {
        set_error_handler(array(__CLASS__, '__error'));
        set_exception_handler(array(__CLASS__, '__exception'));
    }

    /**
     * Restore error and exception handlers
     *
     * @static

     */
    public static function restoreErrorHandler()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Auto load class
     *
     * @static
     * @param $class
     * @return bool
     */
    public static function __autoload($class)
    {
        $class = ltrim($class, '\\');

        if (is_array(self::$config['classpath'])) {
            $available_path = array(self::$config['classpath']['']);

            foreach (self::$config['classpath'] as $prefix => $path) {
                if ($prefix == '') continue;

                if (strtolower(substr($class, 0, strlen($prefix))) == strtolower($prefix)) {
                    array_unshift($available_path, $path);
                    break;
                }
            }
        } else {
            $available_path = array(self::$config['classpath']);
        }

        $file_name = '';
        if ($last_pos = strripos($class, '\\')) {
            $namespace = substr($class, 0, $last_pos);
            $class = substr($class, $last_pos + 1);
            $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file_name .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        foreach ($available_path as $path) {
            $file = stream_resolve_include_path($path . DIRECTORY_SEPARATOR . $file_name);
            if ($file) {
                require $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Error handler for app
     *
     * @static
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @throws \ErrorException
     */
    public static function __error($type, $message, $file, $line)
    {
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    /**
     * Exception handler for app
     *
     * @static
     * @param \Exception $e
     */
    public static function __exception(\Exception $e)
    {
        Event::fire(Event::ERROR, $e);
        echo $e;
    }

    /**
     * Shutdown handler for app
     *
     * @static

     */
    public static function __shutdown()
    {
        Event::fire(Event::SHUTDOWN);
        if (!self::isInit()) return;

        if (self::$config['error']
            && ($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
        ) {
            ob_get_level() and ob_clean();
            try {
                print new View(self::$config['route']['error']);
            } catch (\Exception $e) {
                self::__exception(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            }
        }
    }
}