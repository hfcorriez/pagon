<?php

namespace Omni;

class Route
{
    private static $_routes = array();

    public static function on($path, $runner)
    {
        self::$_routes[$path] = $runner;
    }

    public static function parse($path)
    {
        $routes = App::$config['route'] + self::$_routes;
        if (!is_array($routes) || empty($routes)) throw new Exception('config["routes"] must be set before.');

        $path = trim($path, '/');

        if ($path === '') return array($routes[''], '', array());
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

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

        if (!isset($routes['404'])) throw new Exception('config["route"]["404"] not set.');
        return array($routes['404'], $path, array($path));
    }
}