<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 */

namespace OmniApp;

const VERSION = '0.2';

/*********************
 * core app
 ********************/

/**
 * App Class
 */
class App
{
    public static $config = array('route' => array());
    public static $modules = array();

    /**
     * @var Middleware[]
     */
    private static $middleware = array();

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
        Event::fire('init');

        self::$config = $config;

        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));

        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        if (!empty($config['timezone'])) date_default_timezone_set($config['timezone']);

        self::$start_time = microtime(true);

        self::$_init = true;
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
     * Get run time
     *
     * @return string
     */
    public static function runTime()
    {
        return number_format(microtime(true) - self::startTime(), 6);
    }

    /**
     * Is init?
     *
     * @static
     * @return bool
     */
    public static function hasInit()
    {
        return self::$_init;
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
            return isset(self::$config[$key]) ? self::$config[$key] : null;
        } else {
            return self::$config[$key] = $value;
        }
    }

    /**
     * Add middleware
     *
     * @param Middleware $middleware
     */
    public static function add($middleware)
    {
        if (is_string($middleware) && class_exists($middleware) && is_subclass_of($middleware, __NAMESPACE__ . '\Middleware')) {
            $middleware = new $middleware();
        }
        if (!empty(self::$middleware)) {
            $middleware->setNext(self::$middleware[0]);
        }
        array_unshift(self::$middleware, $middleware);
    }

    /**
     * Map route
     *
     * @param $path
     * @param $runner
     */
    public static function map($path, $runner)
    {
        Route::on($path, $runner);
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public static function root()
    {
        return rtrim(getenv('DOCUMENT_ROOT'), '/') . rtrim(Http\Request::rootUri(), '/') . '/';
    }

    /**
     * App run
     *
     * @static
     */
    public static function run()
    {
        Event::fire('run');

        if (self::config('error')) self::registerErrorHandler();

        if (!empty(self::$middleware)) {
            self::$middleware[0]->call();
        }

        $path = self::isCli() ? '/' . join('/', array_slice($GLOBALS['argv'], 1)) : Http\Request::path();
        list($controller, $route, $params) = Route::parse($path);

        try {
            Event::fire('start');
            ob_start();
            if (!self::control($controller, $params)) {
                self::notFound();
            }
            self::stop();
        } catch (Exception\Stop $e) {
            if (self::isCli()) {
                CLI\Output::write(ob_get_clean());
            } else {
                Http\Response::write(ob_get_clean());
            }
            Event::fire('stop');
        } catch (\Exception $e) {
            if (self::config('debug')) {
                throw $e;
            } else {
                try {
                    self::error();
                } catch (Exception\Stop $e) {
                    //
                }
            }
            Event::fire('exception');
        }

        if (!self::isCli()) {
            if (headers_sent() === false) {
                header(sprintf('HTTP/%s %s %s', Http\Request::protocol(), Http\Response::status(), Http\Response::message()));

                foreach (Http\Response::header() as $name => $value) {
                    $h_values = explode("\n", $value);
                    foreach ($h_values as $h_val) {
                        header("$name: $h_val", false);
                    }
                }
            }
            echo Http\Response::body();
        } else {
            echo CLI\Output::body();
        }

        if (self::config('error')) self::restoreErrorHandler();

        Event::fire('end');
    }

    /**
     * Control the runner
     *
     * @param       $runner
     * @param array $params
     * @return bool
     */
    public static function control($runner, $params = array())
    {
        if (is_string($runner) && Controller::factory($runner, $params)) {
            return true;
        } elseif (is_callable($runner)) {
            call_user_func_array($runner, $params);
            return true;
        }
        return false;
    }

    /**
     * Event register
     *
     * @param $event
     * @param $closure
     */
    public static function on($event, $closure)
    {
        Event::attach($event, $closure);
    }

    /**
     * Stop
     *
     * @throws Exception\Stop
     */
    public static function stop()
    {
        throw new Exception\Stop;
    }

    /**
     * Pass
     *
     * @throws Exception\Pass
     */
    public static function pass()
    {
        self::cleanBuffer();
        throw new Exception\Pass();
    }

    /**
     * Set or run not found
     */
    public static function notFound($runner = null)
    {
        if (is_callable($runner)) {
            Route::notFound($runner);
        } else {
            self::cleanBuffer();
            ob_start();
            $runner = Route::notFound();
            if ($runner) {
                self::control($runner);
            } else {
                echo "Page not found";
            }
            self::output(404, ob_get_clean());
        }
    }

    /**
     * Set or run not found
     */
    public static function error($runner = null)
    {
        if (is_callable($runner)) {
            Route::error($runner);
        } else {
            self::cleanBuffer();
            ob_start();
            $runner = Route::error();
            if ($runner) {
                self::control($runner);
            } else {
                echo "Error occured";
            }
            self::output(500, ob_get_clean());
        }
    }

    /**
     * Output
     *
     * @param $status
     * @param $data
     */
    public static function output($status, $data)
    {
        self::cleanBuffer();
        if (self::isCli()) {
            CLI\Output::body($data);
            CLI\Output::status($status);
        } else {
            Http\Response::contentType('text/plain');
            Http\Response::body($data);
            Http\Response::status($status);
        }
        self::stop();
    }

    /**
     * Clean current output buffer
     */
    protected static function cleanBuffer()
    {
        if (ob_get_level() !== 0) {
            ob_clean();
        }
    }

    /**
     * Register error and exception handlers
     *
     * @static

     */
    public static function registerErrorHandler()
    {
        set_error_handler(array(__CLASS__, '__error'));
    }

    /**
     * Restore error and exception handlers
     *
     * @static

     */
    public static function restoreErrorHandler()
    {
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
     * Shutdown handler for app
     *
     * @static
     * @todo Make as middleware
     */
    public static function __shutdown()
    {
        Event::fire('shutdown');
        if (!self::hasInit()) return;

        if (self::config('error')
            && ($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
        ) {
            ob_get_level() and ob_clean();
            echo new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
        }
    }
}