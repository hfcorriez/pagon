<?php

namespace OmniApp;

use OmniApp\Middleware;
use OmniApp\Exception\Pass;
use OmniApp\Exception\Stop;

/**
 * Route
 */
class Route extends Middleware
{
    const _CLASS_ = __CLASS__;

    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Register a route for path
     *
     * @param string               $path
     * @param \Closure|string      $runner
     * @param \Closure|string|null $more
     */
    public function on($path, $runner, $more = null)
    {
        if ($more) {
            $_args = func_get_args();
            $path = array_shift($_args);
            $runner = $_args;
        }
        App::config()->route[$path] = $runner;
    }

    /**
     * Run route
     *
     * @return array
     * @throws \Exception
     * @return array|mixed
     */
    public function dispatch()
    {
        $routes = (array)App::config('route');

        // No routes
        if (!$routes) return false;

        //$path = trim($path, '/');
        if ($this->path AND !preg_match('/^[\w\-~\/\.]{1,400}$/', $this->path)) {
            return false;
        }

        // Loop routes for parse and dispatch
        foreach ($routes as $p => $link) {
            if (!$p) continue;

            // Try to parse the params
            if (($params = self::parseRoute($this->path, $p)) !== false) {
                // For save if dispatched?
                try {
                    return $this->nextLink($link, $params);
                    // If multiple controller
                } catch (Pass $e) {
                    // When catch Next, continue next route
                }
            }
        }

        return false;
    }

    /**
     * Call for middleware
     */
    public function call()
    {
        ob_start();
        try {
            if (!$this->dispatch()) {
                $this->notFound();
            }
        } catch (Stop $e) {
        }
        App::$response->write(ob_get_clean(), -1);
    }

    /**
     * Run next
     *
     * @param array|string $middleware
     * @param array        $params
     * @throws Exception\Pass
     * @return bool
     */
    public function nextLink($middleware = null, $params = array())
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
                $_dispatched = $this->run(current($q), $a);
            } elseif ($_next = next($q)) {
                // After first, use cursor the get controller
                $_dispatched = $this->run($_next, $a);
            } else {
                // If last controller call the next, pass the next route
                throw new Pass();
            }
            if (!$_dispatched) unset($q, $a);
        }

        return $_dispatched;
    }

    /**
     * Control the runner
     *
     * @param       $runner
     * @param array $params
     * @return bool
     */
    public function run($runner, $params = array())
    {
        $self = $this;
        // Save next closure
        $next = function () use ($self) {
            $self->nextLink();
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
    public function notFound($runner = null)
    {
        if ($runner) {
            App::config('route.404', $runner, true);
        }
        return ($_route = App::config('route.404')) ? $this->run($_route) : false;
    }


    /**
     * Get or set error runner
     *
     * @param mixed $runner
     * @return mixed
     */
    public function error($runner = null)
    {
        $_args = array();
        if (!$runner instanceof \Exception) {
            App::config('route.error', $runner, true);
        } else {
            $_args = array($runner);
        }
        return ($_route = App::config('route.error')) ? $this->run($_route, $_args) : false;
    }

    /**
     * Parse the route
     *
     * @static
     * @param $path
     * @param $route
     * @return array|bool
     */
    protected static function parseRoute($path, $route)
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
