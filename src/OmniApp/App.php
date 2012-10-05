<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 */

namespace OmniApp;

spl_autoload_register(array(__NAMESPACE__ . '\\App', '__autoload'));

const VERSION = '0.2';

/*********************
 * core app
 ********************/

/**
 * App Class
 */
class App
{
    /**
     * @var Config
     * @todo protect
     */
    protected static $config = array();

    /**
     * @var array Modules for the app
     */
    public static $modules = array();

    /**
     * @var Http\Request
     */
    public static $request;

    /**
     * @var Http\Response|CLI\Output
     */
    public static $response;

    /**
     * @var string Mode
     */
    private static $mode = 'development';

    /**
     * @var Middleware[]
     */
    private static $middleware = array();

    /**
     * @var float App start time
     */
    private static $start_time = null;

    /**
     * @var bool Initialized?
     */
    private static $_init = false;

    /**
     * @var bool Is cli?
     */
    private static $_cli = false;

    /**
     * @var bool Is win?
     */
    private static $_win = false;

    /**
     * App init
     *
     * @static
     * @param array $config
     */
    public static function init($config = array())
    {
        // Record start time
        self::$start_time = microtime(true);

        // Is cli
        self::$_cli = PHP_SAPI == 'cli';

        // Is win
        self::$_win = substr(PHP_OS, 0, 3) == 'WIN';

        if (!self::$_cli) {
            self::$request = new Http\Request();
            self::$response = new Http\Response();
        } else {
            self::$response = new CLI\Output();
        }

        // handle autoload and shutdown
        register_shutdown_function(array(__CLASS__, '__shutdown'));

        // Force use UTF-8 encoding
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');

        // Add default middleware
        self::$middleware = array(new Middleware\RunTime());

        // configure timezone
        self::config('timezone', function ($value) {
            date_default_timezone_set($value);
        });

        // configure debug
        self::config('debug', function ($value) {
            if ($value == true) {
                App::add(new \OmniApp\Middleware\Debug());
            }
        });

        // Config
        self::config($config);

        // If trigger exists, trigger closure
        Event::fireEvent('mode:' . self::mode());

        // Fire init
        Event::fireEvent('init');

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
        return self::$_cli;
    }

