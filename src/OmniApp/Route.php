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

        $path = trim($path, '/');
        if ($path === '') return array(App::$config['route'][''], '', array());
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

        foreach (App::$config['route'] as $route => $controller) {
            if (!$route) continue;

            if ($route{0} === '/') {
                if (preg_match($route, $path, $matches)) {
                    $complete = array_shift($matches);
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if ($params[0]) {
                        foreach ($matches as $match) array_unshift($params, $match);
                    } else $params = $matches;
                    return array($controller, $complete, $params);
                }
            } else {
                if (mb_substr($path, 0, mb_strlen($route)) === $route) {
                    $params = explode('/', trim(mb_substr($path, mb_strlen($route)), '/'));
                    return array($controller, $route, $params);
                }
            }
        }

        if (!isset(App::$config['route']['404'])) throw new \Exception('404 page found, but no 404 route found.');
        return array(App::$config['route']['404'], $path, array($path));
    }
}
