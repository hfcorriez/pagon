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
        Event::add(EVENT::INIT, $config);
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
        Event::add(EVENT::RUN);
        $path = self::isCli() ? join('/', array_slice($GLOBALS['argv'], 1)) : Request::path();
        list($controller, $route, $params) = Route::parse($path);

        if (is_string($controller)) {
            Controller::factory($controller, $params);
        } else {
            call_user_func_array($controller, $params);
        }
        Event::add(EVENT::END);
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
        Event::add(EVENT::ERROR, $type, $message, $file, $line);
    }

    /**
     * Exception handler for app
     *
     * @static
     * @param \Exception $e
     */
    public static function __exception(\Exception $e)
    {
        echo $e;
        Event::add(EVENT::EXCEPTION, $e);
    }

    /**
     * Shutdown handler for app
     *
     * @static

     */
    public static function __shutdown()
    {
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
        Event::add(EVENT::SHUTDOWN);
    }
}

/**
 * Omni ArrayObject implements object get and set.
 */
class ArrayObjectWrapper extends \ArrayObject
{
    public function __set($name, $val)
    {
        $this[$name] = $val;
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this)) {
            $ret = &$this[$name];
        } else $ret = null;
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
 * Event
 */
abstract class Event
{
    const INIT = 'INIT';
    const RUN = 'RUN';
    const END = 'END';
    const ERROR = 'ERROR';
    const EXCEPTION = 'EXCEPTION';
    const SHUTDOWN = 'SHUTDOWN';

    /**
     * Register event on $name
     *
     * @static
     * @param $name
     * @param $runner
     */
    public static function on($name, $runner)
    {
        App::$config['event'][$name][] = $runner;
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
        if (!empty(App::$config['event'][$name])) {
            foreach (App::$config['event'][$name] as $runner) {
                self::execute($runner, $params);
            }
        }
    }

    /**
     * Execute runner for event point
     *
     * @static
     * @param       $runner
     * @param array $params
     */
    private static function execute($runner, $params = array())
    {
        if (is_string($runner)) {
            call_user_func_array(array(new $runner(), 'run'), $params);
        } else {
            call_user_func_array($runner, $params);
        }
    }

    /**
     * Run method for event runner object
     *  if u register a class name for runner, u must implements run method.
     *
     * @abstract

     */
    abstract function run();
}

/*********************
 * MVC
 ********************/

/**
 * Abstract model
 */
abstract class Model
{
    /**
     * @var \Database
     */
    protected $_data;

    /**
     * Construct new model
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            if ($this->__isValidProperty($key)) {
                $this->{$key} = $value;
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * Save current values
     *
     * @return void
     */
    public function save()
    {
        if (!$this->isChanged()) return;
        $this->_data = $this->getChanged() + $this->_data;
    }

    /**
     * Get changed values
     *
     * @return array
     */
    public function getChanged()
    {
        $changed = array();
        foreach ($this->_data as $key => $value) {
            if ($this->{$key} !== $value) $changed[$key] = $this->$key;
        }
        return $changed;
    }

    /**
     * Check if changed
     *
     * @return bool
     */
    public function isChanged()
    {
        foreach ($this->_data as $key => $value) {
            if ($this->{$key} !== $value) return true;
        }
        return false;
    }

    /**
     * Export as array
     *
     * @return array
     */
    public function getAsArray()
    {
        $data = array();
        foreach ($this->_data as $key => $value) {
            $data[$key] = $this->{$key};
        }
        return $data;
    }

    /**
     * Forbidden set non-exists property
     *
     * @param        $key
     * @param string $value
     * @throws \Exception
     */
    public function __set($key, $value = null)
    {
        throw new \Exception(__CLASS__ . " has no property named '$key'.");
    }

    /**
     * Forbidden get non-exists property
     *
     * @param $key
     * @throws \Exception
     */
    public function __get($key)
    {
        throw new \Exception(__CLASS__ . " has no property named '$key'.");
    }

    /**
     * Valida property name
     *
     * @param $key
     * @return bool
     */
    private function __isValidProperty($key)
    {
        return $key{0} != '_' && property_exists($this, $key);
    }
}

/**
 * Abstract Controller
 */
abstract class Controller
{
    /**
     * abstract before run
     *
     * @abstract

     */
    abstract function before();

    /**
     * abstract after run
     *
     * @abstract

     */
    abstract function after();

    /**
     * Factory a controller
     *
     * @static
     * @param       $controller
     * @param array $params
     * @return mixed
     */
    final public static function factory($controller, $params = array())
    {
        $controller = new $controller();

        $controller->before();
        call_user_func_array(array($controller, 'run'), $params);
        $controller->after();

        return $controller;
    }
}

/**
 * Abstract view
 */
class View
{
    private $file = NULL;

    /**
     * Factory a view
     *
     * @static
     * @param $view
     * @return mixed|View
     */
    final public static function factory($view)
    {
        return new $view();
    }

    /**
     * Construct a view
     *
     * @param $view
     * @throws \Exception
     */
    public function __construct($view)
    {
        if (!App::$config['views']) throw new \Exception('No views found.');
        $this->file = App::$config['views'] . '/' . strtolower(trim($view, '/')) . '.php';
        if (!is_file($this->file)) throw new \Exception('View file not exist: ' . $this->file);
    }

    /**
     * Set variable for view
     *
     * @param $array
     * @return View
     */
    public function set($array)
    {
        foreach ($array as $key => $value) $this->$key = $value;
        return $this;
    }

    /**
     * Assign value for view
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * Get assign value for view
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     * Support output
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        extract((array)$this);
        include($this->file);
        return ob_get_clean();
    }
}

/**
 * Route
 */
class Route
{
    /**
     * Register a route for path
     *
     * @static
     * @param $path
     * @param $runner
     */
    public static function on($path, $runner)
    {
        App::$config['route'][$path] = $runner;
    }

