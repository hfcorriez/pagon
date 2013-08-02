<?php

use Pagon\App;
use Pagon\Cache;
use Pagon\Html;
use Pagon\Logger;
use Pagon\Paginator;
use Pagon\Url;

/**
 * Get current App
 *
 * @return App
 */
function app()
{
    return App::self();
}

/**
 * Get the query from url
 *
 * @param string $key
 * @param string $default
 * @return mixed
 */
function query($key, $default = null)
{
    return App::self()->input->query($key, $default);
}

/**
 * Get the data from post
 *
 * @param string $key
 * @param string $default
 * @return mixed
 */
function data($key, $default = null)
{
    return App::self()->input->data($key, $default);
}

/**
 * Get cookie
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function cookie($key, $default = null)
{
    return App::self()->input->cookie($key, $default);
}

/**
 * Build url
 *
 * @param string $path
 * @param array  $query
 * @param bool   $full
 * @return string
 */
function url($path, array $query = null, $full = false)
{
    return Url::to($path, $query, $full);
}

/**
 * Build route url
 *
 * @param string $name
 * @param array  $params
 * @param array  $query
 * @param bool   $full
 */
function route_url($name, array $params, array $query = null, $full = false)
{
    return Url::route($name, $params, $query, $full);
}

/**
 * Get assert url
 *
 * @param string $path
 * @param array  $query
 * @param bool   $full
 * @return string
 */
function assert_url($path, array $query = null, $full = false)
{
    return Url::asset($path, $query, $full);
}

/**
 * Get current url
 *
 * @param array $query
 * @param bool  $full
 * @return string
 */
function current_url(array $query = null, $full = false)
{
    return Url::current($query, $full);
}

/**
 * Get site url
 *
 * @return mixed
 */
function site_url()
{
    return Url::site();
}

/**
 * Build page
 *
 * @param string $pattern
 * @param int    $total
 * @param int    $size
 * @param int    $displays
 * @return Paginator
 */
function page($pattern = "/page/(:num)", $total = 0, $size = 10, $displays = 10)
{
    return Paginator::create($pattern, $total, $size, $displays);
}

/**
 * Get config
 *
 * @param string       $key
 * @param string|array $value
 * @return string|array
 */
function config($key, $value = null)
{
    if ($value === null) {
        return App::self()->get($key);
    }
    App::self()->set($key, $value);
}

/**
 * Get or set cache
 *
 * @param string $name
 * @param string $key
 * @param mixed  $value
 * @return Cache|bool|string|array
 */
function cache($name, $key = null, $value = null)
{
    if ($key === null) {
        return Cache::dispense($name);
    } else if ($value !== null) {
        return Cache::dispense($name)->set($key, $value);
    } else {
        return Cache::dispense($name)->get($key);
    }
}

/**
 * Get exist file path
 *
 * @param string $file
 * @return bool|string
 */
function path($file)
{
    return App::self()->path($file);
}

/**
 * Load file
 *
 * @param string $file
 * @return bool|mixed
 */
function load($file)
{
    return App::self()->load($file);
}

/**
 * Mount the dir
 *
 * @param string $path
 * @param string $dir
 */
function mount($path, $dir = null)
{
    App::self()->mount($path, $dir);
}

/**
 * Set local value
 *
 * @param string $key
 * @param mixed  $value
 * @return mixed
 */
function local($key, $value = null)
{
    $app = App::self();
    if ($value === null) {
        return isset($app->locals[$key]) ? $app->locals[$key] : null;
    }

    $app->locals[$key] = $value;
}

/**
 * Render template
 *
 * @param string $path
 * @param array  $data
 * @param array  $options
 */
function render($path, array $data = null, array $options = array())
{
    App::self()->render($path, $data, $options);
}

/**
 * Compile template
 *
 * @param string $path
 * @param array  $data
 * @param array  $options
 * @return \Pagon\View
 */
function compile($path, array $data = null, array $options = array())
{
    return App::self()->output->compile($path, $data, $options);
}

/**
 * Dispense logger
 *
 * @param string $name
 * @return mixed
 */
function logger($name = 'log')
{
    return Logger::dispense($name);
}

/**
 * Log debug
 *
 * @param string $message
 * @param mixed  $context
 */
function log_debug($message, $context = null)
{
    call_user_func_array(array('Pagon\Logger', 'debug'), func_get_args());
}

