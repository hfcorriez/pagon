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
        App::$config['route'][$path] = $runner;
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
        if (empty(App::$config['route'])) throw new \Exception('No routes found.');

        //$path = trim($path, '/');
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = 'error';

        foreach (App::$config['route'] as $route => $controller) {
            if (!$route) continue;

            if (preg_match(self::toRegex($route), $path, $matches)) {
                $complete = array_shift($matches);
                $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                if ($params[0]) {
                    foreach ($matches as $match) array_unshift($params, $match);
                } else $params = $matches;
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
            App::$config['route']['error'] = $runner;
        }
        return !empty(App::$config['route']['error']) ? App::$config['route']['error'] : null;
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
            App::$config['route']['error'] = $runner;
        }
        return !empty(App::$config['route']['error']) ? App::$config['route']['error'] : null;
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
