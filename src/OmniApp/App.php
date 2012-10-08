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
     * @var string View engine
     */
    protected static $view = 'OmniApp\View\Base';

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
        self::$middleware = array(new Middleware\Router());

        // configure timezone
        self::config('timezone', function ($value) {
            date_default_timezone_set($value);
        });

        // configure debug
        self::config('debug', function ($value) {
            if ($value == true) {
                App::add(new \OmniApp\Middleware\PrettyException());
            }
        });

        // Set view engine
        self::config('view.engine', function ($value, $previous) {
            // Auto fix framework views
            if ($value{0} !== '\\') {
                $value = View::_CLASS_ . '\\' . $value;
            }
            // Not set ok then configure previous
            if ($value !== self::view($value)) {
                App::config('view.engine', $previous);
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
                $_trigger = true;

                if (is_array($value)) {
                    // If values if array then loop set
                    foreach ($value as $k => $v) {
                        self::config($key . '.' . $k, $v, false, $only_trigger);
                    }
                } elseif (!$only_trigger) {
                    //  Set value to key
                    if (self::$config instanceof Config) {
                        if (self::$config->get($key) !== $value) {
                            self::$config->set($key, $value);
                        } else {
                            $_trigger = false;
                        }
                    } else {
                        if (isset($configs[$key]) && $configs[$key] !== $value) {
                            $configs[$key] = $value;
                        } else {
                            $_trigger = false;
                        }
                    }
                }

                // Fire event when listener exists
                $_trigger && Event::fireEvent('config:' . $key, $value, self::config($key));
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
        if (is_string($middleware) && is_subclass_of($middleware, Middleware::_CLASS_)) {
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
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function get($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isGet()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Route post method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function post($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isPost()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Route put method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function put($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isPut()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Route delete method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function delete($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isDelete()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Route options method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function options($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isOptions()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Route head method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function head($path, $runner, $more = null)
    {
        if (self::$_cli || !self::$request->isHead()) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Http all route
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function all($path, $runner, $more = null)
    {
        if (self::$_cli) return;

        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Map route
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public static function map($path, $runner, $more = null)
    {
        if ($more !== null) {
            call_user_func_array(Route::_CLASS_ . '::on', func_get_args());
        } else {
            Route::on($path, $runner);
        }
    }

    /**
     * Set or get view
     *
     * @param string $view
     * @return string
     */
    public static function view($view = null)
    {
        if ($view) {
            if (self::$view !== $view && is_subclass_of($view, View::_CLASS_)) {
                self::$view = $view;
            }
        }
        return self::$view;
    }

    /**
     * Render template
     *
     * @param string $file
     * @param array  $params
     * @throws Exception
     */
    public static function render($file, $params = array())
    {
        $view = self::$view;
        self::$response->write($view::factory($file, $params));
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
        // Check has init?
        if (!self::$_init) {
            throw new \Exception('App has not initialized');
        }

        Event::fireEvent('run');

        $_error = false;
        if (self::config('error')) {
            $_error = true;
            self::registerErrorHandler();
        }

        try {
            // request app call
            self::$middleware[0]->call();
        } catch (\Exception $e) {
            if (self::$config->debug) {
                throw $e;
            } else {
                try {
                    App::error($e);
                } catch (Exception\Stop $e) {
                    //
                }
            }
            Event::fireEvent('error');
        }

        if (!self::$_cli) {
            // Send headers when http request
            self::$response->sendHeader();
        }
        // send data
        echo self::$response->body();

        if ($_error) self::restoreErrorHandler();

        Event::fireEvent('end');
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
        throw new Exception\Stop();
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
     * Next
     *
     * @throws Exception\Next
     */
    public static function next()
    {
        throw new Exception\Next();
    }

    /**
     * Set or run not found
     */
    public static function notFound($runner = null)
    {
        if ($runner instanceof \Closure) {
            Route::notFound($runner);
        } else {
            self::cleanBuffer();
            ob_start();
            if (!Route::notFound()) {
                echo "Path not found";
            }
            self::output(404, ob_get_clean());
        }
    }

    /**
     * Set or run not found
     */
    public static function error($runner = null)
    {
        if ($runner instanceof \Closure) {
            Route::error($runner);
        } else {
            self::cleanBuffer();
            ob_start();
            if (!Route::error($runner)) {
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
    public static function cleanBuffer()
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
        restore_error_handler();
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
        if (substr($class, 0, strlen(__NAMESPACE__) + 1) == __NAMESPACE__ . '\\') {
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