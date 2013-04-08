<?php
/**
 * Pagon Framework
 *
 * @package   Pagon
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2013 Pagon Framework
 */

namespace Pagon;

const VERSION = '0.5';

// Depend Fiber
if (!class_exists('Fiber')) {
    require __DIR__ . '/Fiber.php';
}

// Depend Emitter
if (!class_exists('EventEmitter')) {
    require __DIR__ . '/EventEmitter.php';
}

/*********************
 * core app
 ********************/

/**
 * App Class
 */
class App extends EventEmitter
{
    /**
     * @var Http\Input|Cli\Input
     */
    public $input;

    /**
     * @var Http\Output|Cli\Output
     */
    public $output;

    /**
     * @var Router
     */
    public $router;

    /**
     * @var Config
     */
    public $config = array(
        'debug'    => false,
        'views'    => false,
        'error'    => false,
        'routes'   => array(),
        'names'    => array(),
        'buffer'   => true,
        'timezone' => 'UTC',
        'charset'  => 'UTF-8',
        'alias'    => array(),
        'engines'  => array(
            'jade' => 'Jade'
        ),
        'stacks'   => array('' => array()),
    );

    /**
     * @var array Local variables
     */
    public $locals = array();

    /**
     * @var string Mode
     */
    protected $mode;

    /**
     * @var bool Is cli?
     */
    private $_cli = false;

    /**
     * @var bool Is run?
     */
    private $_run = false;

    /**
     * @var App The top app
     */
    protected static $self;

    /**
     * Return current app
     *
     * @throws \RuntimeException
     * @return App
     */
    public static function self()
    {
        if (!self::$self) {
            throw new \RuntimeException("There is no App exists");
        }

        return self::$self;
    }

    /**
     * App init
     *
     * @param array|string $config
     * @throws \RuntimeException
     */
    public function __construct($config = array())
    {
        $app = & $this;

        // Is cli
        $this->_cli = PHP_SAPI == 'cli';

        // Register shutdown
        register_shutdown_function(array($this, '__shutdown'));

        // Register autoload
        spl_autoload_register(array($this, '__autoload'));

        // Set io depends on SAPI
        if (!$this->_cli) {
            $this->input = new Http\Input($this);
            $this->output = new Http\Output($this);
        } else {
            $this->input = new Cli\Input($this);
            $this->output = new Cli\Output($this);
        }

        // Init Route
        $this->router = new Router(array('path' => $this->input->path()));
        $this->router->app = $this;

        // Force use UTF-8 encoding
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');

        // Config
        $this->config = !is_array($config) ? Config::load((string)$config)->defaults($this->config) : new Config($config + $this->config);

        // Register some initialize
        $this->on('run', function () use ($app) {
            // configure timezone
            if ($app->config['timezone']) date_default_timezone_set($app->config['timezone']);

            // configure debug
            if ($app->config['debug']) $app->add(new Middleware\PrettyException());

            // Share the cryptor for the app
            $app->share('cryptor', function ($app) {
                if (empty($app->config['crypt'])) {
                    throw new \RuntimeException('Encrypt cookie need configure config["crypt"]');
                }
                return new \Pagon\Utility\Cryptor($app->config['crypt']);
            });
        });

        // Set default locals
        $this->locals['config'] = & $this->config;

        // Set mode
        $this->mode = ($_mode = getenv('PAGON_ENV')) ? $_mode : 'development';

        // Fire init
        $this->emit('init');

        // Save current app
        self::$self = $this;
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
     * Set with no event emit
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->config->set($key, $value);
    }

    /**
     * Set config as true
     *
     * @param $key
     */
    public function enable($key)
    {
        $this->config->set($key, true);
    }

    /**
     * Set config as false
     *
     * @param $key
     */
    public function disable($key)
    {
        $this->config->set($key, false);
    }

    /**
     * Check config if true
     *
     * @param $key
     * @return bool
     */
    public function enabled($key)
    {
        return $this->config->set($key) === true;
    }

    /**
     * Check config if false
     *
     * @param $key
     * @return bool
     */
    public function disabled($key)
    {
        return $this->config->set($key) === false;
    }

