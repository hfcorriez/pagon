<?php

namespace Pagon;

/**
 * Middleware
 * structure of base middleware
 *
 * @package Pagon
 * @property App                    app          Application to service
 * @property Http\Input|Cli\Input   input        Input of Application
 * @property Http\Output|Cli\Output output       Output of Application
 *
 */
abstract class Middleware extends EventEmitter
{
    const _CLASS_ = __CLASS__;

    /**
     * @var callable
     */
    protected $next;

    /**
     * Create new middleware or route
     *
     * @param string|\Closure $route
     * @param array           $options
     * @param array           $prefixes
     * @throws \InvalidArgumentException
     * @return bool|Route|callable
     */
    public static function build($route, $options = null, array $prefixes = array())
    {
        if (is_object($route)) return $route;

        if (!is_string($route)) throw new \InvalidArgumentException('The parameter $route need string');

        $prefixes[] = '';
        $prefixes[] = __NAMESPACE__ . "\\Middleware";

        foreach ($prefixes as $namespace) {
            if (!is_subclass_of($class = $namespace . '\\' . $route, __CLASS__, true)) continue;

            return new $class((array)$options);
        }

        throw new \InvalidArgumentException("Non-exists route class '$route'");
    }

    /**
     * Graft the route inner
     *
     * @param \Closure|string $route
     * @param array           $option
     * @throws \RuntimeException
     * @return mixed
     */
    public function graft($route, array $option = array())
    {
        if (!$route = self::build($route, $option)) {
            throw new \RuntimeException("Graft \"$route\" fail");
        }

        return call_user_func_array($route, array(
            $this->injectors['input'], $this->injectors['output'], $this->next
        ));
    }

    /**
     * @return mixed
     */
    abstract function call();

    /**
     * @param $input
     * @param $output
     * @param $next
     */
    public function __invoke($input, $output, $next)
    {
        $this->injectors['input'] = $input;
        $this->injectors['output'] = $output;
        $this->injectors['app'] = $input->app;
        $this->next = $next;
        $this->call();
    }

    /**
     * Call next
     */
    public function next()
    {
        call_user_func($this->next);
    }
}
