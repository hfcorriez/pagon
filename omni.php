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
     * @var Env
     */
    public static $env;
    
    private static $_init = false;

    public static function init($config = array())
    {
        if (!empty($config['event'])) 
        {
            Event::init($config['event']);
            unset($config['event']);
        }
        Event::add(EVENT_INIT);
        
        iconv_set_encoding("internal_encoding", "UTF-8");
        mb_internal_encoding('UTF-8');
        self::$config = new Config($config);
        date_default_timezone_set(self::$config->timezone ? self::$config->timezone : 'UTC');
        
        self::$env = Env::instance();
        self::$env->lang = Env::preferedLanguage(self::$config->lang);
        if (!self::$env->is_cli) self::$request = Request::instance();
        I18n::$lang = self::$env->lang;
        
        if (!self::$config->apppath) throw new Exception('Config->apppath must be set before.');
        if (self::$config->error) self::register_error_handler();
        if (self::$config->classpath) spl_autoload_register(array(__CLASS__, '__autoloader'));
        
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

    private static function __autoloader($class_name)
    {
        Event::add(EVENT_AUTOLOAD, $class_name);
        $class_name = ltrim($class_name, '\\');
        require self::$config->classpath . '/' . strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php');
    }

    public static function __error($type, $message, $file, $line)
    {
        Event::add(EVENT_ERROR, $type, $message, $file, $line);
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    public static function __exception(\Exception $e)
    {
        Event::add(EVENT_EXCEPTION, $e);
        $type    = get_class($e);
        $code    = $e->getCode();
        $message = $e->getMessage();
        $file    = $e->getFile();
        $line    = $e->getLine();
        $trace = $e->getTrace();
        $text = sprintf('%s [ %s ]: %s ~ %s [ %d ]', $type, $code, $message, $file, $line);
        Logger::error($text);
        
        if (!self::$env->is_cli AND !headers_sent()) header(self::$request->protocol . ' 500 Internal Server Error');
        
        if (self::$env->is_cli OR self::$request->is_ajax === TRUE)
        {
        	echo "\n{$text}\n";
        	exit(1);
        }
        
        echo Exception::getView($e);
        exit(1);
    }

    public static function __shutdown()
    {
        Event::add(EVENT_SHUTDOWN);
        Logger::save();
        
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
    public function __construct()
    {
        //
    }
}

class Env extends Instance
{
    public $lang = 'en-US';
    public $is_cli;
    public $is_win;
    public $start_time;
    public $start_memory;
    public $timezone = 'UTC';
    public $charset = 'UTF-8';
    public $basename;
    public $argv;
    public $_;
    
    public function __construct()
    {
        $this->is_cli = (PHP_SAPI == 'cli');
        $this->is_win = (substr(PHP_OS, 0, 3) == 'WIN');
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();
        $this->timezone = date_default_timezone_get();
        
        if ($this->is_cli)
        {
            $argv = $GLOBALS['argv'];
            $this->_ = array_shift($argv);
            $this->argv = $argv;
        }
        else $this->_ = $_SERVER['SCRIPT_FILENAME'];
        
        $this->basename = basename($this->_);
    }
    
    public static function preferedLanguage($languages = array())
    {
        if (isset($_COOKIE['lang'])) return $_COOKIE['lang'];
        if (!$languages) return 'en-US';
        
        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" . "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $hits, PREG_SET_ORDER);
        $bestlang = $languages[0];
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
             
            if (in_array($language,$languages) && ($qvalue > $bestqval))
            {
                $bestlang = $language;
                $bestqval = $qvalue;
            }
            else if (in_array($langprefix,$languages) && (($qvalue*0.9) > $bestqval))
            {
                $bestlang = $langprefix;
                $bestqval = $qvalue*0.9;
            }
        }
        return $bestlang;
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
    
        if (!isset($routes['404'])) throw new Exception('Config->routes["404"] not set.');
        return array($routes['404'], $path, array($path));
    }
}

class Model {}

abstract class Controller
{
    abstract function before();
    abstract function after();
    
    public static function factory($controller, $params = array())
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

class View
{
    private $_file = NULL;

    public function __construct($view)
    {
        $this->_file = (App::$config->viewpath ? App::$config->viewpath : App::$config->apppath) . '/' . strtolower($view) . '.php';
        if (!is_file($this->_file)) throw new Exception('View file not exist: ' . $this->_file);
    }

    public function set($array)
    {
        foreach ($array as $k => $v) $this->$k = $v;
    }

    public function __toString()
    {
        ob_start();
        extract((array) $this);
        require $this->_file;
        return ob_get_clean();
    }
}

abstract class Event
{
    private static $_events = array();
    
    public static function init($events = array())
    {
        foreach ($events as $name => $event) self::$_events[$name] = !empty(self::$_events[$name]) ? array_merge(self::$_events[$name], $event) : $event;
    }
    
    public static function on($name, $runner)
    {
        self::$_events[$name][] = $runner;
    }
    
    public static function add($name)
    {
        $params = array_slice(func_get_args(), 1);
        if (empty(self::$_events[$name])) return;
        foreach (self::$_events[$name] as $runner) self::__excute($runner, $params);
    }
    
    private static function __excute($runner, $params = array())
    {
        if (is_object($runner) && get_class($runner) == 'Closure') return call_user_func_array($runner, $params);
        else return call_user_func_array(array(new $runner(), 'run'), $params);
    }
    
    abstract function run();
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

class Exception extends \Exception {
    
    public static function getView(\Exception $e)
    {
        $html = '<div style="border:1px solid #990000; padding:10px 20px; margin:10px; font: 13px/1.4em verdana; background: #fff;">
        <b style="color: #990000">'. get_class($e) .'[' . $e->getCode() . '] in ' . $e->getFile() .' [' . $e->getLine() . ']</b>
        <p>' . $e->getMessage() . '</p>';
        
        if ($backtrace = array_slice(debug_backtrace(), 1, 5))
        {
            foreach ($backtrace as $id => $line)
            {
                if (empty($line['file'])) continue;
        
                $html .= '<div class="box" style="margin: 1em 0; background: #ebf2fa; padding: 10px; border: 1px solid #bedbeb;">';
                if ($id !== 0 )
                {
                    $html .= '<b>Called by '. (isset($line['class']) ? $line['class']. $line['type'] : '');
                    $html .= $line['function']. '()</b>';
                }
                $html .= ' in '. $line['file']. ' ['. $line['line']. ']';
                if (!empty($line['source']))$html .= '<code class="source" style="white-space: pre; background: #fff; padding: 1em; display: block; margin: 1em 0; border: 1px solid #bedbeb;">'. $line['source']. '</code>';
        
                if (!empty($line['args']))
                {
                    $html .= '<div><b>Function Arguments</b><xmp>';
                    $html .= print_r($line['args'], true);
                    $html .= '</xmp></div>';
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}

class I18n 
{
    public static $lang = 'en-US';
    protected static $_cache = array();

    public static function lang($lang = NULL)
    {
        if ($lang) self::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
        return self::$lang;
    }
    
    public static function get($string, $lang = NULL)
    {
        if (!$lang) $lang = self::$lang;
        $table = self::load($lang);
        return isset($table[$string]) ? $table[$string] : $string;
    }
    
    public static function load($lang)
    {
        if (isset(self::$_cache[$lang])) return self::$_cache[$lang];

        $table = array();
        $parts = explode('-', $lang);
        $path = implode(DIRECTORY_SEPARATOR, $parts);

        $file = App::$config->langpath . '/' . $path . '.php';
        if (file_exists($file)) $table = include($file);

        return self::$_cache[$lang] = $table;
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