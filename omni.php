<?php
/**
* OmniApp Framework
*
* @package		OmniApp
* @author		Corrie Zhao <hfcorriez@gmail.com>
* @copyright	(c) 2011 OmniApp Framework
*/
namespace OMni
{

const LOG_LEVEL_DEBUG = 0;
const LOG_LEVEL_NOTICE = 1;
const LOG_LEVEL_WARN = 2;
const LOG_LEVEL_ERROR = 3;
const LOG_LEVEL_CRITICAL = 4;

const DEFAULT_TIMEZONE = 'UTC';
const DEFAULT_LANG = 'en-US';

/**
 * App Class
 */
class App
{
    public static $config = array();
    public static $request = array();
    public static $env = array();
    
    private static $init = false;

    public static function init($config = array())
    {
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        self::$config = new ArrayObjectWrapper($config);
        date_default_timezone_set(self::$config->timezone ? self::$config->timezone : DEFAULT_TIMEZONE);
        
        Event::add('init');
        self::__init();
        
        I18n::$lang = self::$env->lang;
        
        if (!self::$config->apppath) throw new Exception('Config->apppath must be set before.');
        if (self::$config->error) self::register_error_handler();
        if (self::$config->classpath) spl_autoload_register(array(__CLASS__, '__autoloader'));
        
        register_shutdown_function(array(__CLASS__, '__shutdown'));
        self::$init = true;
    }
    
    public static function isInit(){ return self::$init; }

    public static function run()
    {
        Event::add('run');
        return self::dispatch(self::$env->is_cli ? join('/', self::$env->argv) : self::$request->path);
    }

    public static function dispatch($path)
    {
        list($controller, $route, $params) = self::route($path);
        
        if (is_object($controller) && get_class($controller) == 'Closure') return call_user_func_array($controller, $params);
        else return Controller::start($controller, $params);
    }

    public static function route($path)
    {
        $routes = self::$config->route;
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
        
        if (!isset($routes['404'])) throw new Exception('Config->routes["404"] not set.');
        return array($routes['404'], $path, array($path));
    }

    public static function register_error_handler()
    {
        set_error_handler(array(__CLASS__, '__error'));
        set_exception_handler(array(__CLASS__, '__exception'));
    }
    
    public static function restore_error_handler()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public static function __error($type, $message, $file, $line)
    {
        Event::add('error', array('type'=>$type, 'message'=>$message, 'file'=>$file, 'line'=>$line));
        $is_log = true;
        $is_display = false;
        switch ($type) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $is_log = false;
                $tag = "Notice";
                $level = LOG_LEVEL_NOTICE;
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $tag = "Warn";
                $level = LOG_LEVEL_WARN;
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $is_display = true;
                $tag = "Error";
                $level = LOG_LEVEL_ERROR;
                break;
            default:
                $tag = "Critical";
                $level = LOG_LEVEL_CRITICAL;
        }
        $text = sprintf("%s: %s in %s on line %d (%s)", $tag, $message, $file, $line, self::$request->url);

        if ($is_log) Logger::error($text);
        if ($is_display && ($contoller = self::$config->route['error'])) Controller::start($contoller, array($text));
    }

    public static function __exception(\Exception $exception)
    {
        Event::add('exception', $exception);
        $text = $exception->getTraceAsString() . " ({self::$request->url})";
        
        Logger::error($text);
        if ($contoller = self::$config->route['exception']) Controller::start($contoller, array($text));
    }

    public static function __shutdown()
    {
        Event::add('shutdown');
        if ($error = error_get_last())
        {
            if (in_array($error['type'], array(E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR)))
                Logger::critical(sprintf("Critical: %s in %s on line %d (%s)", $error['message'], $error['file'], $error['line'], self::$request->url));
        }
         
        Logger::save();
    }
    
