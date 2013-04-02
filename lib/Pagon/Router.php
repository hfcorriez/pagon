<?php

namespace Pagon;

use Pagon\Middleware;
use Pagon\Exception\Pass;
use Pagon\Exception\Stop;

/**
 * Route
 */
class Router extends Middleware
{
    const _CLASS_ = __CLASS__;

    /**
     * @var App
     */
    public $app;

    /**
     * @var \Closure
     */
    protected $automatic;

    /**
     * Register a route for path
     *
     * @param string               $path
     * @param \Closure|string      $route
     * @param \Closure|string|null $more
     */
    public function set($path, $route, $more = null)
    {
        if ($more) {
            $_args = func_get_args();
            $path = array_shift($_args);
            $route = $_args;
        }
        $this->app->config['route'][$path] = $route;
    }

    /**
     * Get the controllers
     *
     * @param $path
     * @return mixed
     */
    public function get($path)
    {
        return $this->app->config['route'][$path];
    }

    /**
     * Run route
     *
     * @return array
     * @throws \Exception
     * @return bool
     */
    public function dispatch()
    {
        // Check path
        if ($this->options['path'] === null) return false;

        // Get routes
        $routes = (array)$this->app->config['route'];

        // Loop routes for parse and dispatch
        foreach ($routes as $p => $route) {
            // Try to parse the params
            if (($param = self::match($this->options['path'], $p)) !== false) {
                try {
                    $param && $this->app->param($param);

                    return $this->run($route);

                    // If multiple controller
                } catch (Pass $e) {
                    // When catch Next, continue next route
                }
            }
        }

        // Try to check automatic route parser
        if ($this->automatic instanceof \Closure) {
            $route = call_user_func($this->automatic, $this->options['path']);

            if (class_exists($route)) {
                try {
                    return $this->run($route);
                } catch (Pass $e) {
                    // When catch Next, continue next route
                }
            }
        }

        return false;
    }

    /**
     * Run the routes
     *
     * @param $route
     * @return array|string
     */
    public function run($route)
    {
        return $this->pass($route, function ($r) {
            return Route::build($r);
        });
    }

    /**
     * Run the route
     *
     * @param array|string $routes
     * @param  \Closure    $build
     * @throws \InvalidArgumentException
     * @throws Exception\Pass
     * @return array|string
     */
    public function pass($routes, \Closure $build)
    {
        if (!$routes) return false;

        $routes = (array)$routes;
        $param = null;

        $pass = function ($route) use ($build, &$param) {
            $runner = $route instanceof \Closure ? $route : $build($route);
            if (is_callable($runner)) {
                call_user_func_array($runner, $param);
                return true;
            } else {
                throw new \InvalidArgumentException("Route '$route' is not exists");
            }
        };

        $param = array(
            $this->app->input,
            $this->app->output,
            function () use (&$routes, $pass) {
                if (!$route = next($routes)) {
                    throw new Pass;
                }

                $pass($route);
            }
        );

        return $pass(current($routes));
    }

    /**
     * @param callable $closure
     */
    public function automatic(\Closure $closure)
    {
        $this->automatic = $closure;
    }

    /**
     * Run the route
     *
     * @param string $route
     * @param array  $args
     * @return bool
     */
    public function handle($route, $args = array())
    {
        if (isset($this->app->config['route'][$route])) {
            $args && $this->app->param($args);
            return $this->run($this->app->config['route'][$route]);
        }
        return false;
    }

    /**
     * Call for middleware
     */
    public function call()
    {
        try {
            if (!$this->dispatch()) {
                $this->app->notFound();
            }
        } catch (Stop $e) {
        }
    }

    /**
     * Parse the route
     *
     * @static
     * @param $path
     * @param $route
     * @return array|bool
     */
    protected static function match($path, $route)
    {
        $param = false;

        // Regex or Param check
        if (!strpos($route, ':') && strpos($route, '^') === false) {
            if ($path === $route) {
                $param = array();
            }
        } else {
            // Try match
            if (preg_match(self::toRegex($route), $path, $matches)) {
                array_shift($matches);
                $param = $matches;
            }
        }

        // When complete the return
        return $param;
    }

    /**
     * To regex
     *
     * @static
     * @param string $regex
     * @return string
     */
    protected static function toRegex($regex)
    {
        if ($regex[1] !== '^') {
            $regex = str_replace(array('/'), array('\\/'), $regex);
            if ($regex{0} == '^') {
                // As regex
                $regex = '/' . $regex . '/';
            } elseif (strpos($regex, ':')) {
                // Need replace
                $regex = '/^' . preg_replace('/\(:([a-zA-Z0-9]+)\)/', '(?<$1>[^\/]+?)', $regex) . '\/?$/';
            } else {
                // Full match
                $regex = '/^' . $regex . '$/';
            }

            // * support
            if (strpos($regex, '*')) {
                $regex = str_replace('*', '([^\/]+?)', $regex);
            }
        }
        return $regex;
    }
}
