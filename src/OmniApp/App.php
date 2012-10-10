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
    /**
     * @var Config
     */
    protected $config = array();

    /**
     * @var array Modules for the app
     */
    public $modules = array();

    /**
     * @var Http\Request|Cli\Input
     */
    public $request;

    /**
     * @var Http\Response|Cli\Output
     */
    public $response;

    /**
     * @var string View engine
     */
    protected $view;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var string Mode
     */
    private $mode = 'development';

    /**
     * @var Middleware[]
     */
    private $middleware = array();

    /**
     * @var float App start time
     */
    private $start_time = null;

    /**
     * @var bool Initialized?
     */
    private $_init = false;

    /**
     * @var bool Is cli?
     */
    private $_cli = false;

    /**
     * @var bool Is win?
     */
    private $_win = false;

    /**
     * App init
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $app = &$this;
        // Record start time
        $this->start_time = microtime(true);

        // Is cli
        $this->_cli = PHP_SAPI == 'cli';

        // Is win
        $this->_win = substr(PHP_OS, 0, 3) == 'WIN';

        // handle autoload and shutdown
        register_shutdown_function(array($this, '__shutdown'));
        spl_autoload_register(array($this, '__autoload'));

        // Set io depends on SAPI
        if (!$this->_cli) {
            $this->request = new Http\Request($app);
            $this->response = new Http\Response($app);
        } else {
            $this->request = new Cli\Input();
            $this->response = new Cli\Output();
        }

        // Init Route
        $this->route = new Route($app, $this->request->path());

        // Force use UTF-8 encoding
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');

        // Add default middleware
        $this->middleware = array($this->route);

        // configure timezone
        $this->config('timezone', function ($value) {
            date_default_timezone_set($value);
        });

        // configure debug
        $this->config('debug', function ($value) use ($app) {
            if ($value == true) {
                $app->add(new \OmniApp\Middleware\PrettyException());
            }
        });

        // Set view engine
        $this->config('view.engine', function ($value, $previous) use ($app) {
            // Auto fix framework views
            if ($value{0} !== '\\') {
                $value = View::_CLASS_ . '\\' . $value;
            }
            // Not set ok then configure previous
            if ($value !== $app->view($value)) {
                $app->config('view.engine', $previous);
            }
        });

        // Config
        $this->config($config);

        // Fire init
        Event::fireEvent('init');

        $this->_init = true;
    }

    /**
     * Check if cli
     *
     * @return bool
     */
    public function isCli()
    {
        return $this->_cli;
    }

    /**
     * Check if windows, if not, it must be *unix
     *
     * @return bool
     */
    public function isWin()
    {
        return $this->_win;
    }

    /**
     * Get app start time
     *
     * @return int
     */
    public function startTime()
    {
        return $this->start_time;
    }

    /**
     * Get run time
     *
     * @return string
     */
    public function runTime()
    {
        return number_format(microtime(true) - $this->startTime(), 6);
    }

    /**
     * Is init?
     *
     * @return bool
     */
    public function isInit()
    {
        return $this->_init;
    }

    /**
     * Config get or set after init
     *
     * @param      $key
     * @param null $value
     * @param bool $no_detect_callback
     * @param bool $only_trigger
     * @return array|bool|\OmniApp\Config
     */
    public function config($key = null, $value = null, $no_detect_callback = false, $only_trigger = false)
    {
        // Save early configs
        static $configs = array();

        if ($key === null) {
            // If not arguments, return config
            return $this->config;
        } elseif ($value === null) {
            if (is_array($key) || $key instanceof Config) {
                // First set config
                $_first = false;

                // Set config first
                if (empty($this->config)) {
                    // First set config
                    $_first = true;

                    // Check key type
                    if ($key instanceof Config) {
                        // If config instance, use it
                        $this->config = $key;
                    } else {
                        // Else convert to Dict config
                        $this->config = new Config\Dict($key);
                    }

                    // Check configs and merge
                    if ($configs) {
                        // Loop config and set
                        foreach ($configs as $k => $v) {
                            $this->config->set($k, $v);
                        }
                        $key = $this->config;
                        $configs = array();
                    }
                }

                // Loop config
                foreach ($key as $k => $v) {
                    $this->config($k, $v, false, $_first);
                }
            } else {
                // When get config
                return $this->config instanceof Config ? $this->config->get($key) : false;
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
                        $this->config($key . '.' . $k, $v, false, $only_trigger);
                    }
                } elseif (!$only_trigger) {
                    //  Set value to key
                    if ($this->config instanceof Config) {
                        if ($this->config->get($key) !== $value) {
                            $this->config->set($key, $value);
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
                $_trigger && Event::fireEvent('config:' . $key, $value, $this->config($key));
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
     * @return string
     */
    public function mode($mode = null, \Closure $closure = null)
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
        if ($mode && is_string($mode) && $this->mode != $mode && !$this->_init) {
            // If set mode and mode is string and App is not init
            $this->mode = $mode;
        }

        return $this->mode;
    }

    /**
     * Add middleware
     *
     * @param Middleware|\Closure|string $middleware
     */
    public function add($middleware)
    {
        // Check and construct Middleware
        if (is_string($middleware) && is_subclass_of($middleware, Middleware::_CLASS_)) {
            $middleware = new $middleware();
        } elseif ($middleware instanceof \Closure) {
            $middleware = new Middleware($middleware);
        }

        // Check middleware
        if ($middleware instanceof Middleware) {
            // Set next middleware
            $middleware->setNext($this->middleware[0]);
            $middleware->setApp($this);
            // Un-shift middleware
            array_unshift($this->middleware, $middleware);
        }
    }

    /**
     * Route get method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function get($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isGet()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Route post method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function post($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isPost()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Route put method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function put($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isPut()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Route delete method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function delete($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isDelete()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Route options method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function options($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isOptions()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Route head method
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function head($path, $runner, $more = null)
    {
        if ($this->_cli || !$this->request->isHead()) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Http all route
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function all($path, $runner, $more = null)
    {
        if ($this->_cli) return;

        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Map route
     *
     * @param string          $path
     * @param \Closure|string $runner
     * @param \Closure|string $more
     */
    public function map($path, $runner, $more = null)
    {
        if ($more !== null) {
            call_user_func_array(array($this->route, 'on'), func_get_args());
        } else {
            $this->route->on($path, $runner);
        }
    }

    /**
     * Set or get view
     *
     * @param string $view
     * @return string
     */
    public function view($view = null)
    {
        if ($view) {
            if ($this->view !== $view && is_subclass_of($view, View::_CLASS_)) {
                $this->view = $view;
            }
        } elseif ($view === false) {
            $this->view = null;
        }
        return $this->view;
    }

    /**
     * Render template
     *
     * @param string $file
     * @param array  $params
     */
    public function render($file, $params = array())
    {
        $_view = $this->view ? $this->view : View::_CLASS_;
        $this->response->write(new $_view($file, $params));
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public function root()
    {
        return rtrim(getenv('DOCUMENT_ROOT'), '/') . rtrim($this->request->rootUri(), '/') . '/';
    }

    /**
     * App will run
     *
     */
    public function run()
    {
        // Check has init?
        if (!$this->_init) {
            throw new \Exception('App has not initialized');
        }

        // If trigger exists, trigger closure
        Event::fireEvent('mode:' . $this->mode());

        // Fire run
        Event::fireEvent('run');

        $_error = false;
        if ($this->config('error')) {
            $_error = true;
            $this->registerErrorHandler();
        }

        try {
            ob_start();

            // request app call
            $this->middleware[0]->call();

            // Write direct output to the head of buffer
            $this->response->write(ob_get_clean(), -1);
        } catch (\Exception $e) {
            if ($this->config->debug) {
                throw $e;
            } else {
                try {
                    $this->error($e);
                } catch (Exception\Stop $e) {
                    //
                }
            }
            Event::fireEvent('error');
        }

        if (!$this->_cli) {
            // Send headers when http request
            $this->response->sendHeader();
        }
        // send data
        echo $this->response->body();

        if ($_error) $this->restoreErrorHandler();

        Event::fireEvent('end');
    }

    /**
     * Event register
     *
     * @param $event
     * @param $closure
     */
    public function on($event, $closure)
    {
        Event::attach($event, $closure);
    }

    /**
     * Stop
     *
     * @throws Exception\Stop
     */
    public function stop()
    {
        throw new Exception\Stop();
    }

    /**
     * Pass
     *
     * @throws Exception\Pass
     */
    public function pass()
    {
        $this->cleanBuffer();
        throw new Exception\Pass();
    }

    /**
     * Set or run not found
     */
    public function notFound($runner = null)
    {
        if ($runner instanceof \Closure) {
            $this->route->notFound($runner);
        } else {
            $this->cleanBuffer();
            ob_start();
            if (!$this->route->notFound()) {
                echo "Path not found";
            }
            $this->output(404, ob_get_clean());
        }
    }

    /**
     * Set or run not found
     */
    public function error($runner = null)
    {
        if ($runner instanceof \Closure) {
            $this->route->error($runner);
        } else {
            $this->cleanBuffer();
            ob_start();
            if (!$this->route->error($runner)) {
                echo "Error occurred";
            }
            $this->output(500, ob_get_clean());
        }
    }

    /**
     * Output
     *
     * @param $status
     * @param $data
     */
    public function output($status, $data)
    {
        $this->cleanBuffer();
        if (!$this->_cli) {
            $this->response->contentType('text/plain');
        }
        $this->response->body($data);
        $this->response->status($status);
        $this->stop();
    }

    /**
     * Clean current output buffer
     */
    public function cleanBuffer()
    {
        if (ob_get_level() !== 0) {
            ob_clean();
        }
    }

    /**
     * Register error and exception handlers
     *

     */
    public function registerErrorHandler()
    {
        set_error_handler(array($this, '__error'));
    }

    /**
     * Restore error and exception handlers
     *

     */
    public function restoreErrorHandler()
    {
        restore_error_handler();
    }

    /**
     * Auto load class
     *
     * @param $class
     * @return bool
     */
    protected function __autoload($class)
    {
        if ($class{0} == '\\') $class = ltrim($class, '\\');

        if (substr($class, 0, strlen(__NAMESPACE__) + 1) == __NAMESPACE__ . '\\') {
            require __DIR__ . '/' . str_replace('\\', '/', substr($class, 8)) . '.php';
        } else {
            $available_path = false;
            $file_name = '';
            if (isset($this->config['classpath'])) {
                if (is_array($this->config['classpath'])) {
                    $available_path = array('' => $this->config['classpath']['']);

                    foreach ($this->config['classpath'] as $prefix => $path) {
                        if ($prefix == '') continue;

                        if (substr($class, 0, strlen($prefix)) == $prefix) {
                            array_unshift($available_path, $path);
                            break;
                        }
                    }
                } else {
                    $available_path = array($this->config['classpath']);
                }
            }

            if (!$available_path) return false;

            if ($last_pos = strrpos($class, '\\')) {
                $namespace = substr($class, 0, $last_pos);
                $class = substr($class, $last_pos + 1);
                $file_name = str_replace('\\', '/', $namespace) . '/';
            }
            $file_name .= str_replace('_', '/', $class) . '.php';
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
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     * @throws \ErrorException
     */
    protected function __error($type, $message, $file, $line)
    {
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    /**
     * Shutdown handler for app
     *
     * @todo Make as middleware
     */
    public function __shutdown()
    {
        Event::fireEvent('shutdown');
        if (!$this->_init) return;

        if ($this->config('error')
            && ($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
        ) {
            ob_get_level() and ob_clean();
            echo new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
        }
    }
}