    private static function __preferedLanguage()
    {
        if (isset($_COOKIE['lang'])) return $_COOKIE['lang'];
        if (!self::$config->lang || !is_array(self::$config->lang)) return DEFAULT_LANG;
        
        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" . "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $hits, PREG_SET_ORDER);
        $bestlang = self::$config->lang[0];
        $bestqval = 0;
        
        foreach ($hits as $arr)
        {
            $langprefix = strtolower ($arr[1]);
            if (!empty($arr[3]))
            {
                $langrange = strtolower ($arr[3]);
                $language = $langprefix . "-" . $langrange;
            }
            else $language = $langprefix;
            $qvalue = 1.0;
            if (!empty($arr[5])) $qvalue = floatval($arr[5]);
             
            if (in_array($language,self::$config->lang) && ($qvalue > $bestqval))
            {
                $bestlang = $language;
                $bestqval = $qvalue;
            }
            else if (in_array($langprefix,self::$config->lang) && (($qvalue*0.9) > $bestqval))
            {
                $bestlang = $langprefix;
                $bestqval = $qvalue*0.9;
            }
        }
        return $bestlang;
    }

    private static function __init()
    {
        // Make objective
        self::$env = new ArrayObjectWrapper(array());
        self::$request = new ArrayObjectWrapper(array());
        
        // Env init
        self::$env->lang = self::__preferedLanguage();
        self::$env->is_cli = (PHP_SAPI == 'cli');
        self::$env->is_win = (substr(PHP_OS, 0, 3) == 'WIN');
        self::$env->start_time = microtime(true);
        self::$env->start_memory = memory_get_usage();
        self::$env->timezone = date_default_timezone_get();
        self::$env->charset = 'UTF-8';

        if (self::$env->is_cli)
        {
            $argv = $GLOBALS['argv'];
            self::$env->_ = array_shift($argv);
            self::$env->argv = $argv;
        }
        else
       {
            self::$env->_ = $_SERVER['SCRIPT_FILENAME'];
            // Request init
            if (empty($_SERVER['HTTP_TRACK_ID'])) $_SERVER['HTTP_TRACK_ID'] = md5(uniqid());
    
            self::$request->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            self::$request->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            self::$request->method = $_SERVER['REQUEST_METHOD'];
            self::$request->is_ajax = !empty($_SERVER['X-Requested-With']) && 'XMLHttpRequest' == $_SERVER['X-Requested-With'];
    
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' === substr($key, 0, 5)) {
                    $headers[strtolower(substr($key, 5))] = $value;
                } elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
                    $headers[strtolower($key)] = $value;
                }
            }
    
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['authorization'] = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$pass);
            }
            self::$request->headers = $headers;
        }
        self::$env->basename = basename(self::$env->_); 
    }
    
    private static function __autoloader($class_name)
    {
        Event::add('autoload', $class_name);
        $class_name = ltrim($class_name, '\\');
        require self::$config->classpath . '/' . strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php');
    }
}

class View
{

    private $_view = NULL;

    public function __construct($file)
    {
        $this->_view = $file;
    }


    public function set($array)
    {
        foreach ($array as $k => $v) $this->$k = $v;
    }

    public function __toString()
    {
        ob_start();
        extract((array) $this);
        require (App::$config->viewpath ? App::$config->viewpath : App::$config->apppath) . '/' . strtolower($this->_view) . '.php';
        return ob_get_clean();
    }
}

class Model
{
    public function __construct()
    {
        //
    }
}

class Exception extends \Exception {}

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
}

abstract class Event
{
    public static function on($name, $runner)
    {
        if (!App::isInit()) throw new Exception('App is not init.');
        if (!isset(App::$config->event[$name])) App::$config->event[$name] = array();
        App::$config->event[$name][] = $runner;
    }
    
    public static function add($name, $params = array())
    {
        if (empty(App::$config->event[$name])) return;
        foreach (App::$config->event[$name] as $runner) self::__excute($runner, $params);
    }
    
    private static function __excute($runner, $params)
    {
        if (is_object($runner) && get_class($runner) == 'Closure') return $runner($params);
        else
        {
            $event = new $runner();
            return $event->run($params);
        }
    }
    
