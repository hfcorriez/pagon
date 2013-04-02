<?php
/*
 * (The MIT License)
 *
 * Copyright (c) 2013 hfcorriez@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Pagon;

class Fiber
{
    /**
     * @var array Injectors
     */
    protected $injectors;

    /**
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        $this->injectors = $injectors;
    }

    /**
     * Add injector
     *
     * @param string   $key
     * @param \Closure $value
     */
    public function __set($key, $value)
    {
        $this->injectors[$key] = $value;
    }

    /**
     * Get injector
     *
     * @param string $key
     * @throws \InvalidArgumentException
     * @return mixed|\Closure
     */
    public function __get($key)
    {
        if (!isset($this->injectors[$key])) throw new \InvalidArgumentException("Non-exists $key of injector");

        if ($this->injectors[$key] instanceof \Closure) {
            return $this->injectors[$key]();
        } else {
            return $this->injectors[$key];
        }
    }

    /**
     * Exists injector
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->injectors[$key]);
    }

    /**
     * Delete injector
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->injectors[$key]);
    }

    /**
     * Protect the closure
     *
     * @param \Closure|String $key
     * @param \Closure|String $closure
     * @return \Closure
     */
    public function protect($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }

        $func = function () use ($closure) {
            return $closure;
        };

        return $key ? ($this->injectors[$key] = $func) : $func;
    }

    /**
     * Share the closure
     *
     * @param \Closure|string $key
     * @param \Closure|null   $closure
     * @return \Closure
     */
    public function share($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }
        $that = $this;
        $func = function () use ($closure, $that) {
            static $obj;

            if ($obj === null) {
                $obj = $closure($that);
            }

            return $obj;
        };

        return $key ? ($this->injectors[$key] = $func) : $func;
    }

    /**
     * Extend the injector
     *
     * @param string   $key
     * @param \Closure $closure
     * @return \Closure
     * @throws \InvalidArgumentException
     */
    public function extend($key, \Closure $closure)
    {
        if (!isset($this->injectors[$key])) {
            throw new \InvalidArgumentException(sprintf('Injector "%s" is not defined.', $key));
        }

        $factory = $this->injectors[$key];

        if (!($factory instanceof \Closure)) {
            throw new \InvalidArgumentException(sprintf('Injector "%s" does not contain an object definition.', $key));
        }

        $that = $this;
        return $this->injectors[$key] = function () use ($closure, $factory, $that) {
            return $closure(call_user_func_array($factory, func_get_args()), $that);
        };
    }

    /**
     * Call injector
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (($closure = $this->$method) instanceof \Closure) {
            return call_user_func_array($closure, $args);
        }

        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
    }

    /**
     * Get or set injector
     *
     * @param string $key
     * @param mixed  $value
     * @return bool|mixed
     */
    public function raw($key, $value = null)
    {
        if ($value !== null) {
            $this->injectors[$key] = $value;
        }

        return isset($this->injectors[$key]) ? $this->injectors[$key] : false;
    }

    /**
     * Get all defined
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->injectors);
    }
}