    /**
     * Config mode
     *
     * # Manuel call must before App::init
     *
     * @param string|\Closure $mode
     * @return callable|null|string
     */
    public function mode($mode = null)
    {
        if ($mode) {
            $this->mode = $mode instanceof \Closure ? $mode() : (string)$mode;
        }
        return $this->mode;
    }

    /**
     * Configure mode
     *
     * @param string|\Closure $mode
     * @param \Closure        $closure
     */
    public function configure($mode, \Closure $closure = null)
    {
        if ($closure === null) {
            $closure = $mode instanceof \Closure ? $mode : null;
            $mode = null;
        } elseif ($mode == 'all') {
            // All mode
            $mode = null;
        }

        // Not exists closure?
        if (!$closure) return;

        // Allow set mode get method when mode is closure
        if (!$mode) {
            $closure($this->mode);
        } elseif ($mode == $this->mode) {
            $closure();
        }
    }

    /**
     * Add middleware
     *
     * @param Middleware|\Closure|string $path
     * @param Middleware|\Closure|string $middleware
     * @param array                      $options
     * @throws \RuntimeException
     * @return void
     */
    public function add($path, $middleware = null, $options = array())
    {
        if ($path instanceof Middleware || is_string($path) && $path{0} != '/') {
            // If not path
            $options = (array)$middleware;
            $middleware = $path;
            $path = '';
        }

        if (is_string($middleware)) {
            // If middleware is class name
            if ($middleware{0} !== '\\') {
                // Support short class name
                $middleware = __NAMESPACE__ . '\Middleware\\' . $middleware;
            }

            // Check if base on Middleware class
            if (!is_subclass_of($middleware, Middleware::_CLASS_)) {
                throw new \RuntimeException("Bad middleware can not be called");
            }
        }

        // Set default array
        if (!isset($this->config['stacks'][$path])) {
            $this->config['stacks'][$path] = array();
        }

        // Add to the end
        $this->config['stacks'][$path][] = array($middleware, $options);
    }