    abstract function run();
}

abstract class Controller
{
    abstract function before();
    abstract function after();
    
    public static function start($controller, $params = array())
    {
        $controller = new $controller();
        $request_methods = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD');
        $method = App::$request->method;
        
        if (!in_array($method, $request_methods) || !method_exists($controller, $method))
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

class I18n 
{
    public static $lang = 'en-US';
    protected static $_cache = array();

    public static function lang($lang = NULL)
    {
        if ($lang) I18n::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
        return I18n::$lang;
    }
    
    public static function get($string, $lang = NULL)
    {
        if (!$lang) $lang = I18n::$lang;
        $table = I18n::load($lang);
        return isset($table[$string]) ? $table[$string] : $string;
    }
    
    public static function load($lang)
    {
        if (isset(I18n::$_cache[$lang])) return I18n::$_cache[$lang];

        $table = array();
        $parts = explode('-', $lang);
        $path = implode(DIRECTORY_SEPARATOR, $parts);

        $file = App::$config->langpath . '/' . $path . '.php';
        if (file_exists($file)) $table = include($file);

        return I18n::$_cache[$lang] = $table;
    }
}

class Logger
{
    public static $logs;
    public static $path_format = '$date/$tag.log';
    public static $log_format = '[$datetime] #$id $text';
    
    private static $_levels = array('debug'=>LOG_LEVEL_DEBUG, 'notice'=>LOG_LEVEL_NOTICE, 'warn' => LOG_LEVEL_WARN, 'error' => LOG_LEVEL_ERROR, 'critical' => LOG_LEVEL_CRITICAL);
    
    public static function __callstatic($name, $argments)
    {
        if (isset(self::$_levels[$name])) self::write($argments[0], self::$_levels[$name], $name);
    }
    
    public static function write($text, $level = LOG_LEVEL_NOTICE, $tag = false)
    {
        if (!App::$config->log) return trigger_error('Config->log not set.', E_USER_NOTICE);
        if ($level < App::$config->log['level']) return;
    
        $log = array (
            'id' => isset($_SERVER['HTTP_TRACK_ID']) ? $_SERVER['HTTP_TRACK_ID'] : '',
            'time' => microtime(true),
            'text' => $text,
            'level' => $level,
            'tag' => $tag ? $tag : array_search($level, self::$_levels),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'memory' => memory_get_usage(),
        );
        self::$logs[] = $log;
    }
    
    public static function save()
    {
        if (empty(self::$logs)) return;
        if (!App::$config->log['dir']) return trigger_error('Config->log["dir"] not set.', E_USER_NOTICE);
        
        $index = strrpos(self::$path_format, '/');
        $dir_format = $index !== false ? substr(self::$path_format, 0, $index) : '';
        $file_format = $index !== false ? substr(self::$path_format, $index+1) : self::$path_format;

        $dir = App::$config->log['dir'] . '/' . strtr($dir_format, array('$date'=>date('Ymd')));
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $logs_wraper = array();
        foreach (self::$logs as $log)
        {
            $replace = array();
            $log['datetime'] = (date('Y-m-d H:i:s', $log['time'])) . substr($log['time'] - floor($log['time']), 1, 4); 
            foreach ($log as $k=>$v) $replace['$' . $k] = $v; 
            $logs_wraper[$log['tag']][] = strtr(self::$log_format, $replace);
        }
        foreach ($logs_wraper as $tag => $wraper) file_put_contents($dir . '/' . strtr($file_format, array('$tag'=>$tag)), join(PHP_EOL, $wraper) . PHP_EOL, FILE_APPEND);
    }
}

}
// global functions
namespace {
    
if (!function_exists('__'))
{
    function __($string, array $values = NULL, $lang = 'en')
    {
        if ($lang !== \Omni\I18n::$lang) $string = \Omni\I18n::get($string);
        return empty($values) ? $string : strtr($string, $values);
    }
}

}