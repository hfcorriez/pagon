<?php

namespace OmniApp;

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
        App::config('route.' . $path, $runner, true);
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
        $routes = (array)App::config('route');

        //$path = trim($path, '/');
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

        foreach ($routes as $route => $controller) {
            if (!$route) continue;
            $complete = false;
            $params = array();

            if (!strpos($route, ':')) {
                if ($path === $route) {
                    $complete = $route;
                }
            } else {
                if (preg_match(self::toRegex($route), $path, $matches)) {
                    $complete = array_shift($matches);
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if ($params[0]) {
                        foreach ($matches as $match) array_unshift($params, $match);
                    } else $params = $matches;
                }
            }

            if ($complete) {
                return array($controller, $complete, $params);
            }
        }

        return self::notFound();
    }

    /**
     * Get or set not found route
     *
     * @param mixed $runner
     * @return mixed
     */
    public static function notFound($runner = null)
    {
        if ($runner) {
            App::config('route.404', $runner, true);
        }
        return App::config('route.404');
    }


    /**
     * Get or set error runner
     *
     * @param mixed $runner
     * @return mixed
     */
    public static function error($runner = null)
    {
        if ($runner) {
            App::config('route.error', $runner, true);
        }
        return App::config('route.error');
    }

    /**
     * To regex
     *
     * @param $string
     * @return string
     */
    public static function toRegex($string)
    {
        $regex = str_replace(array('/'), array('\/'), $string);
        $regex = '/^' . $regex . '$/';
        return $regex;
    }
}