    /**
     * Check if windows, if not, it must be *unix
     *
     * @static
     * @return bool
     */
    public static function isWin()
    {
        return self::$_win;
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
    public static function isInit()
    {
        return self::$_init;
    }

    /**
     * Config get or set after init
     *
     * @param      $key
     * @param null $value
     * @param bool $no_detect_callback
     * @param bool $only_trigger
     * @return mixed
     */
    public static function config($key = null, $value = null, $no_detect_callback = false, $only_trigger = false)
    {
        // Save early configs
        static $configs = array();

        if ($key === null) {
            // If not arguments, return config
            return self::$config;
        } elseif ($value === null) {
            if (is_array($key) || $key instanceof Config) {
                // First set config
                $_first = false;

                // Set config first
                if (empty(self::$config)) {
                    // First set config
                    $_first = true;

                    // Check key type
                    if ($key instanceof Config) {
                        // If config instance, use it
                        self::$config = $key;
                    } else {
                        // Else convert to Dict config
                        self::$config = new Config\Dict($key);
                    }

                    // Check configs and merge
                    if ($configs) {
                        // Loop config and set
                        foreach ($configs as $k => $v) {
                            self::$config->set($k, $v);
                        }
                        $key = self::$config;
                        $configs = array();
                    }
                }

                // Convert Config to array
                if ($key instanceof Config) {
                    $key = $key->getArrayCopy();
                }

                // Loop config
                foreach ($key as $k => $v) {
                    self::config($k, $v, false, $_first);
                }
            } else {
                // When get config
                return self::$config instanceof Config ? self::$config->get($key) : false;
            }
        } else {
            if (!$no_detect_callback && $value instanceof \Closure) {
                // Auto attach event
                Event::on('config:' . $key, function (Event $e, $v, $p) use ($value) {
                    $value($v, $p);
                });
            } else {
                // Fire event when listener exists
                Event::fireEvent('config:' . $key, $value, self::config($key));

                if (is_array($value)) {
                    // If values if array then loop set
                    foreach ($value as $k => $v) {
                        self::config($key . '.' . $k, $v, false, $only_trigger);
                    }
                } elseif (!$only_trigger) {
                    //  Set value to key
                    if (self::$config instanceof Config) {
                        self::$config->set($key, $value);
                    } else {
                        $configs[$key] = $value;
                    }
                }
            }
        }
        return;
    }

    /**
     * Config mode
     * # Manuel call must before App::init
     *
     * @param          $mode
     * @param callable $closure
     * @return null|string
     */
    public static function mode($mode = null, \Closure $closure = null)
    {
        // Save get mode method
        static $mode_get = null;

        if ($mode === null) {
            // Get or generate mode
            $mode = $mode_get ? $mode_get() : getenv('OMNIAPP_ENV');
        } elseif ($closure === null) {
            // Allow set mode get method when mode is closure
            if ($mode instanceof \Closure) {
                $mode_get = $mode;
            }
        } elseif ($closure) {
            // Set trigger for the mode
            Event::on('mode:' . $mode, $closure);
            // Don not change the current mode
            $mode = null;
        }

        // Check
        if ($mode && is_string($mode) && self::$mode != $mode && !self::$_init) {
            // If set mode and mode is string and App is not init
            self::$mode = $mode;
        }

        return self::$mode;
    }

    /**
     * Add middleware
     *
     * @param Middleware $middleware
     */
    public static function add($middleware)
    {
        // Check and construct Middleware
        if (is_string($middleware) && is_subclass_of($middleware, __NAMESPACE__ . '\Middleware')) {
            $middleware = new $middleware();
        }

        // Check middleware
        if ($middleware instanceof Middleware) {
            // Set next middleware
            $middleware->setNext(self::$middleware[0]);
            // Un-shift middleware
            array_unshift(self::$middleware, $middleware);
        }
    }

    /**
     * Route get method
     *
     * @param $path
     * @param $runner
     */
    public static function get($path, $runner)
    {
        if (self::$_cli || !self::$request->isGet()) return;

        self::map($path, $runner);
    }

    /**
     * Route post method
     *
     * @param $path
     * @param $runner
     */
    public static function post($path, $runner)
    {
        if (self::$_cli || !self::$request->isPost()) return;

        self::map($path, $runner);
    }

    /**
     * Route put method
     *
     * @param $path
     * @param $runner
     */
    public static function put($path, $runner)
    {
        if (self::$_cli || !self::$request->isPut()) return;

        self::map($path, $runner);
    }

    /**
     * Route delete method
     *
     * @param $path
     * @param $runner
     */
    public static function delete($path, $runner)
    {
        if (self::$_cli || !self::$request->isDelete()) return;

        self::map($path, $runner);
    }

    /**
     * Route options method
     *
     * @param $path
     * @param $runner
     */
    public static function options($path, $runner)
    {
        if (self::$_cli || !self::$request->isOptions()) return;

        self::map($path, $runner);
    }

    /**
     * Route head method
     *
     * @param $path
     * @param $runner
     */
    public static function head($path, $runner)
    {
        if (self::$_cli || !self::$request->isHead()) return;

        self::map($path, $runner);
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
     * Set or get view
     *
     * @param $view_class
     * @return string
     */
    public static function view($view_class = null)
    {
        if ($view_class) {
            View::setView($view_class);
        }
        return View::getView();
    }

    /**
     * Render template
     *
     * @param string $file
     * @param array  $params
     * @return View
     */
    public static function render($file, $params = array())
    {
        $view = View::factory($file, $params);
        self::$response->write($view);
        return $view;
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public static function root()
    {
        return rtrim(getenv('DOCUMENT_ROOT'), '/') . rtrim(self::$request->rootUri(), '/') . '/';
    }

    /**
     * App will run
     *
     * @static
     */
    public static function run()
    {
        if (!self::$_init) {
            throw new \Exception('App has not initialized');
        }

        Event::fireEvent('run');

        if (self::config('error')) self::registerErrorHandler();

        self::$middleware[0]->call();

        echo self::$response->body();

        if (self::config('error')) self::restoreErrorHandler();

        Event::fireEvent('end');
    }

    /**
     * Call for the middleware
     *
     * @throws \Exception
     */
    public static function call()
    {
        try {
            $path = self::$_cli ? '/' . join('/', array_slice($GLOBALS['argv'], 1)) : self::$request->path();
            list($controller, $route, $params) = Route::parse($path);

            Event::fireEvent('start');
            ob_start();
            if (!self::dispatch($controller, $params)) {
                self::notFound();
            }
            self::stop();
        } catch (Exception\Stop $e) {
            self::$response->write(ob_get_clean());
            Event::fireEvent('stop');
        } catch (\Exception $e) {
            if (self::config('debug')) {
                throw $e;
            } else {
                try {
                    self::error($e);
                } catch (Exception\Stop $e) {
                    //
                }
            }
            Event::fireEvent('exception');
        }
    }

    /**
     * Control the runner
     *
     * @param       $runner
     * @param array $params
     * @return bool
     */
    public static function dispatch($runner, $params = array())
    {
        // Set params
        if (!self::$_cli) self::$request->params = $params;

        if (is_string($runner) && Controller::factory($runner, array(self::$request, self::$response))) {
            // Support controller class string
            return true;
        } elseif (is_callable($runner)) {
            // Closure function support
            call_user_func_array($runner, array(self::$request, self::$response));
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
            if (!$runner || !self::dispatch($runner)) {
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
            $e = $runner;
            $runner = Route::error();
            if (!$runner || !self::dispatch($runner, array($e))) {
                echo "Error occurred";
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
        if (!self::$_cli) {
            self::$response->contentType('text/plain');
        }
        self::$response->body($data);
        self::$response->status($status);
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
        static $available_path = array();

        if ($class{0} == '\\') $class = ltrim($class, '\\');

        $file_name = '';
        if (substr($class, 0, 8) == 'OmniApp\\') {
            require __DIR__ . '/' . str_replace('\\', '/', substr($class, 8)) . '.php';
        } else {
            if (isset(self::$config['classpath']) && !$available_path) {
                if (is_array(self::$config['classpath'])) {
                    $available_path = array(self::$config['classpath']['']);

                    foreach (self::$config['classpath'] as $prefix => $path) {
                        if ($prefix == '') continue;

                        if (substr($class, 0, strlen($prefix)) == $prefix) {
                            array_unshift($available_path, $path);
                            break;
                        }
                    }
                } else {
                    $available_path = array(self::$config['classpath']);
                }
            }

            if (!$available_path) return false;

            if ($last_pos = strrpos($class, '\\')) {
                $namespace = substr($class, 0, $last_pos);
                $class = substr($class, $last_pos + 1);
                $file_name = str_replace('\\', '/', $namespace) . '/';
            }
            $file_name .= (strpos($class, '_') ? str_replace('_', '/', $class) : $class) . '.php';
            foreach ($available_path as $path) {
                $file = stream_resolve_include_path($path . '/' . $file_name);
                if ($file) {
                    require $file;
                    return true;
                }
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
        Event::fireEvent('shutdown');
        if (!self::$_init) return;

        if (self::config('error')
            && ($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
        ) {
            ob_get_level() and ob_clean();
            echo new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
        }
    }
}