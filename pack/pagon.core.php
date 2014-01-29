<?php

namespace Pagon {

use Pagon\Exception\Pass;
use Pagon\Exception\Stop;
use Pagon\Http\Input;
use Pagon\Http\Output;
/*
 * (The MIT License)
 *
 * Copyright (c) 2013 hfcorriez@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


class Fiber implements \ArrayAccess
{
    protected $injectors = array();

    public function __construct(array $injectors = array())
    {
        $this->injectors = $injectors + $this->injectors;
    }

    public function __set($key, $value)
    {
        $this->injectors[$key] = $value;
    }

    public function &__get($key)
    {
        if (!isset($this->injectors[$key])) {
            trigger_error('Undefined index "' . $key . '" of ' . get_class($this), E_USER_NOTICE);
            return null;
        }

       
        if (is_array($this->injectors[$key])
            && isset($this->injectors[$key]['fiber'])
            && isset($this->injectors[$key][0])
            && $this->injectors[$key][0] instanceof \Closure
        ) {
            switch ($this->injectors[$key]['fiber']) {
                case 1:
                   
                    $this->injectors[$key] = call_user_func($this->injectors[$key][0]);
                    $tmp = & $this->injectors[$key];
                    break;
                case 0:
                   
                    $tmp = call_user_func($this->injectors[$key][0]);
                    break;
                default:
                    $tmp = null;
            }
        } else {
            $tmp = & $this->injectors[$key];
        }
        return $tmp;
    }

    public function __isset($key)
    {
        return isset($this->injectors[$key]);
    }

    public function __unset($key)
    {
        unset($this->injectors[$key]);
    }

    public function inject($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }

        return $key ? ($this->injectors[$key] = array($closure, 'fiber' => 0)) : array($closure, 'fiber' => 0);
    }

    public function share($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }

        return $key ? ($this->injectors[$key] = array($closure, 'fiber' => 1)) : array($closure, 'fiber' => 1);
    }

    public function extend($key, \Closure $closure)
    {
        $factory = isset($this->injectors[$key]) ? $this->injectors[$key] : null;
        $that = $this;
        return $this->injectors[$key] = array(function () use ($closure, $factory, $that) {
            return $closure(isset($factory[0]) && isset($factory['fiber']) && $factory[0] instanceof \Closure ? $factory[0]() : $factory, $that);
        }, 'fiber' => isset($factory['fiber']) ? $factory['fiber'] : 0);
    }

    public function __call($method, $args)
    {
        if (!isset($this->injectors[$method]) || !($closure = $this->injectors[$method]) instanceof \Closure) {
            throw new \BadMethodCallException(sprintf('Call to undefined method "%s::%s()', get_called_class(), $method));
        }

        return call_user_func_array($closure, $args);
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->injectors;
        }

        return isset($this->injectors[$key]) ? $this->injectors[$key] : $default;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->injectors[$k] = $v;
            }
        } else {
            $this->injectors[$key] = $value;
        }

        return $this;
    }

    public function keys()
    {
        return array_keys($this->injectors);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->injectors);
    }

    public function append(array $injectors)
    {
        $this->injectors = $injectors + $this->injectors;
        return $this;
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function &offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }
}

if (function_exists('FNMATCH')) {
    define('FNMATCH', true);
} else {
    define('FNMATCH', false);
}

class EventEmitter extends Fiber
{
    protected $listeners = array();

    public function emit($event, $args = null)
    {
        $event = strtolower($event);

        if ($args !== null) {
           
            $args = array_slice(func_get_args(), 1);
        } else {
            $args = array();
        }

        $all_listeners = array();

        foreach ($this->listeners as $name => $listeners) {
            if (strpos($name, '*') === false || !self::match($name, $event)) {
                continue;
            }

            foreach ($this->listeners[$name] as &$listener) {
                $all_listeners[$name][] = & $listener;
            }
        }

        if (!empty($this->listeners[$event])) {
            foreach ($this->listeners[$event] as &$listener) {
                $all_listeners[$event][] = & $listener;
            }
        }

        $emitted = false;

       
        foreach ($all_listeners as $name => $listeners) {
            $this_args = $args;
            if (strpos($name, '*') !== false) {
                array_push($this_args, $event);
            }
            foreach ($listeners as &$listener) {
                if ($listener instanceof \Closure) {
                   
                    call_user_func_array($listener, $this_args);
                    $emitted = true;
                } elseif (is_array($listener) && $listener[0] instanceof \Closure) {
                    if ($listener[1]['times'] > 0) {
                       
                        call_user_func_array($listener[0], $this_args);
                        $emitted = true;
                        $listener[1]['times']--;
                    }
                }
            }
        }

        return $emitted;
    }

    public function on($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->listeners[strtolower($e)][] = $listener;
            }
        } else {
            $this->listeners[strtolower($event)][] = $listener;
        }
        return $this;
    }

    public function once($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->listeners[strtolower($e)][] = array($listener, array('times' => 1));
            }
        } else {
            $this->listeners[strtolower($event)][] = array($listener, array('times' => 1));
        }
        return $this;
    }

    public function many($event, $times = 1, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->listeners[strtolower($e)][] = array($listener, array('times' => $times));
            }
        } else {
            $this->listeners[strtolower($event)][] = array($listener, array('times' => $times));
        }
        return $this;
    }

    public function off($event, \Closure $listener)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                $this->off($e, $listener);
            }
        } else {
            $event = strtolower($event);
            if (!empty($this->listeners[$event])) {
               
                if (($key = array_search($listener, $this->listeners[$event])) !== false) {
                   
                    unset($this->listeners[$event][$key]);
                }
            }
        }
        return $this;
    }

    public function listeners($event)
    {
        if (!empty($this->listeners[$event])) {
            return $this->listeners[$event];
        }
        return array();
    }

    public function addListener($event, \Closure $listener)
    {
        return $this->on($event, $listener);
    }

    public function removeListener($event, \Closure $listener)
    {
        return $this->off($event, $listener);
    }

    public function removeAllListeners($event = null)
    {
        if ($event === null) {
            $this->listeners = array();
        } else if (($event = strtolower($event)) && !empty($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }
        return $this;
    }

    protected static function match($pattern, $string)
    {
        if (FNMATCH) {
            return fnmatch($pattern, $string);
        } else {
            return preg_match("#^" . strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')) . "$#i", $string);
        }
    }
}

abstract class Middleware extends EventEmitter
{
    const _CLASS_ = __CLASS__;

    public $app;

    public $input;

    public $output;

    protected $next;

    public static function build($route, $options = null, array $prefixes = array())
    {
        if (is_object($route)) return $route;

        if (!is_string($route)) throw new \InvalidArgumentException('The parameter $route need string');

        $prefixes[] = '';
        $prefixes[] = __NAMESPACE__ . "\\Middleware";

        if (strpos($route, '@')) {
            $arr = explode('@', $route);
            $route = $arr[0];
            $options = (array)$options + array('entry' => $arr[1]);
        }

        foreach ($prefixes as $namespace) {
            if (!is_subclass_of($class = ($namespace ? $namespace . '\\' : '') . $route, __CLASS__, true)) continue;

            return new $class((array)$options);
        }

        throw new \InvalidArgumentException("Non-exists route class '$route'");
    }

    public function graft($route, array $option = array())
    {
        if (!$route = self::build($route, $option)) {
            throw new \RuntimeException("Graft \"$route\" fail");
        }

        return call_user_func_array($route, array(
            $this->input, $this->output, $this->next
        ));
    }

    abstract function call();

    public function __invoke($input, $output, $next)
    {
        $this->injectors['input'] = $input;
        $this->injectors['output'] = $output;
        $this->injectors['app'] = $input->app;
        $this->input = & $this->injectors['input'];
        $this->output = & $this->injectors['output'];
        $this->app = & $this->injectors['app'];
        $this->next = $next;
        $this->call();
    }

    public function next()
    {
        call_user_func($this->next);
    }
}



abstract class Route extends Middleware
{
    protected function before()
    {
       
    }

    protected function after()
    {
       
    }

    public function call()
    {
       
        $run = !empty($this->injectors['entry']) ? $this->injectors['entry'] : 'run';

        $this->before();

       
        if (!method_exists($this, $run) && method_exists($this, 'missing')) {
            call_user_func(array($this, 'missing'), $this->input, $this->output);
        } else {
            $this->$run($this->input, $this->output);
        }
        $this->after();
    }

    public function next()
    {
        call_user_func($this->next);
    }
}




class Router extends Middleware
{
    const _CLASS_ = __CLASS__;

    public $app;

    protected $routes;

    protected $last = -1;

    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors);
        $this->app = & $this->injectors['app'];
        $this->routes = & $this->app->routes;
    }

    public function map($path, $route, $method = '*')
    {
        if (!is_array($route)) {
           
            $this->routes[] = array(
                'path'  => $path,
                'route' => $route,
                'via'   => $method == '*' ? null : (array)$method
            );
            $this->last++;
        } else {
            foreach ($route as $r) {
               
                $this->routes[] = array(
                    'path'  => $path,
                    'route' => $r,
                    'via'   => $method == '*' ? null : (array)$method
                );
                $this->last++;
            }
        }

        return $this;
    }

    public function name($name, $path = null)
    {
        if ($path === null) {
            $path = $this->routes[$this->last]['path'];
        }

        $this->app->names[$name] = $path;
        return $this;
    }

    public function defaults($defaults = array())
    {
        $this->routes[$this->last]['defaults'] = $defaults;
        return $this;
    }

    public function rules($rules = array())
    {
        $this->routes[$this->last]['rules'] = $rules;
        return $this;
    }

    public function path($name)
    {
        return isset($this->app->names[$name]) ? $this->app->names[$name] : false;
    }

    public function dispatch()
    {
       
        if ($this->injectors['path'] === null) return false;

        $method = $this->input->method();
        $path = & $this->injectors['path'];
        $prefixes = & $this->injectors['prefixes'];
        $app = & $this->app;

        if ($this->handle($this->routes, function ($route) use ($path, $method, $prefixes, $app) {
           
            $rules = isset($route['rules']) ? $route['rules'] : array();

           
            $defaults = isset($route['defaults']) ? $route['defaults'] : array();

           
            if (($params = Router::match($path, $route['path'], $rules, $defaults)) !== false) {
               
                if ($route['via'] && !in_array($method, $route['via'])) return false;

                $app->input->params = $params;

                return Route::build($route['route'], null, $prefixes);
            }
            return false;
        })
        ) return true;


       
        if (isset($this->injectors['automatic']) && is_callable($this->injectors['automatic'])) {
            $routes = (array)call_user_func($this->injectors['automatic'], $this->injectors['path']);

            return $this->handle($routes, function ($route) use ($prefixes) {
                if (!is_subclass_of($route, Middleware::_CLASS_, true)) return false;

                return Route::build($route, null, $prefixes);
            });
        }

        return false;
    }

    public function handle(array $routes, \Closure $mapper)
    {
        array_unshift($routes, null);

        $arguments = array(
            $this->app->input,
            $this->app->output,
            function () use (&$routes, $mapper, &$arguments) {
                while ($route = next($routes)) {
                    if ($route = $mapper($route)) break;
                }

                if (!$route) throw new Pass;

                call_user_func_array($route, $arguments);
                return true;
            }
        );

        try {
            return $arguments[2]();
        } catch (Pass $e) {
            return false;
        }
    }

    public function run($route, array $params = array())
    {
        if ($route = Route::build($route)) {
            $this->app->input->params = $params;
            call_user_func_array($route, array(
                $this->app->input,
                $this->app->output,
                function () {
                    throw new Pass;
                }
            ));
            return true;
        }
        return false;
    }

    public function call()
    {
        $prefixes = array();
        $this->injectors['path'] = $this->app->input->path();

       
        if ($this->app->prefixes) {
            foreach ($this->app->prefixes as $path => $namespace) {
                if (strpos($this->injectors['path'], $path) === 0) {
                    $prefixes[99 - strlen($path)] = $namespace;
                }
            }
            ksort($prefixes);
        }
        $this->injectors['prefixes'] = $prefixes;

        if (!$this->dispatch()) {
            $this->next();
        }
    }

    public static function match($path, $route, $rules = array(), $defaults = array())
    {
        $param = false;

       
        if (!strpos($route, ':') && strpos($route, '^') === false) {
            if ($path === $route || $path === $route . '/') {
                $param = array();
            }
        } else {
           
            if (preg_match(self::pathToRegex($route, $rules), $path, $matches)) {
                array_shift($matches);
                $param = $matches;
            }
        }

       
        return $param === false ? false : ($defaults + $param);
    }

    public static function pathToRegex($path, $rules = array())
    {
        if ($path[1] !== '^') {
            $path = str_replace(array('/'), array('\\/'), $path);
            if ($path{0} == '^') {
               
                $path = '/' . $path . '/';
            } elseif (strpos($path, ':')) {
                $path = str_replace(array('(', ')'), array('(?:', ')?'), $path);

                if (!$rules) {
                   
                    $path = '/^' . preg_replace('/(?<!\\\\):([a-zA-Z0-9]+)/', '(?<$1>[^\/]+?)', $path) . '\/?$/';
                } else {
                    $path = '/^' . preg_replace_callback('/(?<!\\\\):([a-zA-Z0-9]+)/', function ($match) use ($rules) {
                            return '(?<' . $match[1] . '>' . (isset($rules[$match[1]]) ? $rules[$match[1]] : '[^\/]+') . '?)';
                        }, $path) . '\/?$/';
                }
            } else {
               
                $path = '/^' . $path . '\/?$/';
            }

           
            if (strpos($path, '*')) {
                $path = str_replace('*', '([^\/]+?)', $path);
            }
        }
        return $path;
    }
}


class Config extends Fiber
{
    const LOAD_AUTODETECT = 0;

    public static $dir;

    protected static $imports = array(
        'mimes' => array(
    '323'      => array('text/h323'),
    '7z'       => array('application/x-7z-compressed'),
    'abw'      => array('application/x-abiword'),
    'acx'      => array('application/internet-property-stream'),
    'ai'       => array('application/postscript'),
    'aif'      => array('audio/x-aiff'),
    'aifc'     => array('audio/x-aiff'),
    'aiff'     => array('audio/x-aiff'),
    'amf'      => array('application/x-amf'),
    'asf'      => array('video/x-ms-asf'),
    'asr'      => array('video/x-ms-asf'),
    'asx'      => array('video/x-ms-asf'),
    'atom'     => array('application/atom+xml'),
    'avi'      => array('video/avi', 'video/msvideo', 'video/x-msvideo'),
    'bin'      => array('application/octet-stream', 'application/macbinary'),
    'bmp'      => array('image/bmp'),
    'c'        => array('text/x-csrc'),
    'c++'      => array('text/x-c++src'),
    'cab'      => array('application/x-cab'),
    'cc'       => array('text/x-c++src'),
    'cda'      => array('application/x-cdf'),
    'class'    => array('application/octet-stream'),
    'cpp'      => array('text/x-c++src'),
    'cpt'      => array('application/mac-compactpro'),
    'csh'      => array('text/x-csh'),
    'css'      => array('text/css'),
    'csv'      => array('text/x-comma-separated-values', 'application/vnd.ms-excel', 'text/comma-separated-values', 'text/csv'),
    'dbk'      => array('application/docbook+xml'),
    'dcr'      => array('application/x-director'),
    'deb'      => array('application/x-debian-package'),
    'diff'     => array('text/x-diff'),
    'dir'      => array('application/x-director'),
    'divx'     => array('video/divx'),
    'dll'      => array('application/octet-stream', 'application/x-msdos-program'),
    'dmg'      => array('application/x-apple-diskimage'),
    'dms'      => array('application/octet-stream'),
    'doc'      => array('application/msword'),
    'docx'     => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'dvi'      => array('application/x-dvi'),
    'dxr'      => array('application/x-director'),
    'eml'      => array('message/rfc822'),
    'eps'      => array('application/postscript'),
    'evy'      => array('application/envoy'),
    'exe'      => array('application/x-msdos-program', 'application/octet-stream'),
    'fla'      => array('application/octet-stream'),
    'flac'     => array('application/x-flac'),
    'flc'      => array('video/flc'),
    'fli'      => array('video/fli'),
    'flv'      => array('video/x-flv'),
    'gif'      => array('image/gif'),
    'gtar'     => array('application/x-gtar'),
    'gz'       => array('application/x-gzip'),
    'h'        => array('text/x-chdr'),
    'h++'      => array('text/x-c++hdr'),
    'hh'       => array('text/x-c++hdr'),
    'hpp'      => array('text/x-c++hdr'),
    'hqx'      => array('application/mac-binhex40'),
    'hs'       => array('text/x-haskell'),
    'htm'      => array('text/html'),
    'html'     => array('text/html'),
    'ico'      => array('image/x-icon'),
    'ics'      => array('text/calendar'),
    'iii'      => array('application/x-iphone'),
    'ins'      => array('application/x-internet-signup'),
    'iso'      => array('application/x-iso9660-image'),
    'isp'      => array('application/x-internet-signup'),
    'jar'      => array('application/java-archive'),
    'java'     => array('application/x-java-applet'),
    'jpe'      => array('image/jpeg', 'image/pjpeg'),
    'jpeg'     => array('image/jpeg', 'image/pjpeg'),
    'jpg'      => array('image/jpeg', 'image/pjpeg'),
    'js'       => array('application/javascript'),
    'json'     => array('application/json'),
    'latex'    => array('application/x-latex'),
    'lha'      => array('application/octet-stream'),
    'log'      => array('text/plain', 'text/x-log'),
    'lzh'      => array('application/octet-stream'),
    'm4a'      => array('audio/mpeg'),
    'm4p'      => array('video/mp4v-es'),
    'm4v'      => array('video/mp4'),
    'man'      => array('application/x-troff-man'),
    'mdb'      => array('application/x-msaccess'),
    'midi'     => array('audio/midi'),
    'mid'      => array('audio/midi'),
    'mif'      => array('application/vnd.mif'),
    'mka'      => array('audio/x-matroska'),
    'mkv'      => array('video/x-matroska'),
    'mov'      => array('video/quicktime'),
    'movie'    => array('video/x-sgi-movie'),
    'mp2'      => array('audio/mpeg'),
    'mp3'      => array('audio/mpeg'),
    'mp4'      => array('application/mp4', 'audio/mp4', 'video/mp4'),
    'mpa'      => array('video/mpeg'),
    'mpe'      => array('video/mpeg'),
    'mpeg'     => array('video/mpeg'),
    'mpg'      => array('video/mpeg'),
    'mpg4'     => array('video/mp4'),
    'mpga'     => array('audio/mpeg'),
    'mpp'      => array('application/vnd.ms-project'),
    'mpv'      => array('video/x-matroska'),
    'mpv2'     => array('video/mpeg'),
    'ms'       => array('application/x-troff-ms'),
    'msg'      => array('application/msoutlook', 'application/x-msg'),
    'msi'      => array('application/x-msi'),
    'nws'      => array('message/rfc822'),
    'oda'      => array('application/oda'),
    'odb'      => array('application/vnd.oasis.opendocument.database'),
    'odc'      => array('application/vnd.oasis.opendocument.chart'),
    'odf'      => array('application/vnd.oasis.opendocument.forumla'),
    'odg'      => array('application/vnd.oasis.opendocument.graphics'),
    'odi'      => array('application/vnd.oasis.opendocument.image'),
    'odm'      => array('application/vnd.oasis.opendocument.text-master'),
    'odp'      => array('application/vnd.oasis.opendocument.presentation'),
    'ods'      => array('application/vnd.oasis.opendocument.spreadsheet'),
    'odt'      => array('application/vnd.oasis.opendocument.text'),
    'oga'      => array('audio/ogg'),
    'ogg'      => array('application/ogg'),
    'ogv'      => array('video/ogg'),
    'otg'      => array('application/vnd.oasis.opendocument.graphics-template'),
    'oth'      => array('application/vnd.oasis.opendocument.web'),
    'otp'      => array('application/vnd.oasis.opendocument.presentation-template'),
    'ots'      => array('application/vnd.oasis.opendocument.spreadsheet-template'),
    'ott'      => array('application/vnd.oasis.opendocument.template'),
    'p'        => array('text/x-pascal'),
    'pas'      => array('text/x-pascal'),
    'patch'    => array('text/x-diff'),
    'pbm'      => array('image/x-portable-bitmap'),
    'pdf'      => array('application/pdf', 'application/x-download'),
    'php'      => array('application/x-httpd-php'),
    'php3'     => array('application/x-httpd-php'),
    'php4'     => array('application/x-httpd-php'),
    'php5'     => array('application/x-httpd-php'),
    'phps'     => array('application/x-httpd-php-source'),
    'phtml'    => array('application/x-httpd-php'),
    'pl'       => array('text/x-perl'),
    'pm'       => array('text/x-perl'),
    'png'      => array('image/png', 'image/x-png'),
    'po'       => array('text/x-gettext-translation'),
    'pot'      => array('application/vnd.ms-powerpoint'),
    'pps'      => array('application/vnd.ms-powerpoint'),
    'ppt'      => array('application/powerpoint'),
    'pptx'     => array('application/vnd.openxmlformats-officedocument.presentationml.presentation'),
    'ps'       => array('application/postscript'),
    'psd'      => array('application/x-photoshop', 'image/x-photoshop'),
    'pub'      => array('application/x-mspublisher'),
    'py'       => array('text/x-python'),
    'qt'       => array('video/quicktime'),
    'ra'       => array('audio/x-realaudio'),
    'ram'      => array('audio/x-realaudio', 'audio/x-pn-realaudio'),
    'rar'      => array('application/rar'),
    'rgb'      => array('image/x-rgb'),
    'rm'       => array('audio/x-pn-realaudio'),
    'rpm'      => array('audio/x-pn-realaudio-plugin', 'application/x-redhat-package-manager'),
    'rss'      => array('application/rss+xml'),
    'rtf'      => array('text/rtf'),
    'rtx'      => array('text/richtext'),
    'rv'       => array('video/vnd.rn-realvideo'),
    'sea'      => array('application/octet-stream'),
    'sh'       => array('text/x-sh'),
    'shtml'    => array('text/html'),
    'sit'      => array('application/x-stuffit'),
    'smi'      => array('application/smil'),
    'smil'     => array('application/smil'),
    'so'       => array('application/octet-stream'),
    'src'      => array('application/x-wais-source'),
    'svg'      => array('image/svg+xml'),
    'swf'      => array('application/x-shockwave-flash'),
    't'        => array('application/x-troff'),
    'tar'      => array('application/x-tar'),
    'tcl'      => array('text/x-tcl'),
    'tex'      => array('application/x-tex'),
    'text'     => array('text/plain'),
    'texti'    => array('application/x-texinfo'),
    'textinfo' => array('application/x-texinfo'),
    'tgz'      => array('application/x-tar'),
    'tif'      => array('image/tiff'),
    'tiff'     => array('image/tiff'),
    'torrent'  => array('application/x-bittorrent'),
    'tr'       => array('application/x-troff'),
    'tsv'      => array('text/tab-separated-values'),
    'txt'      => array('text/plain'),
    'wav'      => array('audio/x-wav'),
    'wax'      => array('audio/x-ms-wax'),
    'wbxml'    => array('application/wbxml'),
    'webm'     => array('video/webm'),
    'wm'       => array('video/x-ms-wm'),
    'wma'      => array('audio/x-ms-wma'),
    'wmd'      => array('application/x-ms-wmd'),
    'wmlc'     => array('application/wmlc'),
    'wmv'      => array('video/x-ms-wmv', 'application/octet-stream'),
    'wmx'      => array('video/x-ms-wmx'),
    'wmz'      => array('application/x-ms-wmz'),
    'word'     => array('application/msword', 'application/octet-stream'),
    'wp5'      => array('application/wordperfect5.1'),
    'wpd'      => array('application/vnd.wordperfect'),
    'wvx'      => array('video/x-ms-wvx'),
    'xbm'      => array('image/x-xbitmap'),
    'xcf'      => array('image/xcf'),
    'xhtml'    => array('application/xhtml+xml'),
    'xht'      => array('application/xhtml+xml'),
    'xl'       => array('application/excel', 'application/vnd.ms-excel'),
    'xla'      => array('application/excel', 'application/vnd.ms-excel'),
    'xlc'      => array('application/excel', 'application/vnd.ms-excel'),
    'xlm'      => array('application/excel', 'application/vnd.ms-excel'),
    'xls'      => array('application/excel', 'application/vnd.ms-excel'),
    'xlsx'     => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    'xlt'      => array('application/excel', 'application/vnd.ms-excel'),
    'xml'      => array('text/xml', 'application/xml'),
    'xof'      => array('x-world/x-vrml'),
    'xpm'      => array('image/x-xpixmap'),
    'xsl'      => array('text/xml'),
    'xvid'     => array('video/x-xvid'),
    'xwd'      => array('image/x-xwindowdump'),
    'z'        => array('application/x-compress'),
    'zip'      => array('application/x-zip', 'application/zip', 'application/x-zip-compressed')
),
    );

    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    public static function import($name, $file, $type = self::LOAD_AUTODETECT)
    {
        static::$imports[$name] = array($file, $type);
    }

    public static function export($name)
    {
        if (!isset(static::$imports[$name])) {
            throw new \InvalidArgumentException("Load config error with non-exists name \"$name\"");
        }

       
        if (static::$imports[$name] instanceof Config) {
            return static::$imports[$name];
        }

       
        list($path, $type) = static::$imports[$name];

       
        if (!$file = App::self()->path($path)) {
            throw new \InvalidArgumentException("Can not find file path \"$path\"");
        }

        return static::$imports[$name] = static::load($file, $type);
    }

    public static function load($file, $type = self::LOAD_AUTODETECT)
    {
        return new self(self::from($file, $type));
    }

    public static function from($file, $type = self::LOAD_AUTODETECT)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("Config load error with non-exists file \"$file\"");
        }

        if ($type === self::LOAD_AUTODETECT) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        if ($type !== 'php') {
            return self::parse(file_get_contents($file), $type);
        } else {
            return include($file);
        }
    }

    public static function parse($string, $type, array $option = array())
    {
       
        if (!class_exists($class = __NAMESPACE__ . "\\Config\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }

        return $class::parse($string, $option);
    }

    public static function dump($array, $type, array $option = array())
    {
       
        if (!class_exists($class = __NAMESPACE__ . "\\Config\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }

        return $class::dump($array, $option);
    }

    public function string($type)
    {
        return self::dump($this->injectors, $type);
    }

    public function file($file, $type)
    {
        file_put_contents($file, $this->string($type));
    }
}


class View extends EventEmitter
{
    const _CLASS_ = __CLASS__;

    public static $rendering = false;

    protected $path;

    protected $compileDirectly = false;

    protected $injectors = array(
        'dir'    => '',
        'engine' => null,
        'data'   => array(),
        'app'    => null
    );

    public static function factory($type, $data)
    {
        $class = __NAMESPACE__ . '\\View\\' . ucfirst(strtolower($type));

        if (!class_exists($class) && !class_exists($class = $type)) {
            throw new \InvalidArgumentException('Can not find given "' . $type . '" view');
        }

        return new $class($data);
    }

    public static function make($path, $data = array(), $engine = null)
    {
        return new self($path, $data, array('engine' => $engine));
    }

    public function __construct($path, $data = array(), $injectors = array())
    {
        if (is_array($path)) {
           
            $this->injectors['data'] = $path;
            $this->compileDirectly = true;
        } else {
           
            $injectors = array('data' => (array)$data, 'path' => $path) + $injectors + array('dir' => (string)App::self()->get('views'), 'app' => App::self()) + $this->injectors;

           
            $injectors['path'] = ltrim($path, '/');

           
            if (!is_file($injectors['dir'] . '/' . $injectors['path'])) {
               
                if ($path{0} == '/' && is_file($path)) {
                    $injectors['path'] = $path;
                    $injectors['dir'] = '';
                } else if (!empty($injectors['app']) && ($_path = $injectors['app']->path($injectors['path']))) {
                    $injectors['path'] = $_path;
                    $injectors['dir'] = '';
                } else {
                    throw new \Exception('Template file is not exist: ' . $injectors['path']);
                }
            }
        }

       
        parent::__construct($injectors);
    }

    public function data(array $array = array())
    {
        return $this->injectors['data'] = $array + $this->injectors['data'];
    }

    public function compile()
    {
        throw new \RuntimeException("Implements compile method");
    }

    public function render()
    {
       
        self::$rendering = true;

        $this->emit('render');

        $er_code = error_reporting(E_ALL & ~E_NOTICE);

        if ($this->compileDirectly) {
            $__html = $this->compile();
        } else {
            $engine = $this->injectors['engine'];
            if (!$engine) {
                if ($this->injectors) {
                    extract((array)$this->injectors['data']);
                }
                ob_start();
                include($this->injectors['dir'] . ($this->injectors['path']{0} == '/' ? '' : '/') . $this->injectors['path']);
                $__html = ob_get_clean();
            } else if (is_callable($engine)) {
                $__html = $engine($this->injectors['path'], $this->injectors['data'], $this->injectors['dir']);
            } else {
                $__html = $engine->render($this->injectors['path'], $this->injectors['data'], $this->injectors['dir']);
            }
        }

        error_reporting($er_code);

        $this->emit('rendered');

       
        self::$rendering = false;

        return $__html;
    }

    public function __toString()
    {
        return $this->render();
    }
}


const VERSION = '0.8.0';

spl_autoload_register(function ($class) {
    if (substr($class, 0, strlen(__NAMESPACE__) + 1) == __NAMESPACE__ . '\\') {
       
        if ($file = stream_resolve_include_path(__DIR__ . '/' . str_replace('\\', '/', substr($class, strlen(__NAMESPACE__) + 1)) . '.php')) {
            require $file;
            return true;
        }
    } else if (class_exists(__NAMESPACE__ . '\\' . $class)) {
       
        class_alias(__NAMESPACE__ . '\\' . $class, $class);
        return true;
    }

    return false;
});

class App extends EventEmitter
{
    protected $injectors = array(
        'mode'       => 'develop',
        'debug'      => false,
        'views'      => false,
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
        'mounts'     => array('/' => ''),
        'bundles'    => array(),
        'locals'     => array(),
        'resource'   => array(
            'index'   => array('GET'),
            'create'  => array('GET', 'create'),
            'store'   => array('POST'),
            'show'    => array('GET', ':id'),
            'edit'    => array('GET', ':id/edit'),
            'update'  => array('PUT', ':id'),
            'destroy' => array('DELETE', ':id')
        ),
        'safe_query' => true,
        'input'      => null,
        'output'     => null,
        'router'     => null,
    );

    public $input;

    public $output;

    public $router;

    private $_run = false;

    private static $_cli = null;

    protected static $self;

    protected static $loads = array();

    public static function create($config = array())
    {
       
        if (is_null(self::$_cli)) self::$_cli = PHP_SAPI == 'cli';

        $app = new self($config);

       
        if (!self::$_cli) {
            $app->input = new Http\Input(array('app' => $app));
            $app->output = new Http\Output(array('app' => $app));
        } else {
            $app->input = new Cli\Input(array('app' => $app));
            $app->output = new Cli\Output(array('app' => $app));
        }

       
        $app->router = new Router(array('app' => $app));

        return $app;
    }

    public static function self()
    {
        if (!self::$self) {
            throw new \RuntimeException("There is no running App exists");
        }

        return self::$self;
    }

    public function __construct($config = array())
    {
       
        register_shutdown_function(array($this, '__shutdown'));

       
        spl_autoload_register(array($this, '__autoload'));

       
        $this->injectors =
            (!is_array($config) ? Config::load((string)$config) : $config)
            + (!empty($this->injectors['cli']) ? array('buffer' => false) : array())
            + $this->injectors;

       
        if (!isset($this->injectors['cli'])) $this->injectors['cli'] = self::$_cli;

       
        $this->injectors['locals']['config'] = & $this->injectors;

       
        $this->injectors['mode'] = ($_mode = getenv('PAGON_ENV')) ? $_mode : $this->injectors['mode'];

       
        $this->injectors['mounts']['pagon'] = dirname(dirname(__DIR__));

        $this->input = & $this->injectors['input'];
        $this->output = & $this->injectors['output'];
        $this->router = & $this->injectors['router'];
    }

    public function id($id = null)
    {
        return $this->input->id($id);
    }

    public function cli()
    {
        return $this->injectors['cli'];
    }

    public function running()
    {
        return $this->_run;
    }

    public function set($key, $value = null)
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
        return $this;
    }

    public function enable($key)
    {
        return $this->set($key, true);
    }

    public function disable($key)
    {
        return $this->set($key, false);
    }

    public function enabled($key)
    {
        return $this->get($key);
    }

    public function disabled($key)
    {
        return !$this->get($key);
    }

    public function mode($mode = null)
    {
        if ($mode) {
            $this->injectors['mode'] = $mode instanceof \Closure ? $mode() : (string)$mode;
        }
        return $this->injectors['mode'];
    }

    public function add($path, $middleware = null, $options = array())
    {
        if ($path instanceof Middleware
            || is_string($path) && $path{0} != '/'
            || $path instanceof \Closure
        ) {
           
            $options = (array)$middleware;
            $middleware = $path;
            $path = '';
        }

       
        $this->injectors['stacks'][] = array($path, $middleware, $options);
    }

    public function bundle($name, $options = array())
    {
        if (!is_array($options)) {
            $path = $name;
            $name = $options;
            $options = array('path' => $path);
        }
        $this->injectors['bundles'][$name] = $options;
    }

    public function get($key = null, $default = null)
    {
       
        if ($default === null) {
            if ($key === null) return $this->injectors;

            $tmp = null;
            if (strpos($key, '.') !== false) {
                $ks = explode('.', $key);
                $tmp = $this->injectors;
                foreach ($ks as $k) {
                    if (!isset($tmp[$k])) return null;

                    $tmp = & $tmp[$k];
                }
            } else {
                if (isset($this->injectors[$key])) {
                    $tmp = & $this->injectors[$key];
                }
            }

            return $tmp;
        }

        if (func_num_args() > 2) {
            return $this->router->map($key, array_slice(func_get_args(), 1, 'GET'));
        } else {
            return $this->router->map($key, $default, 'GET');
        }
    }

    public function post($path, $route, $more = null)
    {
        if ($more !== null) {
            return $this->router->map($path, array_slice(func_get_args(), 1), 'POST');
        } else {
            return $this->router->map($path, $route, 'POST');
        }
    }

    public function put($path, $route, $more = null)
    {
        if ($more !== null) {
            return $this->router->map($path, array_slice(func_get_args(), 1), 'PUT');
        } else {
            return $this->router->map($path, $route, 'PUT');
        }
    }

    public function delete($path, $route, $more = null)
    {
        if ($more !== null) {
            return $this->router->map($path, array_slice(func_get_args(), 1), 'DELETE');
        } else {
            return $this->router->map($path, $route, 'DELETE');
        }
    }

    public function command($path, $route = null, $more = null)
    {
        if ($more !== null) {
            return $this->router->map($path . '( :args)', array_slice(func_get_args(), 1), "CLI");
        } else {
            return $this->router->map($path . '( :args)', $route, "CLI");
        }
    }

    public function all($path, $route = null, $more = null)
    {
        if ($more !== null) {
            return $this->router->map($path, array_slice(func_get_args(), 1));
        } else {
            return $this->router->map($path, $route);
        }
    }

    public function resource($path, $namespace, $excepts = array())
    {
        foreach ($this->injectors['resource'] as $type => $opt) {
            if (in_array($type, $excepts)) continue;

            $this->router->map(
                $path . (!empty($opt[1]) ? '/' . $opt[1] : ''),
                $namespace . '\\' . (!empty($opt[2]) ? $opt[2] : ucfirst($type)),
                $opt[0]
            );
        }
    }

    public function autoRoute($closure, $index = 'Index')
    {
        if ($closure instanceof \Closure) {
            return $this->router->automatic = $closure;
        } elseif ($closure === true || is_string($closure)) {
            $_cli = $this->injectors['cli'];
           
            return $this->router->automatic = function ($path) use ($closure, $_cli, $index) {
                $parts = array();

               
                if (!$_cli && $path !== '/') {
                    $_paths = explode('/', ltrim($path, '/'));
                } else if ($_cli && $path !== '') {
                    $_ = explode(' ', $path);
                    $_paths = explode(':', array_shift($_));
                }

               
                if (isset($_paths)) {
                    foreach ($_paths as $_path) {
                        $parts[] = ucfirst(strtolower($_path));
                    }
                }

               
                if ($closure !== true) array_unshift($parts, $closure);

               
                if (!$parts) return $index;

               
                $class = join('\\', $parts);

               
                return array($class, $class . '\\' . $index);
            };
        }
        return false;
    }

    public function mount($path, $dir = null)
    {
        if (!$dir) {
            $dir = $path;
            $path = '';
        }

        $this->injectors['mounts'][$path] = $dir;
        return $this;
    }

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

    public function path($file)
    {
        foreach ($this->injectors['mounts'] as $path => $dir) {
            if ($path === '' || strpos($file, $path) === 0) {
                $_path = rtrim($dir, '/') . '/' . ltrim(($path ? substr($file, strlen($path)) : $file), '/');
                if (!is_file($_path)) continue;
                return $_path;
            }
        }

        return false;
    }

    public function engine($name, $engine = null)
    {
        if ($engine) {
           
            $this->injectors['engines'][$name] = $engine;
        }
        return isset($this->injectors['engines'][$name]) ? $this->injectors['engines'][$name] : null;
    }

    public function render($path, array $data = null, array $options = array())
    {
        $this->output->body($this->compile($path, $data, $options)->render());
    }

    public function renderView($view, array $data = null)
    {
        $this->output->body(View::factory($view, $data));
    }

    public function compile($path, array $data = null, array $options = array())
    {
       
        if (!isset($options['engine'])) {
           
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $options['engine'] = false;

           
            if ($ext && isset($this->injectors['engines'][$ext])) {
               
                if (is_string($this->injectors['engines'][$ext])) {
                    if (!class_exists($class = $this->injectors['engines'][$ext])
                        && !class_exists($class = __NAMESPACE__ . '\\Engine\\' . $this->injectors['engines'][$ext])
                    ) {
                        throw new \RuntimeException("Unavailable view engine '{$this->injectors['engines'][$ext]}'");
                    }
                   
                    $this->injectors['engines'][$ext] = $options['engine'] = new $class();
                } else {
                   
                    $options['engine'] = $this->injectors['engines'][$ext];
                }
            }
        }

       
        $data = (array)$data;

       
        $data['_'] = $this;

       
        $view = new View($path,
            $data + $this->injectors['locals'],
            $options + array('dir' => $this->injectors['views'], 'app' => $this)
        );

       
        return $view;
    }

    public function run()
    {
       
        if ($this->_run) {
            throw new \RuntimeException("Application already running");
        }

       
        self::$self = $this;

       
        $this->emit('run');

       
        $this->_run = true;

        $_path = $this->input->path();
        $this->registerErrorHandler();

        try {
           
            $this->emit('bundle');
            foreach ($this->injectors['bundles'] as $id => $options) {
               
                $id = isset($options['id']) ? $options['id'] : $id;

               
                $bootstrap = isset($options['bootstrap']) ? $options['bootstrap'] : 'bootstrap.php';

               
                $dir = isset($options['dir']) ? $options['dir'] : 'bundles/' . $id;

               
                if (isset($options['path'])
                    && strpos($_path, $options['path']) !== 0
                ) {
                    continue;
                }

               
                if (!$file = $this->path($dir . '/' . $bootstrap)) {
                    throw new \InvalidArgumentException('Bundle "' . $id . '" can not bootstrap');
                }

               
                if (isset(self::$loads[$file])) {
                    throw new \RuntimeException('Bundle "' . $id . '" can not bootstrap twice');
                }

               
                $this->emit('bundle.' . $id);

               
                $app = $this;
                extract($options);
                require $file;

               
                self::$loads[$file] = true;
            }

           
            if ($this->injectors['buffer']) ob_start();
            if (!in_array(array('', $this->router, array()), $this->injectors['stacks'])) $this->injectors['stacks'][] = $this->router;

           
            $this->emit('middleware');

           
            if (!$this->router->handle($this->injectors['stacks'], function ($stack) use ($_path) {
               
                if (is_array($stack) && $stack[0] && strpos($_path, $stack[0]) === false) {
                    return false;
                }

                if (!is_array($stack)) {
                    return $stack;
                } else {
                    return Middleware::build($stack[1], $stack[2]);
                }
            })
            ) {
                $this->handleError('404');
            }

           
            if ($this->injectors['buffer']) $this->output->write(ob_get_clean());
        } catch (Exception\Stop $e) {
        } catch (\Exception $e) {
            try {
                $this->handleError('exception', $e);
            } catch (Exception\Stop $e) {
            }
            $this->emit('error');
        }

        $this->_run = false;

       
        $this->emit('flush');

       
        $this->flush();

       
        $this->emit('end');

        $this->restoreErrorHandler();
    }

    public function handleError($type, $route = null)
    {
        if (!isset($this->injectors['errors'][$type])) {
            throw new \InvalidArgumentException('Unknown error type "' . $type . '" to call');
        }

        if (is_string($route) && is_subclass_of($route, Route::_CLASS_, true)
            || $route instanceof \Closure
        ) {
            $this->injectors['errors'][$type][2] = $route;
        } else {
            ob_get_level() && ob_clean();
            ob_start();
            if (empty($this->injectors['errors'][$type][2])
                || !$this->router->run($this->injectors['errors'][$type][2], array('error' => array(
                    'type'    => $type,
                    'error'   => $route,
                    'message' => $this->injectors['errors'][$type])
                ))
            ) {
                if ($this->injectors['cli']) {
                    $this->halt($this->injectors['errors'][$type][0], $this->injectors['errors'][$type][1]);
                } else {
                    $this->output->status($this->injectors['errors'][$type][0]);
                    if ($this->injectors['debug']) {
                        $this->renderView('Error', array(
                            'title'   => $route instanceof \Exception ? $route->getMessage() : $this->injectors['errors'][$type][1],
                            'message' => $route ? ($route instanceof \Exception ? $route->getFile() . '[' . $route->getLine() . ']' : (string)$route) : 'Could not ' . $this->input->method() . ' ' . $this->input->path(),
                            'stacks'  => $this->injectors['debug'] && $route instanceof \Exception ? $route->getTraceAsString() : null
                        ));
                    } else {
                        $this->renderView('Error', array(
                            'title'   => $this->injectors['errors'][$type][1],
                            'message' => $route ? ($route instanceof \Exception ? $route->getMessage() : (string)$route) : 'Could not ' . $this->input->method() . ' ' . $this->input->path()
                        ));
                    }

                    $this->stop();
                }
            }
        }
    }

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

    public function assisting()
    {
        $this->load(dirname(__DIR__) . '/assistant.php');
    }

    public function halt($status, $body = '')
    {
        $this->output->status($status)->body($body);
        throw new Exception\Stop;
    }

    public function flush()
    {
       
        if (!$this->injectors['cli']) {
            $this->output->sendHeader();
        }

       
        echo $this->output->body();

       
        $this->output->clear();
    }

    public function stop()
    {
        throw new Exception\Stop();
    }

    public function pass()
    {
        ob_get_level() && ob_clean();
        throw new Exception\Pass();
    }

    public function registerErrorHandler()
    {
        set_error_handler(array($this, '__error'));
    }

    public function restoreErrorHandler()
    {
        restore_error_handler();
    }

    protected function __autoload($class)
    {
        if ($class{0} == '\\') $class = ltrim($class, '\\');

       
        if (!empty($this->injectors['alias'][$class])) {
            class_alias($this->injectors['alias'][$class], $class);
            $class = $this->injectors['alias'][$class];
        }

       
        $available_path = array();

       
        if ($this->injectors['autoload']) {
            $available_path[99] = $this->injectors['autoload'];
        }

       
        if ($this->injectors['namespaces']) {
           
            foreach ($this->injectors['namespaces'] as $_prefix => $_path) {
               
                if (strpos($class, $_prefix) === 0) {
                   
                    $available_path[(99 - strlen($_prefix)) . $_prefix] = $_path;
                }
            }
           
            ksort($available_path);
        }

       
        if ($available_path) {
           
            $file_name = '';
           
            if ($last_pos = strrpos($class, '\\')) {
                $namespace = substr($class, 0, $last_pos);
                $class = substr($class, $last_pos + 1);
                $file_name = str_replace('\\', '/', $namespace) . '/';
            }
           
            $file_name .= str_replace('_', '/', $class) . '.php';
           
            foreach ($available_path as $_path) {
               
                if ($file = stream_resolve_include_path($_path . '/' . $file_name)) {
                    require $file;
                    return true;
                }
            }
        }

        return false;
    }

    public function __error($type, $message, $file, $line)
    {
        if (error_reporting() & $type) throw new \ErrorException($message, $type, 0, $file, $line);
    }

    public function __shutdown()
    {
        $this->emit('exit');
        if (!$this->_run) return;

        if (($error = error_get_last())
            && in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR, E_CORE_ERROR))
        ) {
            try {
                $this->handleError('crash', new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            } catch (Exception\Stop $e) {
            }
            $this->emit('crash', $error);
            $this->flush();
        }
    }
}

}

namespace Pagon\View {

use Pagon\View;



class Error extends View
{
    public function compile()
    {
        if ($this->injectors) {
            extract((array)$this->injectors['data']);
        }

        $__html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8">
<style type="text/css">
@charset "utf-8";

html {
    color: #444333;
    background: #fff;
    -webkit-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
    text-rendering: optimizelegibility;
}

body, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6 {
    margin: 0;
    padding: 0;
}

body {
    font: 500 0.875em/1.8 Microsoft Yahei, Hiragino Sans GB, WenQuanYi Micro Hei, sans-serif;
    *width: auto;
    *overflow: visible;
    line-height: 22px;
}

a:hover {
    text-decoration: underline;
}

ins, a {
    text-decoration: none;
}

pre, code {
    font-family: "Courier New", Courier, monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
}

pre {
    background: #f8f8f8;
    border: 1px solid #ddd;
    padding: 1em 1.5em;
}

hr {
    border: none;
    border-bottom: 1px solid #cfcfcf;
    margin-bottom: 10px;
    *color: pink;
    *filter: chroma(color=pink);
    height: 10px;
    *margin: -7px 0 2px;
}

.clearfix:before, .clearfix:after {
    content: "";
    display: table;
}

.clearfix:after {
    clear: both
}

.clearfix {
    zoom: 1
}

.typo p, .typo pre, .typo ul, .typo ol, .typo dl, .typo form, .typo hr, .typo table,
.typo-p, .typo-pre, .typo-ul, .typo-ol, .typo-dl, .typo-form, .typo-hr, .typo-table {
    margin-bottom: 1.2em;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 500;
    *font-weight: 800;
    font-family: Helvetica Neue, Microsoft Yahei, Hiragino Sans GB, WenQuanYi Micro Hei, sans-serif;
    color: #333;
}

.typo h1, .typo h2, .typo h3, .typo h4, .typo h5, .typo h6,
.typo-h1, .typo-h2, .typo-h3, .typo-h4, .typo-h5, .typo-h6 {
    margin-bottom: 0.4em;
    line-height: 1.5;
}

.typo h1, .typo-h1 {
    font-size: 1.8em;
}

.typo h2, .typo-h2 {
    font-size: 1.6em;
}

.typo h3, .typo-h3 {
    font-size: 1.4em;
}

.typo h4, .typo-h4 {
    font-size: 1.2em;
}

.typo h5, .typo h6, .typo-h5, .typo-h6 {
    font-size: 1em;
}

::-moz-selection {
    background: #08c;
    color: #fff;
}

::selection {
    background: #08c;
    color: #fff;
}

body h1 {
    font: 38px/1.8em Hiragino Mincho ProN, STSong, serif!important;
}

#wrapper {
    min-width: 480px;
    padding: 5% 8%;
}

#tagline {
    color: #888;
    font-size: 1em;
    margin: -2em 0 2em;
    padding-bottom: 2em;
}
</style>
</head>
<body class="typo">
<div id="wrapper" class="typo typo-selection">
    <h1>$title</h1>
    <h2 id="tagline">$message</h2>
HTML;

        if (!empty($stacks)) {
            $__html .= <<<HTML
<pre>
$stacks
</pre>
HTML;
        }
        $__html .= <<<HTML
</div>
</body>
</html>
HTML;

        return $__html;
    }
}

}

namespace Pagon\Http {

use Pagon\Config;
use Pagon\Config\Xml;
use Pagon\EventEmitter;
use Pagon\Exception\Stop;
use Pagon\View;
use Pagon\Exception\Pass;
use Pagon\Html;



class Input extends EventEmitter
{
    public $app;

    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array(
            'params' => array(),
            'query'  => &$_GET,
            'data'   => &$_POST,
            'files'  => &$_FILES,
            'server' => &$_SERVER,
            'app'    => null
        ));
        $this->app = & $this->injectors['app'];
    }

    public function id($id = null)
    {
        if (!isset($this->injectors['id'])) {
            $this->injectors['id'] = $id ? ($id instanceof \Closure ? $id() : (string)$id) : sha1(uniqid());
        }
        return $this->injectors['id'];
    }

    public function protocol()
    {
        return $this->injectors['server']['SERVER_PROTOCOL'];
    }

    public function uri()
    {
        return $this->injectors['server']['REQUEST_URI'];
    }

    public function root()
    {
        return rtrim($this->injectors['server']['DOCUMENT_ROOT'], '/') . rtrim($this->scriptName(), '/');
    }

    public function scriptName($basename = null)
    {
        $_script_name = $this->injectors['server']['SCRIPT_NAME'];
        if ($basename === null) {
            if (strpos($this->injectors['server']['REQUEST_URI'], $_script_name) !== 0) {
                $_script_name = dirname($_script_name);
            }
        } else if (!$basename) {
            $_script_name = dirname($_script_name);
        }
        return rtrim(str_replace('\\', '/', $_script_name), '/');
    }

    public function path()
    {
        if (!isset($this->injectors['path_info'])) {
            $_path_info = substr_replace($this->injectors['server']['REQUEST_URI'], '', 0, strlen($this->scriptName()));
            if (strpos($_path_info, '?') !== false) {
               
                $_path_info = substr_replace($_path_info, '', strpos($_path_info, '?'));
            }
            $this->injectors['path_info'] = (!$_path_info || $_path_info{0} != '/' ? '/' : '') . $_path_info;
        }
        return $this->injectors['path_info'];
    }

    public function url($full = true)
    {
        if (!$full) return $this->injectors['server']['REQUEST_URI'];

        return $this->site() . $this->injectors['server']['REQUEST_URI'];
    }

    public function site($basename = null)
    {
        return $this->scheme() . '://' . $this->domain() . $this->scriptName($basename);
    }

    public function method()
    {
        return $this->injectors['server']['REQUEST_METHOD'];
    }

    public function ip($proxy = true)
    {
        if ($proxy && ($ips = $this->proxy())) {
            return $ips[0];
        }
        return $this->injectors['server']['REMOTE_ADDR'];
    }

    public function proxy()
    {
        if (empty($this->injectors['server']['HTTP_X_FORWARDED_FOR'])) return array();

        $ips = $this->injectors['server']['HTTP_X_FORWARDED_FOR'];
        return strpos($ips, ', ') ? explode(', ', $ips) : array($ips);
    }

    public function subDomains()
    {
        $parts = explode('.', $this->host());
        return array_reverse(array_slice($parts, 0, -2));
    }

    public function refer()
    {
        return isset($this->injectors['server']['HTTP_REFERER']) ? $this->injectors['server']['HTTP_REFERER'] : null;
    }

    public function host($port = false)
    {
        if (isset($this->injectors['server']['HTTP_HOST']) && ($host = $this->injectors['server']['HTTP_HOST'])) {
            if ($port) return $host;

            if (strpos($host, ':') !== false) {
                $hostParts = explode(':', $host);

                return $hostParts[0];
            }

            return $host;
        }
        return $this->injectors['server']['SERVER_NAME'];
    }

    public function domain()
    {
        return $this->host();
    }

    public function scheme()
    {
        return !isset($this->injectors['server']['HTTPS']) || $this->injectors['server']['HTTPS'] == 'off' ? 'http' : 'https';
    }

    public function port()
    {
        return empty($this->injectors['server']['SERVER_PORT']) ? null : (int)$this->injectors['server']['SERVER_PORT'];
    }

    public function userAgent()
    {
        return $this->ua();
    }

    public function ua()
    {
        return !empty($this->injectors['server']['HTTP_USER_AGENT']) ? $this->injectors['server']['HTTP_USER_AGENT'] : null;
    }

    public function type()
    {
        if (empty($this->injectors['server']['CONTENT_TYPE'])) return null;
        $type = $this->injectors['server']['CONTENT_TYPE'];

        $parts = preg_split('/\s*[;,]\s*/', $type);

