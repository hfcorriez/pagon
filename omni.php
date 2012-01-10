<?php
/**
* OmniApp Framework
*
* @package		OmniApp
* @author		Corrie Zhao <hfcorriez@gmail.com>
* @copyright	(c) 2011 OmniApp Framework
* @todo 		Support Cli, I18n
*/
namespace OMni;

/**
 * App Class
 */
class App
{
    public static $config;
    public static $request;
    public static $env;

    private static $logs = array();

    const LOG_LEVEL_DEBUG = 0;
    const LOG_LEVEL_NOTICE = 1;
    const LOG_LEVEL_WARN = 2;
    const LOG_LEVEL_ERROR = 3;
    const LOG_LEVEL_CRITICAL = 4;

    public static function init($config = array())
    {
        self::$config = new ArrayObjectWrapper($config);
        if(!self::$config->apppath) throw new Exception('Config::apppath must be set before.');
        
        if (self::$config->error)
        {
            self::register_error_handler();
        }

        if(self::$config->classpath) spl_autoload_register(array(__CLASS__, '__autoloader'));
        
        self::__init();
    }

    public static function start()
    {
        self::dispatch(self::$request['path']);
    }

    public static function dispatch($path)
    {
        list($controller, $route, $params) = self::route($path);
        if(gettype($controller) == 'object' && get_class($controller) == 'Closure')
        {
            call_user_func_array($controller, $params);
        }
        else
       {
            self::controller($controller, $params);
        }
    }
    
    public static function controller($controller, $params)
    {
        $controller = new $controller($params);
        $request_methods = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD');
        $method = self::$request['method'];

        if(!in_array($method, $request_methods) || !method_exists($controller, $method))
        {
            if( ! method_exists($controller, 'run'))
            {
                throw new Exception('Invalid Request Method.');
            }
            $method = 'run';
        }

        $controller->before($method);
        if($params)
        {
            call_user_func_array(array($controller, $method), $params);
        }
        else
        {
            $controller->$method();
        }
        $controller->after($method);

        return $controller;
        
    }

    public static function model($name)
    {
        return Model($name);
    }

    public static function view($tpl)
    {
        return View($tpl);
    }

    public static function route($path)
    {
        $routes = self::$config->route;
        if(!is_array($routes) || empty($routes)) throw new Exception('Config::routes must be set before.');

        $path = trim($path, '/');
        if($path === '')
        {
            return array($routes[''], '', array());
        }

        if($path AND ! preg_match('/^[\w\-~\/\.]{1,400}$/', $path))
        {
            $path = '404';
        }

        foreach($routes as $route => $controller)
        {
            if( ! $route) continue;

            if($route{0} === '/')
            {
                if(preg_match($route, $path, $matches))
                {
                    $complete = array_shift($matches);
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if($params[0])
                    {
                        foreach($matches as $match)
                        {
                            array_unshift($params, $match);
                        }
                    }
                    else
                  {
                        $params = $matches;
                    }
                    return array($controller, $complete, $params);
                }
            }
            else
            {
                if(mb_substr($path, 0, mb_strlen($route)) === $route)
                {
                    $params = explode('/', trim(mb_substr($path, mb_strlen($route)), '/'));
                    return array($controller, $route, $params);
                }
            }
        }
        
        if(!isset($routes['404'])) throw new Exception('Config::routes["404"] must be set before.');
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

    public static function log($text, $level = self::LOG_LEVEL_NOTICE)
    {
        if(!self::$config->log) trigger_error('Config::log not be set.', E_USER_NOTICE);
        if($level < self::$config->log['level']) return;
        
        $log = array (
            'id' => isset($_SERVER['HTTP_REQUEST_ID']) ? $_SERVER['HTTP_REQUEST_ID'] : '',
            'time' => microtime(true),
            'text' => $text,
            'level' => $level,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'memory' => memory_get_usage(),
        );
        self::$logs[] = $log;
    }

    public static function __shutdown()
    {
        self::__logSave();
    }

    public static function __error($errno, $errstr, $errfile, $errline)
    {
        $is_log = false;
        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $is_display = false;
                $errtag = "Notice";
                $level = self::LOG_LEVEL_NOTICE;
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $is_log = true;
                $is_display = false;
                $errtag = "Warn";
                $level = self::LOG_LEVEL_WARN;
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $is_log = true;
                $errtag = "Fatal";
                $level = self::LOG_LEVEL_ERROR;
                break;
            default:
                $is_display = false;
                $errtag = "Unknown";
                $level = self::LOG_LEVEL_CRITICAL;
        }

        $text = sprintf("%s:  %s in %s on line %d (%s)", $errtag, $errstr, $errfile, $errline, self::$request->url);

        if ($is_log) self::log($text, $level);
        
        if($is_display && ($contoller = self::$config->route['error'])) self::controller($contoller, array($text));
    }

