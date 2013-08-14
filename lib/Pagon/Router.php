<?php

namespace Pagon;

use Pagon\Exception\Pass;
use Pagon\Exception\Stop;

/**
 * Router
 * parse and manage the Route
 *
 * @package Pagon
 * @property App      app       Application to service
 * @property string   path      The current path
 * @property \Closure automatic Automatic closure for auto route
 */
class Router extends Middleware
{
    const _CLASS_ = __CLASS__;

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
        $this->options['app']->routes[$path] = (array)$route;

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
        if (!isset($this->options['app']->routes[$path])) $this->options['app']->routes[$path] = array();

        // Append
        $this->options['app']->routes[$path] = array_merge($this->options['app']->routes[$path], (array)$route);

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
        return $this->options['app']->routes[$path];
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

        $this->options['app']->names[$name] = $path;
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
        $_last = $this->lastPath();

        if (!isset($this->app->routes[$_last]['via']) || $method == '*') {
            $this->app->routes[$_last]['via'] = array();
        }

        if (!in_array($method, $this->app->routes[$_last]['via'])) {
            $this->app->routes[$_last]['via'][] = $method;
        }

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
        $this->options['app']->routes[$this->lastPath()]['defaults'] = $defaults;
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
        $this->options['app']->routes[$this->lastPath()]['rules'] = $rules;
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
        return isset($this->options['app']->names[$name]) ? $this->options['app']->names[$name] : false;
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
        $routes = (array)$this->options['app']->routes;

        // Loop routes for parse and dispatch
        foreach ($routes as $p => $route) {
            // Lookup rules
            $rules = isset($route['rules']) ? $route['rules'] : array();

            // Lookup defaults
            $defaults = isset($route['defaults']) ? $route['defaults'] : array();

            // Set via
            $via = isset($route['via']) ? $route['via'] : array();

            // Try to parse the params
            if (($param = self::match($this->options['path'], $p, $rules, $defaults)) !== false) {
                // Method match
                if ($via && !in_array($this->input->method(), $via)) continue;

                try {
                    $param && $this->options['app']->param($param);

                    return $this->run($route);
                    // If multiple controller
                } catch (Pass $e) {
                }
            }
        }

        // Try to check automatic route parser
        if (isset($this->injectors['automatic']) && is_callable($this->injectors['automatic'])) {
            $routes = (array)call_user_func($this->injectors['automatic'], $this->options['path']);

            foreach ($routes as $route) {
                // Try to check the class is route
                if (!is_subclass_of($route, Middleware::_CLASS_, true)) continue;

                try {
                    return $this->run($route);
                } catch (Pass $e) {
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
        if ($this->options['app']->prefixes) {
            foreach ($this->options['app']->prefixes as $path => $namespace) {
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
        $run = false;

        $pass = function ($route) use ($build, &$param, &$run) {
            $runner = $route instanceof \Closure ? $route : $build($route);
            if (is_callable($runner)) {
                $run = true;
                call_user_func_array($runner, $param);
                return true;
            } else {
                throw new \InvalidArgumentException("Route '$route' is not exists");
            }
        };

        $param = array(
            $this->options['app']->input,
            $this->options['app']->output,
            function () use (&$routes, $pass) {
                do {
                    $route = next($routes);
                } while ($route && !is_numeric(key($routes)));

                if (!$route) throw new Pass;

                $pass($route);
            }
        );

        $pass(current($routes));

        return $run;
    }

    /**
     * Run the route
     *
     * @param string $route
     * @param array  $params
     * @return bool
     */
    public function handle($route, $params = array())
    {
        if (isset($this->options['app']->routes[$route])) {
            $params && $this->options['app']->param($params);
            return $this->run($this->options['app']->routes[$route]);
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
                $this->options['app']->handleError('404');
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
        $path = array_keys($this->options['app']->routes);
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