    /**
     * Parse site path
     *
     * @static
     * @param $path
     * @return array
     * @throws \Exception
     */
    public static function parse($path)
    {
        if (empty(App::$config['route'])) throw new \Exception('No routes found.');

        $path = trim($path, '/');
        if ($path === '') return array(App::$config['route'][''], '', array());
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

        foreach (App::$config['route'] as $route => $controller) {
            if (!$route) continue;

            if ($route{0} === '/') {
                if (preg_match($route, $path, $matches)) {
                    $complete = array_shift($matches);
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if ($params[0]) {
                        foreach ($matches as $match) array_unshift($params, $match);
                    } else $params = $matches;
                    return array($controller, $complete, $params);
                }
            } else {
                if (mb_substr($path, 0, mb_strlen($route)) === $route) {
                    $params = explode('/', trim(mb_substr($path, mb_strlen($route)), '/'));
                    return array($controller, $route, $params);
                }
            }
        }

        if (!isset(App::$config['route']['404'])) throw new \Exception('404 page found, but no 404 route found.');
        return array(App::$config['route']['404'], $path, array($path));
    }
}

/*********************
 * http module
 ********************/

/**
 * Request
 */
class Request
{
    protected static $path;
    protected static $url;
    protected static $track_id;
    protected static $headers;

    /**
     * Get protocol
     *
     * @static
     * @return string
     */
    public static function protocol()
    {
        return getenv('SERVER_PROTOCOL');
    }

    /**
     * Get path
     *
     * @static
     * @return mixed
     */
    public static function path()
    {
        if (!self::$path) self::$path = parse_url(getenv('REQUEST_URI'), PHP_URL_PATH);

        return self::$path;
    }

    /**
     * Get url
     *
     * @static
     * @return mixed
     */
    public static function url()
    {
        if (!self::$url) {
            self::$url = (strtolower(getenv('HTTPS')) == 'on' ? 'https' : 'http') . '://' . getenv('HTTP_HOST') . getenv('REQUEST_URI');
        }

        return self::$url;
    }

    /**
     * Get method
     *
     * @static
     * @return string
     */
    public static function method()
    {
        return getenv('REQUEST_METHOD');
    }

    /**
     * Is ajax
     *
     * @static
     * @return bool
     */
    public static function isAjax()
    {
        return !getenv('X-Requested-With') && 'XMLHttpRequest' == getenv('X-Requested-With');
    }

    /**
     * Get refer url
     *
     * @static
     * @return string
     */
    public static function refer()
    {
        return getenv('HTTP_REFERER');
    }

    /**
     * Get track id
     *
     * @static
     * @return mixed
     */
    public static function trackId()
    {
        if (!self::$track_id) {
            self::$track_id = md5(uniqid());
        }

        return self::$track_id;
    }

    /**
     * Get domain
     *
     * @static
     * @return string
     */
    public static function domain()
    {
        return getenv('HTTP_HOST');
    }

    /**
     * Get user agent
     *
     * @static
     * @return string
     */
    public static function userAgent()
    {
        return getenv('HTTP_USER_AGENT');
    }

    /**
     * Get header or headers
     *
     * @static
     * @param null $key
     * @return mixed
     */
    public static function header($key = null)
    {
        if (!self::$headers) {
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' === substr($key, 0, 5)) {
                    $headers[strtolower(substr($key, 5))] = $value;
                } elseif (in_array($key, array('CONTENT_LENGTH',
                    'CONTENT_MD5',
                    'CONTENT_TYPE'))
                ) {
                    $headers[strtolower($key)] = $value;
                }
            }

            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $pass);
            }
            self::$headers = $headers;
        }

        if ($key === null) return self::$headers;
        return self::$headers[$key];
    }
}

class Response
{
    public static $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    protected static $status = 200;
    protected static $headers = array();
    protected static $body = '';
    protected static $cookies = array();
    protected static $content_type = 'text/html';
    protected static $charset = 'UTF-8';

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string
     */
    public static function body($content = NULL)
    {
        if ($content === NULL) return self::$body;

        return self::$body = (string)$content;
    }

    /**
     * Set status code
     *
     * @static
     * @param int $status
     * @return int|Response
     * @throws \Exception
     */
    public static function status($status = NULL)
    {
        if ($status === NULL) {
            return self::$status;
        } elseif (array_key_exists($status, self::$messages)) {
            return self::$status = (int)$status;
        }
        else throw new \Exception('Unknown status :value', array(':value' => $status));
    }

    /**
     * Set header
     *
     * @static
     * @param null $key
     * @param null $value
     * @return array
     */
    public static function header($key = NULL, $value = NULL)
    {
        if ($key === NULL) {
            return self::$headers;
        } elseif ($value === NULL) return self::$headers[$key];
        else {
            return self::$headers[$key] = $value;
        }
    }

    /**
     * Get lenth of body
     *
     * @static
     * @return int
     */
    public static function length()
    {
        return strlen(self::$body);
    }

    /**
     * Send headers
     *
     * @static
     * @param bool $replace
     */
    public static function sendHeaders($replace = FALSE)
    {

        header(Request::protocol() . ' ' . self::$status . ' ' . self::$messages[self::$status]);
        foreach (self::$headers as $key => $value) header($key . ': ' . $value, $replace);
    }

    /**
     * Send body
     *
     * @static
     */
    public static function sendBody()
    {
        echo self::$body;
    }

    /**
     * Send all
     *
     * @static
     */
    public static function send()
    {
        self::sendHeaders();
        self::sendBody();
    }
}