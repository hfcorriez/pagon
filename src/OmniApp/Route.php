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
        App::config()->route[$path] = $runner;
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

            // Regex or Param check
            if (!strpos($route, ':') && strpos($route, '^') === false) {
                if ($path === $route) {
                    $complete = $route;
                }
            } else {
                // Try match
                if (preg_match(self::toRegex($route), $path, $matches)) {
                    $complete = $route;
                    array_shift($matches);
                    $params = $matches;
                }
            }

            // When complete the return
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
     * @param $regex
     * @return string
     */
    public static function toRegex($regex)
    {
        if ($regex[1] !== '^') {
            $regex = str_replace(array('/'), array('\\/'), $regex);
            if ($regex{0} == '^') {
                $regex = '/' . $regex . '/';
            } elseif (strpos($regex, ':')) {
                $regex = '/^' . preg_replace('/:([a-zA-Z0-9]+)/', '(?<$1>[a-zA-Z0-9\.\-\+_]+?)', $regex) . '$/';
            } else {
                $regex = '/^' . $regex . '$/';
            }
        }
        return $regex;
    }
}
