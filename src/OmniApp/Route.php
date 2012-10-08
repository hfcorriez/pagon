<?php

namespace OmniApp;

use OmniApp\Exception\Pass;

/**
 * Route
 */
class Route
{
    const _CLASS_ = __CLASS__;

    /**
     * Register a route for path
     *
     * @static
     * @param $path
     * @param $runner
     */
    public static function on($path, $runner)
    {
        if (func_num_args() > 2) {
            $_args = func_get_args();
            $path = array_shift($_args);
            $runner = $_args;
        }
        App::config()->route[$path] = $runner;
    }

    /**
     * Run route
     *
     * @static
     * @return array
     * @throws \Exception
     * @return array|mixed
     */
    public static function dispatch()
    {
        $routes = (array)App::config('route');

        // Set path
        $path = App::isCli() ? '/' . join('/', array_slice($GLOBALS['argv'], 1)) : App::$request->path();

        //$path = trim($path, '/');
        if ($path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) $path = '404';

        // Loop routes for parse and dispatch
        foreach ($routes as $route => $controller) {
            if (!$route) continue;

            // Try to parse the params
            if (($params = self::parse($path, $route)) !== false) {
                // For save if dispatched?
                try {
                    return self::next($controller, $params);
                    // If multiple controller
                } catch (Pass $e) {
                    // When catch Next, continue next route
                }
            }
        }

        return false;
    }

    /**
     * Run next
     *
     * @param array|string $middleware
     * @param array        $params
     * @throws Exception\Pass
     * @return bool
     */
    public static function next($middleware = null, $params = array())
    {
        // Save queue for the controllers
        static $q = null;
        // Save arguments
        static $a = null;

        // Default dispatch value
        $_dispatched = false;

        // Set middleware first
        if ($middleware) {
            $q = (array)$middleware;
            $a = $params;
        }

        if ($q) {
            if ($middleware) {
                // First call the first controller
                $_dispatched = self::call(current($q), $a);
            } elseif ($_next = next($q)) {
                // After first, use cursor the get controller
                $_dispatched = self::call($_next, $a);
            } else {
                // If last controller call the next, pass the next route
                throw new Pass();
            }
            if (!$_dispatched) unset($q, $a);
        }

        return $_dispatched;
    }

    /**
     * Parse the route
     *
     * @param $path
     * @param $route
     * @return array|bool
     */
    public static function parse($path, $route)
    {
        $params = false;

        // Regex or Param check
        if (!strpos($route, ':') && strpos($route, '^') === false) {
            if ($path === $route) {
                $params = array();
            }
        } else {
            // Try match
            if (preg_match(self::toRegex($route), $path, $matches)) {
                array_shift($matches);
                $params = $matches;
            }
        }

        // When complete the return
        return $params;
    }

    /**
     * Control the runner
     *
     * @param       $runner
     * @param array $params
     * @return bool
     */
    public static function call($runner, $params = array())
    {
        // Save next closure
        $next = function () {
            Route::next();
        };

        // Set params
        if (!App::isCli()) App::$request->params = $params;

        // Check runner
        if (is_string($runner) && Controller::factory($runner, array(App::$request, App::$response, $next))) {
            // Support controller class string
            return true;
        } elseif (is_callable($runner)) {
            // Closure function support
            call_user_func_array($runner, array(App::$request, App::$response, $next));
            return true;
        }
        return false;
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
        return ($_route = App::config('route.404')) ? self::call($_route) : false;
    }


    /**
     * Get or set error runner
     *
     * @param mixed $runner
     * @return mixed
     */
    public static function error($runner = null)
    {
        $_args = array();
        if (!$runner instanceof \Exception) {
            App::config('route.error', $runner, true);
        } else {
            $_args = array($runner);
        }
        return ($_route = App::config('route.error')) ? self::call($_route, $_args) : false;
    }

    /**
     * To regex
     *
     * @param string $regex
     * @return string
     */
    protected static function toRegex($regex)
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
