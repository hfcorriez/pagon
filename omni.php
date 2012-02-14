<?php
/**
 * OmniApp Framework
 *
 * @package		OmniApp
 * @author		Corrie Zhao <hfcorriez@gmail.com>
 * @copyright   (c) 2011 - 2012 OmniApp Framework
 * @license     http://www.apache.org/licenses/LICENSE-2.0
 *
 * @todo        实现通用Model
 * @todo        优化几种事件
 */

namespace Omni
{

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
     * @var Config
     */
    public static $config;
    
    /**
     * @var Request
     */
    public static $request;

    /**
     * @var Response
     */
    public static $response;
    
    /**
     * @var Env
     */
    public static $env;
    
    private static $_init = false;

    public static function init($config = array())
    {
        Event::add(EVENT_INIT, $config);

        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        self::$config = new Config($config);
        if (self::$config->timezone) date_default_timezone_set(self::$config->timezone);

        self::$env = Env::instance();
        if (!self::$env->is_cli)
        {
            self::$request = Request::instance();
            self::$response = Response::instance();
        }
        if (empty($_SERVER['HTTP_TRACKING_ID'])) $_SERVER['HTTP_TRACKING_ID'] = md5(uniqid());

        if (!self::$config->apppath) throw new Exception('Config->apppath must be set before.');

        if (self::$config->error) self::register_error_handlers();
        if (self::$config->classpath) spl_autoload_register(array(__CLASS__, '__autoload'));

        if (!empty(self::$config->module)) Module::load(self::$config->module);

        register_shutdown_function(array(__CLASS__, '__shutdown'));
        self::$_init = true;
    }
    
    public static function isInit(){ return self::$_init; }

    public static function run()
    {
        Event::add(EVENT_RUN);
        
        $path = self::$env->is_cli ? join('/', self::$env->argv) : self::$request->path;
        list($controller, $route, $params) = Route::parse($path);
        
        if (is_object($controller) && get_class($controller) == 'Closure') return call_user_func_array($controller, $params);
        else return Controller::factory($controller, $params);
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

    private static function __autoload($class_name)
    {
        Event::add(EVENT_AUTOLOAD, $class_name);
        $class_name = ltrim($class_name, '\\');
        require self::$config->classpath . '/' . strtolower(str_replace('\\', '/', $class_name) . '.php');
    }

    public static function __error($type, $message, $file, $line)
    {
        Event::add(EVENT_ERROR, $type, $message, $file, $line);
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    public static function __exception(\Exception $e)
    {
        Event::add(EVENT_EXCEPTION, $e);
        if (!self::$env->is_cli AND !headers_sent()) header(self::$request->protocol . ' 500 Internal Server Error');

        echo $e;
        exit(1);
    }

    public static function __shutdown()
    {
        Event::add(EVENT_SHUTDOWN);

        if (!self::$_init) return;
        
        if (self::$config->error AND $error = error_get_last() AND in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR)))
        {
            ob_get_level() and ob_clean();
            try { print new View(self::$config->route['error']); }
            catch (\Exception $e) { self::__exception(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line'])); }
            exit(1);
        }
    }
}

class ArrayObjectWrapper extends \ArrayObject
{
    public function __set($name, $val) { $this[$name] = $val; }

    public function &__get($name) 
    {
        if (array_key_exists($name, $this)) $ret = &$this[$name];
        else $ret = null;
        return $ret;
    }
    
    public function __isset($name) { return isset($this[$name]); }
    
    public function __unset($name) { unset($this[$name]); }
}

class Instance
{
    private static $_instance = array();
    
    public static function instance()
    {
        $class_name = get_called_class();
        if (!isset(self::$_instance[$class_name])) self::$_instance[$class_name] = new $class_name();
        return self::$_instance[$class_name];
    }
}

class Request extends Instance
{
    public $path;
    public $url;
    public $method;
    public $protocol;
    public $is_ajax;
    public $headers;
    public $referer;
    public $track_id;
    public $domain;
    public $user_agent;
    
    public function __construct()
    {
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        $this->is_ajax = !empty($_SERVER['X-Requested-With']) && 'XMLHttpRequest' == $_SERVER['X-Requested-With'];
        $this->referer = empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'];
        $this->track_id = !empty($_SERVER['HTTP_TRACK_ID']) ? $_SERVER['HTTP_TRACK_ID'] : md5(uniqid());
        $this->domain = $_SERVER['HTTP_HOST'];
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $headers = array();
        foreach ($_SERVER as $key => $value)
        {
            if ('HTTP_' === substr($key, 0, 5)) $headers[strtolower(substr($key, 5))] = $value;
            elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) $headers[strtolower($key)] = $value;
        }
        
        if (isset($_SERVER['PHP_AUTH_USER']))
        {
            $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['authorization'] = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$pass);
        }
        $this->headers = $headers;
    }
}