/**
 * Log info
 *
 * @param string $message
 * @param mixed  $context
 */
function log_info($message, $context = null)
{
    call_user_func_array(array('Pagon\Logger', 'info'), func_get_args());
}

/**
 * Log warn
 *
 * @param string $message
 * @param mixed  $context
 */
function log_warn($message, $context = null)
{
    call_user_func_array(array('Pagon\Logger', 'warn'), func_get_args());
}

/**
 * Log error
 *
 * @param string $message
 * @param mixed  $context
 */
function log_error($message, $context = null)
{
    call_user_func_array(array('Pagon\Logger', 'error'), func_get_args());
}

/**
 * Log critical
 *
 * @param string $message
 * @param mixed  $context
 */
function log_critical($message, $context = null)
{
    call_user_func_array(array('Pagon\Logger', 'critical'), func_get_args());
}

/*****************************************************
 * Utils functions
 *****************************************************/

/**
 * Array get
 *
 * @param array      $arr
 * @param int|string $index
 * @return mixed
 */
function array_get($arr, $index)
{
    return isset($arr[$index]) ? $arr[$index] : null;
}

/**
 * Pluck the values to new array with given key
 *
 * @param array  $array
 * @param string $key
 * @return array
 */
function array_pluck($array, $key)
{
    return array_map(function ($v) use ($key) {
        return is_object($v) ? $v->$key : $v[$key];

    }, $array);
}

/**
 * Get new array only contains the keys
 *
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_only($array, $keys)
{
    return array_intersect_key($array, array_flip((array)$keys));
}

/**
 * Get new array except the keys
 *
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_except($array, $keys)
{
    return array_diff_key($array, array_flip((array)$keys));
}

/**
 * Get magic quotes
 *
 * @return bool
 */
function magic_quotes()
{
    return function_exists('get_magic_quotes_gpc') and get_magic_quotes_gpc();
}

/**
 * Check string is start with given needle
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function start_with($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}

/**
 * Check string is end with given needle
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function end_with($haystack, $needle)
{
    return $needle == substr($haystack, strlen($haystack) - strlen($needle));
}

/**
 * If string contains any needles
 *
 * @param string       $haystack
 * @param array|string $needle
 * @return bool
 */
function str_contains($haystack, $needle)
{
    foreach ((array)$needle as $n) {
        if (strpos($haystack, $n) !== false) return true;
    }

    return false;
}

/**
 * Get human readable size
 *
 * @param int $size
 * @return string
 */
function human_size($size)
{
    $units = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $units[$i];
}

/*****************************************************
 * Html functions
 *****************************************************/

/**
 * Create dom element
 *
 * @param string $name
 * @param string $text
 * @param array  $attributes
 * @return string
 */
function html($name, $text, array $attributes = array())
{
    return Html::dom($name, $text, $attributes);
}

/**
 * Quick function for entitles
 *
 * @param string $string
 * @return string
 */
function e($string)
{
    return Html::entities($string);
}

/**
 * Create link
 *
 * @param string $src
 * @param string $text
 * @param array  $attributes
 * @return string
 */
function a($src, $text, array $attributes = array())
{
    return Html::a($src, $text, $attributes);
}

/**
 * Create style link
 *
 * @param string $src
 * @return string
 */
function style($src)
{
    return Html::link($src);
}

/**
 * Create script link
 *
 * @param string $src
 * @param array  $attributes
 * @return string
 */
function script($src, array $attributes = array())
{
    return Html::script($src, $attributes);
}

/**
 * Create select options
 *
 * @param string $name
 * @param array  $options
 * @param string $selected
 * @param array  $attributes
 * @return mixed
 */
function select($name, array $options, $selected = null, array $attributes = array())
{
    return Html::select($name, $options, $selected, $attributes);
}

/*****************************************************
 * Util functions
 *****************************************************/

/**
 *
 * Defer execution
 *
 * @param Closure $closure
 */
function defer(Closure $closure)
{
    App::self()->defer($closure);
}

/**
 * Or
 *
 * @return bool
 */
function __or()
{
    $last = false;
    foreach (func_get_args() as $arg) {
        if ($arg) return $arg;
        $last = $arg;
    }
    return $last;
}

/**
 * And
 *
 * @return bool
 */
function __and()
{
    $last = false;
    foreach (func_get_args() as $arg) {
        if (!$arg) return $arg;
        $last = $arg;
    }
    return $last;
}