        return strtolower($parts[0]);
    }

    public function charset()
    {
        if (empty($this->injectors['server']['CONTENT_TYPE'])) return null;
        $type = $this->injectors['server']['CONTENT_TYPE'];

        if (!preg_match('/charset=([a-z0-9\-]+)/', $type, $match)) return null;

        return strtolower($match[1]);
    }

    public function length()
    {
        if (empty($this->injectors['server']['CONTENT_LENGTH'])) return 0;

        return (int)$this->injectors['server']['CONTENT_LENGTH'];
    }

    public function file($key)
    {
        return isset($this->injectors['files'][$key]) ? $this->injectors['files'][$key] : null;
    }

    public function query($key, $default = null)
    {
        if (isset($this->injectors['query'][$key])) {
            return $this->app->enabled('safe_query') && View::$rendering ? Html::encode($this->injectors['query'][$key]) : $this->injectors['query'][$key];
        }
        return $default;
    }

    public function data($key, $default = null)
    {
        if (isset($this->injectors['data'][$key])) {
            return $this->app->enabled('safe_query') && View::$rendering ? Html::encode($this->injectors['data'][$key]) : $this->injectors['data'][$key];
        }
        return $default;
    }

    public function server($key, $default = null)
    {
        return isset($this->injectors['server'][$key]) ? $this->injectors['server'][$key] : $default;
    }

    public function param($key, $default = null)
    {
        return isset($this->injectors['params'][$key]) ? $this->injectors['params'][$key] : $default;
    }

    public function header($name = null)
    {
        if (!isset($this->injectors['headers'])) {
            $_header = array();
            foreach ($this->injectors['server'] as $key => $value) {
                $_name = false;
                if ('HTTP_' === substr($key, 0, 5)) {
                    $_name = substr($key, 5);
                } elseif ('X_' == substr($key, 0, 2)) {
                    $_name = substr($key, 2);
                } elseif (in_array($key, array('CONTENT_LENGTH',
                    'CONTENT_MD5',
                    'CONTENT_TYPE'))
                ) {
                    $_name = $key;
                }
                if (!$_name) continue;

               
                $_header[strtolower(str_replace('_', '-', $_name))] = trim($value);
            }

            if (isset($this->injectors['server']['PHP_AUTH_USER'])) {
                $pass = isset($this->injectors['server']['PHP_AUTH_PW']) ? $this->injectors['server']['PHP_AUTH_PW'] : '';
                $_header['authorization'] = 'Basic ' . base64_encode($this->injectors['server']['PHP_AUTH_USER'] . ':' . $pass);
            }
            $this->injectors['headers'] = $_header;
            unset($_header);
        }

        if ($name === null) return $this->injectors['headers'];

        $name = strtolower(str_replace('_', '-', $name));
        return isset($this->injectors['headers'][$name]) ? $this->injectors['headers'][$name] : null;
    }

    public function cookie($key = null, $default = null)
    {
        if (!isset($this->injectors['cookies'])) {
            $this->injectors['cookies'] = $_COOKIE;
            $_option = $this->app->cookie;
            foreach ($this->injectors['cookies'] as &$value) {
                if (!$value) continue;

               
                if (strpos($value, 'c:') === 0) {
                    $value = $this->app->cryptor->decrypt(substr($value, 2));
                }

               
                if ($value && strpos($value, 's:') === 0 && $_option['secret']) {
                    $_pos = strrpos($value, '.');
                    $_data = substr($value, 2, $_pos - 2);
                    if (substr($value, $_pos + 1) === hash_hmac('sha1', $_data, $_option['secret'])) {
                        $value = $_data;
                    } else {
                        $value = false;
                    }
                }

               
                if ($value && strpos($value, 'j:') === 0) {
                    $value = json_decode(substr($value, 2), true);
                }
            }
        }
        if ($key === null) return $this->injectors['cookies'];
        return isset($this->injectors['cookies'][$key]) ? $this->injectors['cookies'][$key] : $default;
    }

    public function session($key = null, $value = null)
    {
        if ($value !== null) {
            return $_SESSION[$key] = $value;
        } elseif ($key !== null) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }

        return $_SESSION;
    }

    public function body()
    {
        if (!isset($this->injectors['body'])) {
            $this->injectors['body'] = @(string)file_get_contents('php://input');
        }
        return $this->injectors['body'];
    }

    public function accept($type = null)
    {
        if (!isset($this->injectors['accept'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT'])) {
                $this->injectors['accept'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT']);
            } else {
                $this->injectors['accept'] = array();
            }
        }

       
        if (!$type) return $this->injectors['accept'];
       
        if ($type === true) {
            reset($this->injectors['accept']);
            return key($this->injectors['accept']);
        }

       
        if (is_string($type) && !strpos($type, '/')) {
            $type = Config::export('mimes')->{$type};
            if (!$type) return null;
        }

       
        $type = (array)$type;

       
        foreach ($this->injectors['accept'] as $mime => $q) {
            if ($q && in_array($mime, $type)) return $mime;
        }

       
        if (isset($this->injectors['accept']['*/*'])) return $type[0];
        return null;
    }

    public function encoding($type = null)
    {
        if (!isset($this->injectors['accept_encoding'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT_ENCODING'])) {
                $this->injectors['accept_encoding'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT_ENCODING']);
            } else {
                $this->injectors['accept_encoding'] = array();
            }
        }

       
        if (!$type) return $this->injectors['accept_encoding'];
       
        if ($type === true) {
            reset($this->injectors['accept_encoding']);
            return key($this->injectors['accept_encoding']);
        }

       
        $type = (array)$type;

       
        foreach ($this->injectors['accept_encoding'] as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    public function language($type = null)
    {
        if (!isset($this->injectors['accept_language'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT_LANGUAGE'])) {
                $this->injectors['accept_language'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $this->injectors['accept_language'] = array();
            }
        }

       
        if (!$type) return $this->injectors['accept_language'];
       
        if ($type === true) {
            reset($this->injectors['accept_language']);
            return key($this->injectors['accept_language']);
        }

       
        $type = (array)$type;

       
        foreach ($this->injectors['accept_language'] as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    public function is($method)
    {
        return $this->method() == strtoupper($method);
    }

    public function isAjax()
    {
        return !$this->header('x-requested-with') && 'XMLHttpRequest' == $this->header('x-requested-with');
    }

    public function isXhr()
    {
        return $this->isAjax();
    }

    public function isSecure()
    {
        return $this->scheme() === 'https';
    }

    public function isUpload()
    {
        return !empty($this->injectors['files']);
    }

    public function pass()
    {
        $this->app->pass();
    }

    public function stop()
    {
        $this->app->stop();
    }

    protected static function parseAcceptsMap($string)
    {
        $_accept = array();

       
        $accept = strtolower(str_replace(' ', '', $string));
       
        $accept = explode(',', $accept);
        foreach ($accept as $a) {
           
            $q = 1;
           
            if (strpos($a, ';q=')) {
               
                list($a, $q) = explode(';q=', $a);
            }
           
           
            $_accept[$a] = $q;
        }
        arsort($_accept);
        return $_accept;
    }
}



class Output extends EventEmitter
{
    public static $messages = array(
       
        100 => 'Continue',
        101 => 'Switching Protocols',

       
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

       
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
       
        307 => 'Temporary Redirect',

       
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

       
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    public $app;

    public $locals;

    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array(
            'status'       => 200,
            'body'         => '',
            'content_type' => 'text/html',
            'length'       => false,
            'charset'      => $injectors['app']->charset,
            'headers'      => array(),
            'cookies'      => array(),
            'app'          => null,
        ));

        $this->app = & $this->injectors['app'];
        $this->locals = & $this->app->locals;
    }

    public function body($content = null)
    {
        if ($content !== null) {
            $this->injectors['body'] = $content;
            $this->injectors['length'] = strlen($this->injectors['body']);
            return $this;
        }

        return $this->injectors['body'];
    }

    public function write($data)
    {
        $this->injectors['body'] .= $data;
        $this->injectors['length'] += strlen($data);

        return $this;
    }

    public function end($data = '')
    {
        $this->write($data);
        throw new Stop();
    }

    public function status($status = null)
    {
        if ($status === null) {
            return $this->injectors['status'];
        } elseif (isset(self::$messages[$status])) {
            $this->injectors['status'] = (int)$status;
            return $this;
        } else {
            throw new \Exception('Unknown status :value', array(':value' => $status));
        }
    }

    public function header($name = null, $value = null, $replace = true)
    {
        if ($name === null) {
            return $this->injectors['headers'];
        } elseif (is_array($name)) {
           
            foreach ($name as $k => $v) {
               
                $this->header($k, $v, $replace);
            }
        } else {
            if ($value === null) {
                return $this->injectors['headers'][$name][1];
            } else {
                if (!$replace && !empty($this->injectors['headers'][$name])) {
                    if (is_array($this->injectors['headers'][$name])) {
                        $this->injectors['headers'][$name][] = $value;
                    } else {
                        $this->injectors['headers'][$name] = array($this->injectors['headers'][$name], $value);
                    }
                } else {
                    $this->injectors['headers'][$name] = $value;
                }
            }
        }
        return $this;
    }

    public function charset($charset = null)
    {
        if ($charset) {
            $this->injectors['charset'] = $charset;
            return $this;
        }
        return $this->injectors['charset'];
    }

    public function lastModified($time = null)
    {
        if ($time !== null) {
            if (is_integer($time)) {
                $this->header('Last-Modified', date(DATE_RFC1123, $time));
                if ($time === strtotime($this->app->input->header('If-Modified-Since'))) {
                    $this->app->halt(304);
                }
            }

            return $this;
        }

        return $this->header('Last-Modified');
    }

    public function etag($value = null)
    {
        if ($value !== null) {
            //Set etag value
            $value = 'W/"' . $value . '"';
            $this->header('Etag', $value);

            //Check conditional GET
            if ($etag = $this->app->input->header('If-None-Match')) {
                $etags = preg_split('@\s*,\s*@', $etag);
                if (in_array($value, $etags) || in_array('*', $etags)) {
                    $this->app->halt(304);
                }
            }

            return $this;
        }

        return $this->header('Etag');
    }

    public function expires($time = null)
    {
        if ($time !== null) {
            if (!is_numeric($time) && is_string($time)) {
                $time = strtotime($time);
            } elseif (is_numeric($time) && strlen($time) != 10) {
                $time = time() + (int)$time;
            }

            $this->header('Expires', gmdate(DATE_RFC1123, $time));
            return $this;
        }

        return $this->header('Expires');
    }

    public function type($mime_type = null)
    {
        if ($mime_type) {
            if (!strpos($mime_type, '/')) {
                if (!$type = Config::export('mimes')->{$mime_type}[0]) {
                    throw new \InvalidArgumentException("Unknown mime type '{$mime_type}'");
                }
                $mime_type = $type;
            }
            $this->injectors['content_type'] = $mime_type;

            return $this;
        }

        return $this->injectors['content_type'];
    }

    public function cookie($key = null, $value = null, $option = array())
    {
        if ($value !== null) {
            if ($value !== false) {
                $this->injectors['cookies'][$key] = array($value, $option);
            } else {
                unset($this->injectors['cookies'][$key]);
            }
            return $this;
        }

        if ($key === null) return $this->injectors['cookies'];
        return isset($this->injectors['cookies'][$key]) ? $this->injectors['cookies'][$key] : null;
    }

    public function message()
    {
        $status = $this->injectors['status'];
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        }
        return null;
    }

    public function sendHeader()
    {
       
        if (headers_sent() === false) {
            $this->emit('header');

           
            header(sprintf('HTTP/%s %s %s', $this->app->input->protocol(), $this->injectors['status'], $this->message()));

           
            if (!isset($this->injectors['headers']['Content-Type'])) {
                $this->injectors['headers']['Content-Type'] = $this->injectors['content_type'] . '; charset=' . $this->injectors['charset'];
            }

           
            if (!isset($this->injectors['headers']['Content-Length'])
                && is_numeric($this->injectors['length'])
            ) {
               
                $this->injectors['headers']['Content-Length'] = $this->injectors['length'];
            }

           
            if ($this->injectors['headers']) {
                foreach ($this->injectors['headers'] as $name => $value) {
                   
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            header("$name: $v", false);
                        }
                    } else {
                        header("$name: $value");
                    }
                }
            }

           
            if ($this->injectors['cookies']) {
                $_default = $this->app->cookie;
                if (!$_default) {
                    $_default = array(
                        'path'     => '/',
                        'domain'   => null,
                        'secure'   => false,
                        'httponly' => false,
                        'timeout'  => 0,
                        'sign'     => false,
                        'secret'   => '',
                        'encrypt'  => false,
                    );
                }
               
                foreach ($this->injectors['cookies'] as $key => $value) {
                    $_option = (array)$value[1] + $_default;
                    $value = $value[0];
                   
                    if (is_array($value)) {
                        $value = 'j:' . json_encode($value);
                    }

                   
                    if ($_option['sign'] && $_default['secret']) {
                        $value = 's:' . $value . '.' . hash_hmac('sha1', $value, $_default['secret']);
                    }

                   
                    if ($_option['encrypt']) {
                        $value = 'c:' . $this->app->cryptor->encrypt($value);
                    }

                   
                    setcookie($key, $value, time() + $_option['timeout'], $_option['path'], $_option['domain'], $_option['secure'], $_option['httponly']);
                }
            }
        }
        return $this;
    }

    public function render($template, array $data = null, array $options = array())
    {
        $this->app->render($template, $data, $options);
        return $this;
    }

    public function compile($template, array $data = null, array $options = array())
    {
        return $this->app->compile($template, $data, $options);
    }

    public function json($data)
    {
        $this->type('application/json');
        $this->body(json_encode($data));
        return $this;
    }

    public function jsonp($data, $callback = 'callback')
    {
        $this->type('application/javascript');
        $this->body($callback . '(' . json_encode($data) . ');');
        return $this;
    }

    public function xml($data, array $option = array())
    {
        $this->type('application/xml');
        $this->body(Xml::dump($data, $option));
        return $this;
    }

    public function download($file, $name = null)
    {
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename=' . ($name ? $name : basename($file)));
        $this->header('Content-Transfer-Encoding', 'binary');
        $this->header('Expires', '0');
        $this->header('Cache-Control', 'must-revalidate');
        $this->header('Pragma', 'public');
        $this->injectors['length'] = filesize($file);
        ob_clean();
        readfile($file);
        $this->end('');
    }

    public function redirect($url, $status = 302)
    {
        $this->injectors['status'] = $status;
        $this->injectors['headers']['location'] = $url == 'back' ? $this->app->input->refer() : $url;
        return $this;
    }

    public function clear()
    {
        $this->injectors = array(
                'status'       => 200,
                'body'         => '',
                'content_type' => 'text/html',
                'length'       => false,
                'charset'      => $this->app->charset,
                'headers'      => array(),
                'cookies'      => array(),
            ) + $this->injectors;
    }

    public function isCachable()
    {
        return $this->injectors['status'] >= 200 && $this->injectors['status'] < 300 || $this->injectors['status'] == 304;
    }

    public function isEmpty()
    {
        return in_array($this->injectors['status'], array(201, 204, 304));
    }

    public function isOk()
    {
        return $this->injectors['status'] === 200;
    }

    public function isSuccessful()
    {
        return $this->injectors['status'] >= 200 && $this->injectors['status'] < 300;
    }

    public function isRedirect()
    {
        return in_array($this->injectors['status'], array(301, 302, 303, 307));
    }

    public function isForbidden()
    {
        return $this->injectors['status'] === 403;
    }

    public function isNotFound()
    {
        return $this->injectors['status'] === 404;
    }

    public function isClientError()
    {
        return $this->injectors['status'] >= 400 && $this->injectors['status'] < 500;
    }

    public function isServerError()
    {
        return $this->injectors['status'] >= 500 && $this->injectors['status'] < 600;
    }

    public function __toString()
    {
        return $this->injectors['body'];
    }
}
}

namespace Pagon\Cli {

use Pagon\App;
use Pagon\EventEmitter;
use Pagon\Exception\Stop;
use Pagon\Exception\Pass;
use Pagon\Config;



class Input extends EventEmitter
{
    public $app;

    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array('params' => array(), 'app' => null) + $_SERVER);

        $this->app = & $this->injectors['app'];
    }

    public function id($id = null)
    {
        if (!isset($this->injectors['id'])) {
            $this->injectors['id'] = $id ? ($id instanceof \Closure ? $id() : (string)$id) : sha1(uniqid());
        }
        return $this->injectors['id'];
    }

    public function path()
    {
        if (!isset($this->injectors['path_info'])) {
            if (!empty($GLOBALS['argv'])) {
                $argv = $GLOBALS['argv'];
                array_shift($argv);
                $this->injectors['path_info'] = join(' ', $argv);
            } else {
                $this->injectors['path_info'] = '';
            }
        }

        return $this->injectors['path_info'];
    }

    public function method()
    {
        return 'CLI';
    }

    public function root()
    {
        return getcwd();
    }

    public function body()
    {
        if (!isset($this->injectors['body'])) {
            $this->injectors['body'] = @(string)file_get_contents('php://input');
        }
        return $this->injectors['body'];
    }

    public function param($key, $default = null)
    {
        return isset($this->injectors['params'][$key]) ? $this->injectors['params'][$key] : $default;
    }

    public function pass()
    {
        ob_get_level() && ob_clean();
        throw new Pass();
    }
}


class Output extends EventEmitter
{
    public $app;

    public $locals = array();

    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array(
            'status' => 0,
            'body'   => '',
        ));

        $this->app = & $this->injectors['app'];
        $this->locals = & $this->app->locals;
    }

    public function status($status = null)
    {
        if (is_numeric($status)) {
            $this->injectors['status'] = $status;
            return $this;
        }
        return $this->injectors['status'];
    }

    public function body($content = null)
    {
        if ($content !== null) {
            $this->injectors['body'] = $content;
            return $this;
        }

        return $this->injectors['body'];
    }

    public function write($data)
    {
        if (!$data) return $this->injectors['body'];

        $this->injectors['body'] .= $data;

        return $this;
    }

    public function end($data = '')
    {
        $this->write($data);
        throw new Stop();
    }

    public function clear()
    {
        $this->injectors = array(
                'status' => 0,
                'body'   => '',
            ) + $this->injectors;
    }

    public function ok()
    {
        return $this->injectors['status'] === 0;
    }

    public function __toString()
    {
        return $this->injectors['body'];
    }
}
}

namespace Pagon\Exception {


class Pass extends \Exception
{
}


class Stop extends \Exception
{
}

}