class Response extends Instance
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

    protected $_status = 200;
    protected $_headers = array();
    protected $_body = '';
    protected $_cookies = array();
    protected $_protocol;
    protected $_content_type = 'text/html';
    protected $_charset = 'UTF-8';

    public function __construct()
    {
        $this->_protocol = App::$request->protocol;
        $this->_headers['x-powered-by'] = 'OmniApp ' . VERSION;
    }

    public function __toString()
    {
        return $this->_body;
    }

    public function body($content = NULL)
    {
        if ($content === NULL) return $this->_body;

        $this->_body = (string) $content;
        return $this;
    }

    public function protocol($protocol = NULL)
    {
        if ($protocol)
        {
            $this->_protocol = strtoupper($protocol);
            return $this;
        }

        return $this->_protocol;
    }

    public function status($status = NULL)
    {
        if ($status === NULL)  return $this->_status;
        elseif (array_key_exists($status, self::$messages))
        {
            $this->_status = (int) $status;
            return $this;
        }
        else throw new Exception(__METHOD__.' unknown status value : :value', array(':value' => $status));
    }

    public function header($key = NULL, $value = NULL)
    {
        if ($key === NULL) return $this->_headers;
        elseif ($value === NULL) return $this->_headers[$key];
        else
        {
            $this->_headers[$key] = $value;
            return $this;
        }
    }

    public function length()
    {
        return strlen($this->body());
    }

    public function sendHeaders($replace = FALSE)
    {
        header($this->_protocol.' '.$this->_status.' '.self::$messages[$this->_status]);
        foreach ($this->_headers as $key => $value) header($key . ': ' . $value, $replace);
        return $this;
    }
}

class Env extends Instance
{
    public $is_cli;
    public $is_win;
    public $start_time;
    public $start_memory;
    public $timezone;
    public $basename;
    public $user;
    public $userdir;
    public $argv;
    public $_;

    public function __construct()
    {
        $this->is_cli = (PHP_SAPI == 'cli');
        $this->is_win = (substr(PHP_OS, 0, 3) == 'WIN');
        $this->start_time = $_SERVER['REQUEST_TIME'];
        $this->start_memory = memory_get_usage();
        $this->timezone = date_default_timezone_get();
        
        if ($this->is_cli)
        {
            $argv = $GLOBALS['argv'];
            $this->_ = array_shift($argv);
            $this->argv = $argv;

            if ($this->is_win)
            {
                $this->user = $_SERVER['USERNAME'];
                $this->userdir = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            }
            else
            {
                $this->user = $_SERVER['USER'];
                $this->userdir = $_SERVER['HOME'];
            }

        }
        else $this->_ = $_SERVER['SCRIPT_FILENAME'];
        $this->basename = basename($this->_);
    }
}

class Config extends ArrayObjectWrapper {}

class Route
{
    public static function parse($path)
    {
        $routes = App::$config->route;
        if (!is_array($routes) || empty($routes)) throw new Exception('Config->routes must be set before.');
    
        $path = trim($path, '/');
    
        if ($path === '') return array($routes[''], '', array());
        if ($path AND ! preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';
    
        foreach ($routes as $route => $controller)
        {
            if (!$route) continue;
    
            if ($route{0} === '/')
            {
                if (preg_match($route, $path, $matches))
                {
                    $complete = array_shift($matches);
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if ($params[0])
                    {
                        foreach ($matches as $match) array_unshift($params, $match);
                    }
                    else $params = $matches;
                    return array($controller, $complete, $params);
                }
            }
            else
            {
                if (mb_substr($path, 0, mb_strlen($route)) === $route)
                {
                    $params = explode('/', trim(mb_substr($path, mb_strlen($route)), '/'));
                    return array($controller, $route, $params);
                }
            }
        }
    
        if (!isset($routes['404'])) throw new Exception('Config->route["404"] not set.');
        return array($routes['404'], $path, array($path));
    }
}

abstract class Model {
    public static function factory($model)
    {
        return new $model;
    }
}

abstract class Controller
{
    abstract function before();
    abstract function after();
    
    final public static function factory($controller, $params = array())
    {
        $controller = new $controller();
        $method = App::$request ? App::$request->method : null;

        if (!$method || !method_exists($controller, $method))
        {
            if (!method_exists($controller, 'run')) throw new Exception('Contoller::run not exist.');
            $method = 'run';
        }
        
        $controller->before($method);
        call_user_func_array(array($controller, $method), $params);
        $controller->after($method);
        
        return $controller;
    }
}

class View
{
    private $_file = NULL;

    final public static function factory($view)
    {
        return new $view();
    }

    public function __construct($view)
    {
        if (!App::$config->viewpath) throw new Exception('Config->viewpath not set.');
        $this->_file = App::$config->viewpath . '/' . strtolower(trim($view, '/')) . '.php';
        if (!is_file($this->_file)) throw new Exception('View file not exist: ' . $this->_file);
    }

    public function set($array)
    {
        foreach ($array as $key => $value) $this->$key = $value;
        return $this;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function __toString()
    {
        ob_start();
        extract((array) $this);
        include($this->_file);
        return ob_get_clean();
    }
}

abstract class Module
{
    /**
     * @static
     * @param Module[] $modules
     */
    final public static function load($modules = array())
    {
        foreach ($modules as $module => $config)
        {
            if ($module{0} !== '\\') $module = '\\' . __NAMESPACE__ . '\\' . ucfirst($module);
            if (class_exists($module))
            {
                if ($config && is_string($config)) $config = include($config);
                $module::init($config);
            }
        }
    }
}

abstract class Event
{
    private static $_events = array();
    
    public static function on($name, $runner)
    {
        self::$_events[$name][] = $runner;
    }
    
    public static function add($name)
    {
        $params = array_slice(func_get_args(), 1);
        if (!empty(self::$_events[$name]))
        {
            foreach (self::$_events[$name] as $runner) self::__excute($runner, $params);
        }

        if (!empty(App::$config->event[$name]))
        {
            foreach (App::$config->event[$name] as $runner) self::__excute($runner, $params);
        }
    }
    
    private static function __excute($runner, $params = array())
    {
        if (is_object($runner) && get_class($runner) == 'Closure') return call_user_func_array($runner, $params);
        else return call_user_func_array(array(new $runner(), 'run'), $params);
    }
    
    abstract function run();
}

/**
 * 核心异常
 * @author hfcorriez
 */
class Exception extends \Exception {}

}