    public static function __exception($exception)
    {
        $traceline = "#%s %s(%s): %s(%s)";
        $message = "Exception: Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";
        
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            $trace[$key]['args'] = array_map('gettype', is_array($trace[$key]['args']) ? $trace[$key]['args'] : array());
        }
        
        $result = array();
        foreach ($trace as $key => $stackPoint) 
        {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        $result[] = '#' . ++$key . ' {main}';
        
        $text = sprintf(
            $message . ' (%s)',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine(),
            self::$request->url
        );
        
        self::log($text, self::LOG_LEVEL_ERROR);
        
        if($is_display && ($contoller = self::$config->route['exception'])) self::controller($contoller, array($text));
    }

    private static function __init()
    {
        // Make objective
        self::$env = new ArrayObjectWrapper(array());
        self::$request = new ArrayObjectWrapper(array());
        
        // Env init
        self::$env['is_cli'] = (PHP_SAPI == 'cli');
        self::$env['is_win'] = (substr(PHP_OS, 0, 3) == 'WIN');
        self::$env['start_time'] = microtime(true);
        self::$env['start_memory'] = memory_get_usage();

        if(!self::$env->is_cli)
        {
            // Request init
            if(empty($_SERVER['HTTP_REQUEST_ID'])) $_SERVER['HTTP_REQUEST_ID'] = md5(uniqid());
    
            self::$request['path'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            self::$request['url'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            self::$request['method'] = $_SERVER['REQUEST_METHOD'];
            self::$request['is_ajax'] = !empty($_SERVER['X-Requested-With']) && 'XMLHttpRequest' == $_SERVER['X-Requested-With'];
    
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
            self::$request['headers'] = $headers;
        }
    }

    private static function __logSave()
    {
        if(!empty(self::$logs))
        {
            if(!self::$config->log['dir'])
            {
                trigger_error('Config::log["dir"] not set.', E_USER_NOTICE);
                return;
            }
            
            $dir = self::$config->log['dir'] . '/' . date('Ymd');
            if (!is_dir($dir))
            {
                mkdir($dir, 0777);
                chmod($dir, 0777);
            }
            $logs_wraper = array();
            foreach(self::$logs as $log)
            {
                $logs_wraper[$log['level']][] = '[' . date('Y-m-d H:i:s', $log['time']) . '] ' . ($log['id'] ? "#{$log['id']} " : '') . $log['text'];
            }

            foreach ($logs_wraper as $level => $wraper)
            {
                $file = $dir . '/' . $level . '.log';
                file_put_contents($file, join(PHP_EOL, $wraper) . PHP_EOL, FILE_APPEND);
            }
        }
    }

    private static function __autoloader($class_name)
    {
        $class_name = ltrim($class_name, '\\');
        $fileName  = '';
        $namespace = '';

        if ($lastNsPos = strripos($class_name, '\\'))
        {
            $namespace = substr($class_name, 0, $lastNsPos);
            $class_name = substr($class_name, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }

        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

        require self::$config->classpath . '/' . strtolower($fileName);
    }

    private static function __arrayObjectWrapper($array = false)
    {
        if(is_array($array)) return new ArrayObjectWrapper($array);
        else return $array;
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
        foreach($array as $k => $v)
        {
            $this->$k = $v;
        }
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
    public function __construct($name)
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

    public function __get($name) 
    {
        return isset($this[$name]) ? $this[$name] : null;
    }
    
    public function __isset($name)
    {
        return isset($this[$name]);
    }
}

abstract class Controller
{
    abstract function before();
    abstract function after();
}