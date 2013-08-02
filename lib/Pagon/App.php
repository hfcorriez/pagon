<?php
/**
 * Pagon Framework
 *
 * @package   Pagon
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2013 Pagon Framework
 */

namespace Pagon;

const VERSION = '0.6.3';

// Depend Fiber
if (!class_exists('Fiber')) {
    require __DIR__ . '/Fiber.php';
}

// Depend Emitter
if (!class_exists('EventEmitter')) {
    require __DIR__ . '/EventEmitter.php';
}

/**
 * App
 * The core of Pagon
 *
 * @package Pagon
 * @property string mode         Current run mode, Default is "develop"
 * @property bool   debug        Is debug mode enabled?
 * @property string views        Views dir
 * @property bool   error        Handle error
 * @property bool   buffer       Buffer enabled?
 * @property string timezone     Current timezone
 * @property string charset      The application charset
 * @property array  routes       Store the routes for run
 * @property array  prefixes     The prefixes to mapping the route namespace
 * @property array  names        Routes names
 * @property string autoload     The path to autoload
 * @property array  namespaces   The namespaces to load with directory path
 * @property array  alias        The class to alias
 * @property array  engines      The engines to render template
 * @property array  errors       Default error handles
 * @property array  stacks       The middleware stacks to load
 * @property array  bundles      The bundles to load
 * @property array  locals       The locals variables to used in template
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
    protected $injectors = array(
        'mode'       => 'develop',
        'debug'      => false,
        'views'      => false,
        'error'      => true,
        'routes'     => array(),
        'prefixes'   => array(),
        'names'      => array(),
        'buffer'     => true,
        'timezone'   => 'UTC',
        'charset'    => 'UTF-8',
        'autoload'   => null,
        'alias'      => array(),
        'namespaces' => array(),
        'engines'    => array(
            'jade' => 'Jade'
        ),
        'errors'     => array(
            '404'       => array(404, 'Request path not found'),
            'exception' => array(500, 'Error occurred'),
            'crash'     => array(500, 'Application crashed')
        ),
        'stacks'     => array(),
        'mounts'     => array(),
        'bundles'    => array(),
        'locals'     => array(),
        'safe_query' => true,
    );

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
     * @var array The file has loads
     */
    protected static $loads = array();

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
     * @return App
     */
    public function __construct($config = array())
    {
        // Is cli
        $this->_cli = PHP_SAPI == 'cli';

        // Register shutdown
        register_shutdown_function(array($this, '__shutdown'));

        // Register autoload
        spl_autoload_register(array($this, '__autoload'));

        // Set IO depends the run mode
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

        // Set config
        $this->injectors =
            (!is_array($config) ? Parser::load((string)$config) : $config)
            + ($this->_cli ? array('buffer' => false) : array())
            + $this->injectors;

        // Set default locals
        $this->injectors['locals']['config'] = & $this->injectors;

        // Set mode
        $this->injectors['mode'] = ($_mode = getenv('PAGON_ENV')) ? $_mode : $this->injectors['mode'];

        // Configure debug
        if ($this->injectors['debug']) $this->add(new Middleware\PrettyException());

        // Set pagon root directory
        $this->injectors['mounts']['pagon'] = dirname(dirname(__DIR__));

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
     * Check if run
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->_run;
    }

    /**
     * Set with no event emit
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function set($key, $value)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } elseif (strpos($key, '.') !== false) {
            $config = & $this->injectors;
            $namespaces = explode('.', $key);
            foreach ($namespaces as $namespace) {
                if (!isset($config[$namespace])) $config[$namespace] = array();
                $config = & $config[$namespace];
            }
            $config = $value;
        } else {
            $this->injectors[$key] = $value;
        }
    }

    /**
     * Set config as true
     *
     * @param string $key
     */
    public function enable($key)
    {
        $this->set($key, true);
    }

    /**
     * Set config as false
     *
     * @param string $key
     */
    public function disable($key)
    {
        $this->set($key, false);
    }

    /**
     * Check config if true
     *
     * @param string $key
     * @return bool
     */
    public function enabled($key)
    {
        return $this->get($key);
    }

    /**
     * Check config if false
     *
     * @param string $key
     * @return bool
     */
    public function disabled($key)
    {
        return !$this->get($key);
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
            $this->injectors['mode'] = $mode instanceof \Closure ? $mode() : (string)$mode;
        }
        return $this->injectors['mode'];
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
            $closure($this->injectors['mode']);
        } elseif ($mode == $this->injectors['mode']) {
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
        if ($path instanceof Middleware || is_string($path) && $path{0} != '/' || $path instanceof \Closure) {
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

        // Add to the end
        $this->injectors['stacks'][] = array($path, $middleware, $options);
    }

    /**
     * Add a bundle
     *
     * @param string       $name        Bundle name or path
     * @param string|array $options     Bundle options or name
     */
    public function bundle($name, $options = array())
    {
        if (!is_array($options)) {
            $path = $name;
            $name = $options;
            $options = array('path' => $path);
        }
        $this->injectors['bundles'][$name] = $options;
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
            if ($path === null) return $this->injectors;

            $tmp = null;
            if (strpos($path, '.') !== false) {
                $ks = explode('.', $path);
                $tmp = $this->injectors;
                foreach ($ks as $k) {
                    if (!isset($tmp[$k])) return null;

                    $tmp = & $tmp[$k];
                }
            } else {
                if (isset($this->injectors[$path])) {
                    $tmp = & $this->injectors[$path];
                }
            }

            return $tmp;
        }

        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('GET');
        } else {
            return $this->router->add($path, $route)->via('GET');
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
        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('POST');
        } else {
            return $this->router->add($path, $route)->via('POST');
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
        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('PUT');
        } else {
            return $this->router->add($path, $route)->via('PUT');
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
        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('DELETE');
        } else {
            return $this->router->add($path, $route)->via('DELETE');
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
        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('*');
        } else {
            return $this->router->add($path, $route)->via('*');
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
        if ($more !== null) {
            return call_user_func_array(array($this->router, 'set'), func_get_args())->via('CLI');
        } else {
            return $this->router->add($path, $route)->via('CLI');
        }
    }

    /**
     * Use auto route
     *
     * @param callable|bool $closure
     * @param string        $index
     * @return Router|mixed
     */
    public function autoRoute($closure, $index = 'Index')
    {
        if ($closure instanceof \Closure) {
            return $this->router->automatic = $closure;
        } elseif ($closure === true || is_string($closure)) {
            $_cli = $this->_cli;
            // Set route use default automatic
            return $this->router->automatic = function ($path) use ($closure, $_cli, $index) {
                $splits = array();

                // Split rule
                if (!$_cli && $path !== '/' || $_cli && $path !== '') {
                    $splits = array_map(function ($split) {
                        return ucfirst(strtolower($split));
                    }, $_cli ? explode(':', $path) : explode('/', ltrim($path, '/')));
                }

                // Set default namespace
                if ($closure !== true) array_unshift($splits, $closure);

                // Index
                if (!$splits) return $index;

                // Generate class name
                $class = join('\\', $splits);

                // Try to lookup class and with it's index
                return array($class, $class . '\\' . $index);
            };
        }
    }

    /**
     * Mount dir to path
     *
     * @param string $path
     * @param string $dir
     */
    public function mount($path, $dir = null)
    {
        if (!$dir) {
            $dir = $path;
            $path = '';
        }

        $this->injectors['mounts'][$path] = $dir;
    }

    /**
     * Load PHP file
     *
     * @param string $file
     * @return bool|mixed
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function load($file)
    {
        if (!$file = $this->path($file)) {
            throw new \InvalidArgumentException('Can load non-exists file "' . $file . '"');
        }

        if (isset(self::$loads[$file])) {
            return self::$loads[$file];
        }

        return self::$loads[$file] = include($file);
    }

    /**
     * Get path of mounts file system
     *
     * @param string $file
     * @return bool|string
     */
    public function path($file)
    {
        foreach ($this->injectors['mounts'] as $path => $dir) {
            if ($path === '' || strpos($file, $path) === 0) {
                if (!$path = stream_resolve_include_path($dir . '/' . ($path ? substr($file, strlen($path)) : $file))) continue;
                return $path;
            }
        }

        if ($path = stream_resolve_include_path($file)) {
            return $path;
        }

        return false;
    }

    /**
     * Check if file loaded
     *
     * @param string $file
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function loaded($file)
    {
        if (!$file = $this->path($file)) {
            throw new \InvalidArgumentException('Can not check non-exists file "' . $file . '"');
        }

        if (isset(self::$loads[$file])) {
            return true;
        }

        return false;
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
            $this->injectors['engines'][$name] = $engine;
        }
        return isset($this->injectors['engines'][$name]) ? $this->injectors['engines'][$name] : null;
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
    public function render($path, array $data = null, array $options = array())
    {
        $this->output->body($this->compile($path, $data, $options));
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
    public function compile($path, array $data = null, array $options = array())
    {
        if (!isset($options['engine'])) {
            // Get ext
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $options['engine'] = false;

            // If ext then check engine with ext
            if ($ext && isset($this->injectors['engines'][$ext])) {
                // If engine exists
                if (is_string($this->injectors['engines'][$ext])) {
                    if (class_exists($this->injectors['engines'][$ext])) {
                        $class = $this->injectors['engines'][$ext];
                    } else if (class_exists(__NAMESPACE__ . '\\Engine\\' . $this->injectors['engines'][$ext])) {
                        $class = __NAMESPACE__ . '\\Engine\\' . $this->injectors['engines'][$ext];
                    } else {
                        throw new \RuntimeException("Unavailable view engine '{$this->injectors['engines'][$ext]}'");
                    }
                    // Create new engine
                    $this->injectors['engines'][$ext] = $options['engine'] = new $class();
                } else {
                    // Get engine from exists engines
                    $options['engine'] = $this->injectors['engines'][$ext];
                }
            }
        }

        // Set default data
        if ($data === null) $data = array();

        // Default set app
        $data['_'] = $this;

        // Create view
        $view = new View($path, $data + $this->injectors['locals'], $options + array(
                'dir' => $this->injectors['views']
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
        // Check if run
        if ($this->_run) {
            throw new \RuntimeException("Application already running");
        }

        // Emit run
        $this->emit('run');

        // Set run
        $this->_run = true;

        $_error = false;
        if ($this->injectors['error']) {
            // If config error, register error handle and set flag
            $_error = true;
            $this->registerErrorHandler();
        }

        try {
            // Emit "bundle" event
            $this->emit('bundle');
            /**
             * Loop bundles and lookup the bundle info and start the bundle
             */
            foreach ($this->injectors['bundles'] as $id => $options) {
                // Set id
                $id = isset($options['id']) ? $options['id'] : $id;

                // Set bootstrap file
                $bootstrap = isset($options['bootstrap']) ? $options['bootstrap'] : 'bootstrap.php';

                // Set dir to load
                $dir = isset($options['dir']) ? $options['dir'] : 'bundles/' . $id;

                // Path check, if not match start of path, skip
                if (isset($options['path']) && strpos($this->input->path(), $options['path']) !== 0) {
                    continue;
                }

                // Check the file path
                if (!$file = $this->path($dir . '/' . $bootstrap)) {
                    throw new \InvalidArgumentException('Bundle "' . $id . '" can not bootstrap');
                }

                // Check if bootstrap file loaded
                if (isset(self::$loads[$file])) {
                    throw new \RuntimeException('Bundle "' . $id . '" can not bootstrap twice');
                }

                // Emit "bundle.[id]" event
                $this->emit('bundle.' . $id);

                // Set variable for bootstrap file
                $app = $this;
                extract($options);
                require $file;

                // Save to loads
                self::$loads[$file] = true;
            }

            // Start buffer
            if ($this->injectors['buffer']) ob_start();
            $this->injectors['stacks'][] = $this->router;

            // Emit "middleware" event
            $this->emit('middleware');

            // Loop stacks to match
            foreach ($this->injectors['stacks'] as $index => &$middleware) {
                if (!is_array($middleware)) {
                    $middleware = array('', $middleware);
                }
                // Try to match the path
                if ($middleware[0] && strpos($this->input->path(), $middleware[0]) === false) {
                    unset($this->injectors['stacks'][$index]);
                }
            }

            try {
                $this->router->pass($this->injectors['stacks'], function ($m) {
                    return Middleware::build($m[1], isset($m[2]) ? $m[2] : array());
                });
            } catch (Exception\Pass $e) {
            }

            // Write direct output to the head of buffer
            if ($this->injectors['buffer']) $this->output->write(ob_get_clean());
        } catch (Exception\Stop $e) {
        } catch (\Exception $e) {
            if ($this->injectors['debug']) {
                throw $e;
            } else {
                try {
                    $this->handleError('exception', $e);
                } catch (Exception\Stop $e) {
                }
            }
            $this->emit('error');
        }

        $this->_run = false;

        // Send start
        $this->emit('flush');

        // Flush
        $this->flush();

        // Send end
        $this->emit('end');

        if ($_error) $this->restoreErrorHandler();
    }

    /**
     * Register or run error
     *
     * @param string   $type
     * @param callable $route
     * @throws \InvalidArgumentException
     */
    public function handleError($type, $route = null)
    {
        if (!isset($this->injectors['errors'][$type])) {
            throw new \InvalidArgumentException('Unknown error type "' . $type . '" to call');
        }

        if (is_string($route) && is_subclass_of($route, Route::_CLASS_, true)
            || $route instanceof \Closure
        ) {
            $this->router->set('_' . $type, $route);
        } else {
            ob_get_level() && ob_clean();
            ob_start();
            if (!$this->router->handle('_' . $type,
                array('error' => array(
                    'type'    => $type,
                    'error'   => $route,
                    'message' => $this->injectors['errors'][$type])
                ))
            ) {
                if ($this->_cli) {
                    $this->halt($this->injectors['errors'][$type][0], $this->injectors['errors'][$type][1]);
                } else {
                    $this->render($this->path('pagon/views/error.php'), array(
                        'title'   => $this->injectors['errors'][$type][1],
                        'message' => 'Could not ' . $this->input->method() . ' ' . $this->input->path()
                    ));
                    $this->stop();
                }
            }
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
     * Start assist for common functions
     */
    public function assisting()
    {
        $this->load(dirname(__DIR__) . '/assistant.php');
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
        if (!empty($this->injectors['alias'][$class])) {
            class_alias($this->injectors['alias'][$class], $class);
            $class = $this->injectors['alias'][$class];
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
            if ($this->injectors['autoload']) {
                $available_path[99] = $this->injectors['autoload'];
            }

            // Check other namespaces
            if ($this->injectors['namespaces']) {
                // Loop namespaces as autoload
                foreach ($this->injectors['namespaces'] as $_prefix => $_path) {
                    // Check if match prefix
                    if (strpos($class, $_prefix) === 0) {
                        // Set ordered path
                        $available_path[(99 - strlen($_prefix)) . $_prefix] = $_path;
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
     * Flush output
     */
    public function flush()
    {
        // Send headers
        if (!$this->_cli) {
            $this->output->sendHeader();
        }

        // Send
        echo $this->output->body();
    }

    /**
     * Error handler for app
     *
     * @param int    $type
     * @param string $message
     * @param string $file
     * @param int    $line
     * @throws \ErrorException
     */
    public function __error($type, $message, $file, $line)
    {
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    /**
     * Shutdown handler for app
     */
    public function __shutdown()
    {
        $this->emit('exit');
        if (!$this->_run) return;

        if (($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR, E_CORE_ERROR))
        ) {
            if (!$this->injectors['debug']) {
                try {
                    $this->handleError('crash', $error);
                } catch (Exception\Stop $e) {
                }
                $this->flush();
            }
            $this->emit('crash', $error);
        }
    }
}
