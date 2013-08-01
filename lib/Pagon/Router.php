<?php

namespace Pagon;

use Pagon\Exception\Pass;
use Pagon\Exception\Stop;

/**
 * Router
 * parse and manage the Route
 *
 * @package Pagon
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
     * @return $this
     */
    public function set($path, $route, $more = null)
    {
        if ($more) {
            $_args = func_get_args();
            $path = array_shift($_args);
            $route = $_args;
        }

        // Set route
        $this->app->routes[$path] = (array)$route;

        return $this;
    }

    /**
     * Add router for path
     *
     * @param string               $path
     * @param \Closure|string      $route
     * @param \Closure|string|null $more
     * @return $this
     */
    public function add($path, $route, $more = null)
    {
        if ($more) {
            $_args = func_get_args();
            $path = array_shift($_args);
            $route = $_args;
        }

        // Init route node
        if (!isset($this->app->routes[$path])) $this->app->routes[$path] = array();

        // Append
        $this->app->routes[$path] = array_merge($this->app->routes[$path], (array)$route);

        return $this;
    }

    /**
     * Get the controllers
     *
     * @param $path
     * @return mixed
     */
    public function get($path)
    {
        return $this->app->routes[$path];
    }

    /**
     * Naming the route
     *
     * @param string $name
     * @param string $path
     * @return $this
     */
    public function name($name, $path = null)
    {
        if ($path === null) {
            $path = $this->lastPath();
        }

        $this->app->names[$name] = $path;
        return $this;
    }

    /**
     * Via methods
     *
     * @param $method
     * @return $this
     */
    public function via($method)
    {
        $this->app->routes[$this->lastPath()]['via'] = (array)$method;
        return $this;
    }


    /**
     * Set default parameters
     *
     * @param array $defaults
     * @return $this
     */
    public function defaults($defaults = array())
    {
        $this->app->routes[$this->lastPath()]['defaults'] = $defaults;
        return $this;
    }

    /**
     * Set rules for parameters
     *
     * @param array $rules
     * @return $this
     */
    public function rules($rules = array())
    {
        $this->app->routes[$this->lastPath()]['rules'] = $rules;
        return $this;
    }

    /**
     * Get path by name
     *
     * @param string $name
     * @return mixed
     */
    public function path($name)
    {
        return isset($this->app->names[$name]) ? $this->app->names[$name] : false;
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
        $routes = (array)$this->app->routes;

        // Loop routes for parse and dispatch
        foreach ($routes as $p => $route) {
            $rules = $defaults = array();

            // Lookup rules
            if (isset($route['rules'])) {
                $rules = $route['rules'];
                unset($route['rules']);
            }

            // Lookup defaults
            if (isset($route['defaults'])) {
                $defaults = $route['defaults'];
                unset($route['defaults']);
            }

            // Set via
            $via = isset($route['via']) ? $route['via'] : array('*');

            // Try to parse the params
            if (($param = self::match($this->options['path'], $p, $rules, $defaults)) !== false) {
                // Method match
                if ($via !== array('*') && !in_array($this->input->method(), $via)) continue;

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

            if ($route && class_exists($route)) {
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
     * @param $routes
     * @return array|string
     */
    public function run($routes)
    {
        $prefixes = array();
        // Prefixes Lookup
        if ($this->app->prefixes) {
            foreach ($this->app->prefixes as $path => $namespace) {
                if (strpos($this->options['path'], $path) === 0) {
                    $prefixes[99 - strlen($path)] = $namespace;
                }
            }
            ksort($prefixes);
        }
        return $this->pass($routes, function ($route) use ($prefixes) {
            return Route::build($route, array(), $prefixes);
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
     * Set auto route closure
     *
     * @param callable $closure
     * @return $this
     */
    public function automatic(\Closure $closure)
    {
        $this->automatic = $closure;
        return $this;
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
        if (isset($this->app->routes[$route])) {
            $args && $this->app->param($args);
            return $this->run($this->app->routes[$route]);
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
                $this->app->handleError('404');
            }
        } catch (Stop $e) {
        }
    }

    /**
     * Last path of routes
     *
     * @return mixed
     */
    protected function lastPath()
    {
        $path = array_keys($this->app->routes);
        return end($path);
    }

    /**
     * Parse the route
     *
     * @static
     * @param string $path
     * @param string $route
     * @param array  $rules
     * @param array  $defaults
     * @return array|bool
     */
    protected static function match($path, $route, $rules = array(), $defaults = array())
    {
        $param = false;

        // Regex or Param check
        if (!strpos($route, ':') && strpos($route, '^') === false) {
            if ($path === $route || $path === $route . '/') {
                $param = array();
            }
        } else {
            // Try match
            if (preg_match(self::pathToRegex($route, $rules), $path, $matches)) {
                array_shift($matches);
                $param = $matches;
            }
        }

        // When complete the return
        return $param === false ? false : ($defaults + $param);
    }

    /**
     * To regex
     *
     * @static
     * @param string $path
     * @param array  $rules
     * @return string
     */
    protected static function pathToRegex($path, $rules = array())
    {
        if ($path[1] !== '^') {
            $path = str_replace(array('/'), array('\\/'), $path);
            if ($path{0} == '^') {
                // As regex
                $path = '/' . $path . '/';
            } elseif (strpos($path, ':')) {
                $path = str_replace(array('(', ')'), array('(?:', ')?'), $path);

                if (!$rules) {
                    // Need replace
                    $path = '/^' . preg_replace('/(?<!\\\\):([a-zA-Z0-9]+)/', '(?<$1>[^\/]+?)', $path) . '\/?$/';
                } else {
                    $path = '/^' . preg_replace_callback('/(?<!\\\\):([a-zA-Z0-9]+)/', function ($match) use ($rules) {
                            return '(?<' . $match[1] . '>' . (isset($rules[$match[1]]) ? $rules[$match[1]] : '[^\/]+') . '?)';
                        }, $path) . '\/?$/';
                }
            } else {
                // Full match
                $path = '/^' . $path . '\/?$/';
            }

            // * support
            if (strpos($path, '*')) {
                $path = str_replace('*', '([^\/]+?)', $path);
            }
        }
        return $path;
    }
}
