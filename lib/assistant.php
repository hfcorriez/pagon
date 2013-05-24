<?php

use Pagon\App;
use Pagon\Cache;
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
function get($key, $default = null)
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
function post($key, $default = null)
{
    return App::self()->input->data($key, $default);
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