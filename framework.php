<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 *
 */

const VERSION = '0.1';

const EVENT_INIT = 'init';
const EVENT_RUN = 'run';
const EVENT_ERROR = 'error';
const EVENT_EXCEPTION = 'exception';
const EVENT_SHUTDOWN = 'shutdown';
const EVENT_AUTOLOAD = 'autoload';

/*********************
 * core app
 ********************/

/**
 * App Class
 */
class App
{
    /**
     * App config
     *
     * @var
     */
    public static $config;

    // is init?
    private static $_init = false;

    public static $is_cli = null;
    public static $is_win = null;
    public static $start_time = null;
    public static $start_memory = null;

    /**
     * App init
     *
     * @static
     * @param array $config
     */
    public static function init($config = array())
    {
        self::$config = $config;

        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        if (empty($config['apppath'])) throw new \Exception('config["apppath"] must be set before.');
        if (!empty($config['timezone'])) date_default_timezone_set($config['timezone']);
        if (!empty($config['error'])) self::register_error_handlers();

        self::$is_cli = PHP_SAPI == 'cli';
        self::$is_win = substr(PHP_OS, 0, 3) == 'WIN';
        self::$start_time = $_SERVER['REQUEST_TIME'];
        self::$start_memory = memory_get_usage();

        self::$_init = true;
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
        $path = self::$is_cli ? join('/', array_slice($GLOBALS['argv'], 1)) : Request::path();
        list($controller, $route, $params) = Route::parse($path);

        if (is_string($controller))
        {
            Controller::factory($controller, $params);
        }
        else
        {
            call_user_func_array($controller, $params);
        }
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
        $class = ltrim($class, '\\');
        $file = self::$config['classpath'] . '/' . strtolower(str_replace('\\', '/', $class) . '.php');
        is_file($file) && require $file;
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
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
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
        echo $e;
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
        if (!self::$_init) return;

        if (self::$config['error']
            && ($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))
        )
        {
            ob_get_level() and ob_clean();
            try
            {
                print new View(self::$config['route']['error']);
            }
            catch (\Exception $e)
            {
                self::__exception(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            }
        }
        Event::add(EVENT_SHUTDOWN);
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
 * Event
 */
abstract class Event
{
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
        if (!empty(App::$config['event'][$name]))
        {
            foreach (App::$config['event'][$name] as $runner)
            {
                self::excute($runner, $params);
            }
        }
    }

    /**
     * Excute runner for event point
     *
     * @static
     * @param       $runner
     * @param array $params
     */
    private static function excute($runner, $params = array())
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
     *  if u register a class name for runner, u must implements run method.
     *
     * @abstract
     *
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
     * factory a model
     *
     * @static
     *
     * @param $model
     *
     * @return mixed
     */
    public static function factory($model)
    {
        return new $model;
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
     *
     */
    abstract function before();

    /**
     * abstract after run
     *
     * @abstract
     *
     */
    abstract function after();

    /**
     * Factory a controller
     *
     * @static
     *
     * @param       $controller
     * @param array $params
     *
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
     *
     * @param $view
     *
     * @return mixed|View
     */
    final public static function factory($view)
    {
        return new $view();
    }

    /**
     * Constrct a view
     *
     * @param $view
     */
    public function __construct($view)
    {
        if (!App::$config['viewpath']) throw new Exception('config["viewpath"] not set.');
        $this->file = App::$config['viewpath'] . '/' . strtolower(trim($view, '/')) . '.php';
        if (!is_file($this->file)) throw new Exception('View file not exist: ' . $this->file);
    }

    /**
     * Set variable for view
     *
     * @param $array
     *
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
     *
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
     * @throws Exception
     */
    public static function parse($path)
    {
        if (empty(App::$config['route'])) throw new Exception('config["route"] must be set before.');

        $path = trim($path, '/');
        if ($path === '') return array(App::$config['route'][''], '', array());
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

        foreach (App::$config['route'] as $route => $controller)
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

        if (!isset(App::$config['route']['404'])) throw new Exception('config["route"]["404"] not set.');
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

    public static function protocol()
    {
        return getenv('SERVER_PROTOCOL');
    }

    public static function path()
    {
        if (!self::$path) self::$path = parse_url(getenv('REQUEST_URI'), PHP_URL_PATH);

        return self::$path;
    }

    public static function url()
    {
        if (!self::$url)
        {
            self::$url = (strtolower(getenv('HTTPS')) == 'on' ? 'https' : 'http') . '://' . getenv('HTTP_HOST') . getenv('REQUEST_URI');
        }

        return self::$url;
    }

    public static function method()
    {
        return getenv('REQUEST_METHOD');
    }

    public static function isAjax()
    {
        return !getenv('X-Requested-With') && 'XMLHttpRequest' == getenv('X-Requested-With');
    }

    public static function refer()
    {
        return getenv('HTTP_REFERER');
    }

    public static function trackId()
    {
        if (!self::$track_id)
        {
            self::$track_id = md5(uniqid());
        }

        return self::$track_id;
    }

    public static function domain()
    {
        return getenv('HTTP_HOST');
    }

    public static function userAgent()
    {
        return getenv('HTTP_USER_AGENT');
    }

    public static function header($key = null)
    {
        if (!self::$headers)
        {
            $headers = array();
            foreach ($_SERVER as $key => $value)
            {
                if ('HTTP_' === substr($key, 0, 5)) $headers[strtolower(substr($key, 5))] = $value;
                elseif (in_array($key, array('CONTENT_LENGTH',
                                             'CONTENT_MD5',
                                             'CONTENT_TYPE'))
                ) $headers[strtolower($key)] = $value;
            }

            if (isset($_SERVER['PHP_AUTH_USER']))
            {
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
     * @throws Exception
     */
    public static function status($status = NULL)
    {
        if ($status === NULL) return self::$status;
        elseif (array_key_exists($status, self::$messages))
        {
            return self::$status = (int)$status;
        }
        else throw new Exception(__METHOD__ . ' unknown status :value', array(':value' => $status));
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
        if ($key === NULL) return self::$headers;
        elseif ($value === NULL) return self::$headers[$key];
        else
        {
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

/*********************
 * built-in modules
 ********************/

/**
 * I18n
 */
class I18n
{
    /**
     * @var array config
     */
    // language config
    protected static $lang = 'en-US';

    // cache config
    protected static $cache = array();

    // Enabled?
    protected static $enable = false;

    /**
     * Init i18n config
     *
     * @static
     * @param $config
     * @throws Exception
     */
    public static function init()
    {
        if (App::$config['lang'] && App::$config['langpath']) self::$enable = true;
        self::lang(I18n::preferedLanguage(App::$config['lang']));
    }

    /**
     * Get or set language
     *
     * @static
     * @param null|string $lang
     * @return string
     */
    public static function lang($lang = NULL)
    {
        if ($lang) self::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
        return self::$lang;
    }

    /**
     * Get words tranlation
     *
     * @static
     * @param             $string
     * @param null|string $lang
     * @return string
     */
    public static function get($string, $lang = NULL)
    {
        if (!$lang) $lang = self::$lang;
        $table = self::_load($lang);
        return isset($table[$string]) ? $table[$string] : $string;
    }

    /**
     * Automatic match best language
     *
     * @static
     * @param array $languages
     * @return string
     */
    public static function preferedLanguage($languages = array())
    {
        if (isset($_COOKIE['lang'])) return $_COOKIE['lang'];
        if (!$languages) return 'en-US';

        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" . "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
            $_SERVER['HTTP_ACCEPT_LANGUAGE'], $hits, PREG_SET_ORDER);
        $best_lang = $languages[0];
        $best_q_val = 0;

        foreach ($hits as $arr)
        {
            $lang_prefix = strtolower($arr[1]);
            if (!empty($arr[3]))
            {
                $lang_range = strtolower($arr[3]);
                $language = $lang_prefix . "-" . $lang_range;
            }
            else $language = $lang_prefix;
            $q_value = 1.0;
            if (!empty($arr[5])) $q_value = floatval($arr[5]);

            if (in_array($language, $languages) && ($q_value > $best_q_val))
            {
                $best_lang = $language;
                $best_q_val = $q_value;
            }
            else if (in_array($lang_prefix, $languages) && (($q_value * 0.9) > $best_q_val))
            {
                $best_lang = $lang_prefix;
                $best_q_val = $q_value * 0.9;
            }
        }
        return $best_lang;
    }

    /**
     * Load language table
     *
     * @static
     * @param $lang
     * @return array
     */
    private static function _load($lang)
    {
        if (isset(self::$cache[$lang])) return self::$cache[$lang];
        if (!self::$enable) return false;


        $table = array();
        $parts = explode('-', $lang);
        $path = implode('/', $parts);

        $files = array(
            App::$config['langpath'] . '/' . $path . '.php',
            App::$config['langpath'] . '/' . $lang . '.php',
            App::$config['langpath'] . '/' . strstr($lang, '-', true) . '.php',
        );

        foreach ($files as $file)
        {
            if (file_exists($file))
            {
                $table = include($file);
                break;
            }
        }

        return self::$cache[$lang] = $table;
    }
}

function __($string, array $values = NULL, $lang = 'en')
{
    if ($lang !== I18n::lang()) $string = I18n::get($string);
    return empty($values) ? $string : strtr($string, $values);
}