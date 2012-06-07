<?php
/**
 * OmniApp Framework
 *
 * @package   OmniApp
 * @author    Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license   http://www.apache.org/licenses/LICENSE-2.0

 */

const VERSION = '0.1';

const EVENT_INIT = 'init';
const EVENT_RUN = 'run';
const EVENT_END = 'end';
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
    public static $config;
    private static $_init = false;
    private static $start_time = null;
    private static $start_memory = null;

    /**
     * App init
     *
     * @static
     *
     * @param array $config
     */
    public static function init($config = array())
    {
        foreach (array('route', 'event') as $m)
        {
            if (!isset($config[$m])) $config[$m] = array();
        }

        self::$config = $config;

        spl_autoload_register(array(__CLASS__, '__autoload'));
        register_shutdown_function(array(__CLASS__, '__shutdown'));

        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        if (!empty($config['timezone'])) date_default_timezone_set($config['timezone']);
        if (!isset($config['error']) || $config['error'] === true) self::registerErrorHandler();

        self::$start_time = $_SERVER['REQUEST_TIME'];
        self::$start_memory = memory_get_usage();

        self::$_init = true;
        Event::add(EVENT_INIT, $config);
    }

    /**
     * Check sapi
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
        if ($is_win === null)
        {
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
     * Get app start memory usage
     *
     * @static
     * @return int
     */
    public static function startMemory()
    {
        return self::$start_memory;
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
        Event::add(EVENT_RUN);
        $path = self::isCli() ? join('/', array_slice($GLOBALS['argv'], 1)) : Request::path();
        list($controller, $route, $params) = Route::parse($path);

        if (is_string($controller))
        {
            Controller::factory($controller, $params);
        }
        else
        {
            call_user_func_array($controller, $params);
        }
        Event::add(EVENT_END);
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
     * Restore error and exception hanlders
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
     *
     * @param $class
     */
    public static function __autoload($class)
    {
        $class = ltrim($class, '\\');

        if (is_array(self::$config['classpath']))
        {
            $available_path = array(self::$config['classpath']['']);

            foreach (self::$config['classpath'] as $prefix => $path)
            {
                if ($prefix == '') continue;

                if (strtolower(substr($class, 0, strlen($prefix))) == strtolower($prefix))
                {
                    array_unshift($available_path, $path);
                    break;
                }
            }
        }
        else
        {
            $available_path = array(self::$config['classpath']);
        }

        foreach ($available_path as $path)
        {
            $file = stream_resolve_include_path($path . '/' . strtolower(str_replace('\\', '/', $class) . '.php'));
            if ($file)
            {
                require $file;
                break;
            }
        }

        Event::add(EVENT_AUTOLOAD, $class);
    }

    /**
     * Error handler for app
     *
     * @static
     *
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     *
     * @throws ErrorException
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
     *
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

     */
    public static function __shutdown()
    {
        if (!self::isInit()) return;

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
class ArrayObjectWrapper extends ArrayObject
{
    public function __set($name, $val)
    {
        $this[$name] = $val;
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this))
        {
            $ret = &$this[$name];
        }
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
     *
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
     *
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
     *
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
        if (!App::$config['viewpath']) throw new Exception('No viewpath found.');
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
     *
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
     *
     * @param $path
     *
     * @return array
     * @throws Exception
     */
    public static function parse($path)
    {
        if (empty(App::$config['route'])) throw new Exception('No routes found.');

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

        if (!isset(App::$config['route']['404'])) throw new Exception('404 page found, but no 404 route found.');
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
        if (!self::$url)
        {
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
        if (!self::$track_id)
        {
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
     *
     * @param null $key
     *
     * @return mixed
     */
    public static function header($key = null)
    {
        if (!self::$headers)
        {
            $headers = array();
            foreach ($_SERVER as $key => $value)
            {
                if ('HTTP_' === substr($key, 0, 5))
                {
                    $headers[strtolower(substr($key, 5))] = $value;
                }
                elseif (in_array($key, array('CONTENT_LENGTH',
                                             'CONTENT_MD5',
                                             'CONTENT_TYPE'))
                )
                {
                    $headers[strtolower($key)] = $value;
                }
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
     *
     * @param string $content
     *
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
     *
     * @param int $status
     *
     * @return int|Response
     * @throws Exception
     */
    public static function status($status = NULL)
    {
        if ($status === NULL)
        {
            return self::$status;
        }
        elseif (array_key_exists($status, self::$messages))
        {
            return self::$status = (int)$status;
        }
        else throw new Exception('Unknown status :value', array(':value' => $status));
    }

    /**
     * Set header
     *
     * @static
     *
     * @param null $key
     * @param null $value
     *
     * @return array
     */
    public static function header($key = NULL, $value = NULL)
    {
        if ($key === NULL)
        {
            return self::$headers;
        }
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
     *
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
 * Cli
 */
class Cli
{
    /**
     * Returns one or more command-line options. Options are specified using
     * standard CLI syntax:
     *     php index.php --username=john.smith --password=secret --var="some value with spaces"
     *     // Get the values of "username" and "password"
     *     $auth = CLI::options('username', 'password');
     *
     * @param   string  option name
     * @param   ...
     *
     * @return  array
     */
    public static function options()
    {
        $options = func_get_args();
        $values = array();

        for ($i = 1; $i < $_SERVER['argc']; $i++)
        {
            if (!isset($_SERVER['argv'][$i])) break;

            $opt = $_SERVER['argv'][$i];
            if (substr($opt, 0, 2) !== '--') continue;
            $opt = substr($opt, 2);

            if (strpos($opt, '=')) {
                list ($opt, $value) = explode('=', $opt, 2);
            }
            else $value = NULL;

            if (in_array($opt, $options)) $values[$opt] = $value;
        }

        return $values;
    }

    /**
     * Color output text for the CLI
     *
     * @param string      $text       to color
     * @param string      $color      of text
     * @param bool|string $bold       color
     *
     * @return string
     */
    public static function colorize($text, $color, $bold = FALSE)
    {
        $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black'));
        return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . "m$text\033[0m";
    }

}

/**
 * I18n
 */
class I18n
{
    protected static $lang = 'en-US';
    protected static $cache = array();
    protected static $enable = false;
    protected static $config;

    /**
     * Init i18n config
     *
     * @static
     *
     * @param $config
     *
     * @throws Exception
     */
    public static function init()
    {
        if (!isset(App::$config['i18n'])) return;

        self::$config = &App::$config['i18n'];
        if (self::$config['lang'] && self::$config['dir']) self::$enable = true;
        self::lang(I18n::preferLanguage(self::$config['lang']));
    }

    /**
     * Get or set language
     *
     * @static
     *
     * @param null|string $lang
     *
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
     *
     * @param             $string
     * @param null|string $lang
     *
     * @return string
     */
    public static function get($string, $lang = NULL)
    {
        if (!$lang) $lang = self::$lang;
        $table = self::load($lang);
        return isset($table[$string]) ? $table[$string] : $string;
    }

    /**
     * Automatic match best language
     *
     * @static
     *
     * @param array $languages
     *
     * @return string
     */
    public static function preferLanguage($languages = array())
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
            else
            {
                if (in_array($lang_prefix, $languages) && (($q_value * 0.9) > $best_q_val))
                {
                    $best_lang = $lang_prefix;
                    $best_q_val = $q_value * 0.9;
                }
            }
        }
        return $best_lang;
    }

    /**
     * Load language table
     *
     * @static
     *
     * @param $lang
     *
     * @return array
     */
    private static function load($lang)
    {
        if (isset(self::$cache[$lang])) return self::$cache[$lang];
        if (!self::$enable) return false;

        $table = array();
        $parts = explode('-', $lang);
        $path = implode('/', $parts);

        $files = array(
            self::$config['dir'] . '/' . $path . '.php',
            self::$config['dir'] . '/' . $lang . '.php',
            self::$config['dir'] . '/' . strstr($lang, '-', true) . '.php',
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

/**
 * Log
 * @method static debug
 * @method static info
 * @method static warn
 * @method static error
 * @method static emerg
 */
class Log
{
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARN = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_EMERG = 4;

    protected static $config;
    protected static $messages = array();
    protected static $filename = ':level.log';
    private static $levels = array(
        'debug'    => self::LEVEL_DEBUG,
        'info'     => self::LEVEL_INFO,
        'warn'     => self::LEVEL_WARN,
        'error'    => self::LEVEL_ERROR,
        'emerg'    => self::LEVEL_EMERG
    );

    /**
     * Init log config
     *
     * @static
     * @internal param $config
     */
    public static function init()
    {
        self::$config = &App::$config['log'];
        if (!isset(self::$config['dir'])) self::$config['dir'] = '.';
        if (!isset(self::$config['level'])) self::$config['level'] = self::LEVEL_DEBUG;

        Event::on(EVENT_SHUTDOWN, function()
        {
            Log::save();
        });
    }

    /**
     * call level name as method
     *
     * @static
     *
     * @param $name
     * @param $arguments
     */
    public static function __callstatic($name, $arguments)
    {
        if (isset(self::$levels[$name])) self::write($arguments[0], self::$levels[$name], $name);
    }

    /**
     * Record log
     *
     * @static
     *
     * @param      $text
     * @param int  $level
     * @param null $tag
     *
     * @return bool
     */
    public static function write($text, $level = self::LEVEL_INFO, $tag = null)
    {
        if ($level < self::$config['level']) return false;

        $micro_time = microtime(true);
        $message = array(
            'id'       => Request::trackId(),
            'time'     => $micro_time,
            'text'     => $text,
            'ip'       => getenv('REMOTE_ADDR'),
            'level'    => $tag ? $tag : array_search($level, self::$levels),
            'memory'   => memory_get_usage(),
            'datetime' => date('Y-m-d H:i:s', $micro_time) . substr($micro_time - floor($micro_time), 1, 4),
            'date'     => date('Y-m-d', floor($micro_time)),
        );
        self::$messages[] = $message;
        return true;
    }

    /**
     * Save log message
     *
     * @static
     * @return bool
     */
    public static function save()
    {
        if (empty(self::$messages)) return;

        $log_filename = empty(self::$config['filename']) ? self::$filename : self::$config['filename'];
        $dir_exists = array();

        foreach (self::$messages as $message)
        {
            $replace = array();
            foreach ($message as $k => $v) $replace[':' . $k] = $v;

            $header = '[' . $message['datetime'] . '] [' . str_pad($message['level'], 7, ' ', STR_PAD_BOTH) . '] ';
            $text = $header . str_replace("\n", "\n{$header}", trim($message['text'], "\n"));

            $filename = self::$config['dir'] . '/' . strtr($log_filename, $replace);
            $dir = dirname($filename);
            if (!in_array($dir, $dir_exists))
            {
                if (!is_dir($dir))
                {
                    if (mkdir($dir, 0777, true)) $dir_exists[] = $dir;
                }
                else $dir_exists[] = $dir;
            }

            if (in_array($dir, $dir_exists)) file_put_contents($filename, $text . "\n", FILE_APPEND);
        }
    }
}

/**
 * I18n translate function
 *
 * @param            $string
 * @param array|null $values
 * @param string     $lang
 *
 * @return string
 */
function __($string, array $values = NULL, $lang = 'en')
{
    if ($lang !== I18n::lang()) $string = I18n::get($string);
    return empty($values) ? $string : strtr($string, $values);
}