    /**
     * Route get method
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return mixed|Router
     */
    public function get($path, $route = null, $more = null)
    {
        // Get config for use
        if ($route === null) {
            return $this->config->get($path);
        }

        if ($this->_cli || !$this->input->isGet()) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Route post method
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return Router|mixed
     */
    public function post($path, $route, $more = null)
    {
        if ($this->_cli || !$this->input->isPost()) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Route put method
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return Router|mixed
     */
    public function put($path, $route, $more = null)
    {
        if ($this->_cli || !$this->input->isPut()) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Route delete method
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return Router|mixed
     */
    public function delete($path, $route, $more = null)
    {
        if ($this->_cli || !$this->input->isDelete()) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Match all method
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return Router|mixed
     */
    public function all($path, $route = null, $more = null)
    {
        if ($this->_cli) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Use auto route
     *
     * @param callable|bool $closure
     * @return Router|mixed
     */
    public function autoRoute($closure)
    {
        if ($closure instanceof \Closure) {
            return $this->router->automatic($closure);
        } elseif ($closure === true || is_string($closure)) {
            $_cli = $this->_cli;
            // Set route use default automatic
            return $this->router->automatic(function ($path) use ($closure, $_cli) {
                if (!$_cli && $path !== '/' || $_cli && $path !== '') {
                    $splits = array_map(function ($split) {
                        return ucfirst(strtolower($split));
                    }, $_cli ? explode(':', $path) : explode('/', ltrim($path, '/')));
                } else {
                    // If path is root or is not found
                    $splits = array('Index');
                }

                return ($closure === true ? '' : $closure . '\\') . join('\\', $splits);
            });
        }
    }

    /**
     * Map cli route
     *
     * @param string          $path
     * @param \Closure|string $route
     * @param \Closure|string $more
     * @return Router|mixed
     */
    public function cli($path, $route = null, $more = null)
    {
        if (!$this->_cli) return;

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args());
        } else {
            return $this->router->set($path, $route);
        }
    }

    /**
     * Set or get view
     *
     * @param string $name
     * @param string $engine
     * @return string
     */
    public function engine($name, $engine = null)
    {
        if ($engine) {
            // Set engine
            $this->config['engines'][$name] = $engine;
        }
        return isset($this->config['engines'][$name]) ? $this->config['engines'][$name] : null;
    }

    /**
     * Render template
     *
     * @param string $path
     * @param array  $data
     * @param array  $options
     * @throws \RuntimeException
     * @return void
     */
    public function render($path, $data = array(), array $options = array())
    {
        echo $this->compile($path, $data, $options);
    }

    /**
     * Compile view
     *
     * @param string $path
     * @param array  $data
     * @param array  $options
     * @return View
     * @throws \RuntimeException
     */
    public function compile($path, $data = array(), array $options = array())
    {
        if (!isset($options['engine'])) {
            // Get ext
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $options['engine'] = false;

            // If ext then check engine with ext
            if ($ext && isset($this->config['engines'][$ext])) {
                // If engine exists
                if (is_string($this->config['engines'][$ext])) {
                    if (class_exists($this->config['engines'][$ext])) {
                        $class = $this->config['engines'][$ext];
                    } else if (class_exists(__NAMESPACE__ . '\\Engine\\' . $this->config['engines'][$ext])) {
                        $class = __NAMESPACE__ . '\\Engine\\' . $this->config['engines'][$ext];
                    } else {
                        throw new \RuntimeException("Unavailable view engine '{$this->config['engines'][$ext]}'");
                    }
                    // Create new engine
                    $this->config['engines'][$ext] = $options['engine'] = new $class();
                } else {
                    // Get engine from exists engines
                    $options['engine'] = $this->config['engines'][$ext];
                }
            }
        }

        // Default set app
        $data['_'] = $this;

        // Create view
        $view = new View($path, $data + $this->locals, $options + array(
            'dir' => $this->config['views']
        ));

        // Return view
        return $view;
    }

    /**
     * App will run
     *
     */
    public function run()
    {
        // Emit run
        $this->emit('run');

        // Set run
        $this->_run = true;

        $_error = false;
        if ($this->config['error']) {
            // If config error, register error handle and set flag
            $_error = true;
            $this->registerErrorHandler();
        }

        try {
            // Start buffer
            if ($this->config['buffer']) ob_start();
            $this->config['stacks'][''][] = array($this->router);

            // Loop stacks to match
            foreach ($this->config['stacks'] as $path => $middleware) {
                // Try to match the path
                if ($path && strpos($this->input->path(), $path) !== 0) continue;

                $middleware = (array)$middleware;
                if (empty($middleware)) continue;

                try {
                    $this->router->pass($middleware, function ($m) {
                        return Middleware::build($m[0], isset($m[1]) ? $m[1] : array());
                    });

                    break;
                } catch (Exception\Pass $e) {
                }
            }

            // Write direct output to the head of buffer
            if ($this->config['buffer']) $this->output->write(ob_get_clean());
        } catch (Exception\Stop $e) {
        } catch (\Exception $e) {
            if ($this->config['debug']) {
                throw $e;
            } else {
                try {
                    $this->error($e);
                } catch (Exception\Stop $e) {
                }
            }
            $this->emit('error');
        }

        $this->_run = false;

        // Send start
        $this->emit('flush');

        // Send headers
        if (!$this->_cli) {
            $this->output->sendHeader();
        }

        // Send
        echo $this->output->body();

        // Send end
        $this->emit('end');

        if ($_error) $this->restoreErrorHandler();
    }

    /**
     * Register or run error
     *
     * @param callable $route
     */
    public function error($route = null)
    {
        if ($route && !$route instanceof \Exception) {
            $this->router->set('error', $route);
        } else {
            ob_get_level() && ob_clean();
            ob_start();
            if (!$this->router->handle('error', array($route))) {
                echo 'Error occurred';
            }
            $this->halt(500, ob_get_clean());
        }
    }

    /**
     * Register or run not found
     *
     * @param callable $route
     */
    public function notFound($route = null)
    {
        if ($route && !$route instanceof \Exception) {
            $this->router->set('404', $route);
        } else {
            ob_get_level() && ob_clean();
            ob_start();
            if (!$this->router->handle('404', array($route))) {
                echo 'Path not found';
            }
            $this->halt(404, ob_get_clean());
        }
    }

    /**
     * Register or run not found
     *
     * @param callable $route
     */
    public function crash($route = null)
    {
        if ($route && !$route instanceof \Exception) {
            $this->router->set('crash', $route);
        } else {
            ob_get_level() && ob_clean();
            ob_start();
            if (!$this->router->handle('crash', array($route))) {
                echo 'App is down';
            }
            $this->halt(500, ob_get_clean());
        }
    }

    /**
     * Output the response
     *
     * @param int    $status
     * @param string $body
     * @throws Exception\Stop
     */
    public function halt($status, $body = '')
    {
        $this->output->status($status)->body($body);
        throw new Exception\Stop;
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
        ob_get_level() && ob_clean();
        throw new Exception\Pass();
    }

    /**
     * Get or set param
     *
     * @param string|null $param
     * @return array|bool|null
     */
    public function param($param = null)
    {
        if ($param === null) {
            return $this->input->params;
        } else {
            if (is_array($param)) {
                $this->input->params = $param;
                return true;
            } else {
                return isset($this->input->params[$param]) ? $this->input->params[$param] : null;
            }
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
     * @param string $class
     * @return bool
     */
    protected function __autoload($class)
    {
        if ($class{0} == '\\') $class = ltrim($class, '\\');

        // Alias check
        if (!empty($this->config['alias'][$class])) {
            class_alias($this->config['alias'][$class], $class);
            $class = $this->config['alias'][$class];
        }

        // If with Pagon path, force require
        if (substr($class, 0, strlen(__NAMESPACE__) + 1) == __NAMESPACE__ . '\\') {
            if ($file = stream_resolve_include_path(__DIR__ . '/' . str_replace('\\', '/', substr($class, strlen(__NAMESPACE__) + 1)) . '.php')) {
                require $file;
                return true;
            }
        } else {
            // Set the 99 high order for default autoload
            $available_path = array();

            // Autoload
            if (!empty($this->config['autoload'])) {
                $available_path[99] = $this->config['autoload'];
            }

            // Check other namespaces
            if (!empty($this->config['namespace'])) {
                // Loop namespaces as autoload
                foreach ($this->config['namespace'] as $_prefix => $_path) {
                    // Check if match prefix
                    if (($_pos = strpos($class, $_prefix)) === 0) {
                        // Set ordered path
                        $available_path[strlen($_prefix)] = $_path;
                    }
                }
                // Sort by order
                ksort($available_path);
            }

            // No available path, no continue
            if ($available_path) {
                // Set default file name
                $file_name = '';
                // PSR-0 check
                if ($last_pos = strrpos($class, '\\')) {
                    $namespace = substr($class, 0, $last_pos);
                    $class = substr($class, $last_pos + 1);
                    $file_name = str_replace('\\', '/', $namespace) . '/';
                }
                // Get last file name
                $file_name .= str_replace('_', '/', $class) . '.php';
                // Loop available path for check
                foreach ($available_path as $_path) {
                    // Check file if exists
                    if ($file = stream_resolve_include_path($_path . '/' . $file_name)) {
                        require $file;
                        return true;
                    }
                }
            }

            $try_class = __NAMESPACE__ . '\\' . $class;
            if (class_exists($try_class)) {
                class_alias($try_class, $class);
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
     */
    public function __shutdown()
    {
        $this->emit('shutdown');
        if (!$this->_run) return;

        if (($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR, E_CORE_ERROR))
        ) {
            if (!$this->config['debug']) {
                try {
                    $this->crash();
                } catch (Exception\Stop $e) {
                    // Send headers
                    if (!$this->_cli) {
                        $this->output->sendHeader();
                    }

                    // Send
                    echo $this->output->body();
                }
            }
            $this->emit('crash', $error);
        }
    }
}
