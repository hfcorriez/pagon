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

/**
 * Fiber
 * dependency injector container and manager
 *
 * @package Pagon
 */
class Fiber implements \ArrayAccess
{
    /**
     * @var array Injectors
     */
    protected $injectors = array();

    /**
     * @var array Injectors alias map for functions
     */
    protected $injectorsMap = array();

    /**
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        $this->injectors = $injectors + $this->injectors;

        // Apply the mapping
        foreach ($this->injectorsMap as $k => $v) {
            $k = is_numeric($k) ? $v : $k;
            $this->injectors[$k] = array($k, 'F$' => 2);
        }
    }

    /**
     * Add injector
     *
     * @param string   $key
     * @param \Closure $value
     */
    public function __set($key, $value)
    {
        $this->injectors[$key] = $value instanceof \Closure ? array($value, 'F$' => 1) : $value;
    }

    /**
     * Get injector
     *
     * @param string $key
     * @return mixed|\Closure
     */
    public function &__get($key)
    {
        if (!isset($this->injectors[$key])) {
            trigger_error('Undefined index "' . $key . '" of ' . get_class($this), E_USER_NOTICE);
            return null;
        }

        // Inject or share
        if (is_array($this->injectors[$key])
            && isset($this->injectors[$key]['F$'])
            && isset($this->injectors[$key][0])
        ) {
            switch ($this->injectors[$key]['F$']) {
                case 2:
                    // Share with mapping
                    $this->injectors[$key] = $this->{$this->injectors[$key][0]}();
                    $tmp = & $this->injectors[$key];
                    break;
                case 1:
                    // Share
                    $this->injectors[$key] = call_user_func($this->injectors[$key][0]);
                    $tmp = & $this->injectors[$key];
                    break;
                case 0:
                    // Factory
                    $tmp = call_user_func($this->injectors[$key][0]);
                    break;
                default:
                    $tmp = null;
            }
        } else {
            $tmp = & $this->injectors[$key];
        }
        return $tmp;
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
     * Factory a service
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

        return $key ? ($this->injectors[$key] = array($closure, 'F$' => 0)) : array($closure, 'F$' => 0);
    }

    /**
     * Inject the function
     *
     * @param string   $key
     * @param callable $closure
     */
    public function inject($key, \Closure $closure)
    {
        $this->injectors[$key] = $closure;
    }

    /**
     * Share service
     *
     * @param \Closure|string $key
     * @param \Closure        $closure
     * @return \Closure
     */
    public function share($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }

        return $key ? ($this->injectors[$key] = array($closure, 'F$' => 1)) : array($closure, 'F$' => 1);
    }

    /**
     * Extend the injector
     *
     * @param string   $key
     * @param \Closure $closure
     * @return \Closure
     */
    public function extend($key, \Closure $closure)
    {
        $that = $this;
        return $this->injectors[$key] = array(function () use ($closure, $that) {
            return $closure($that->$key, $that);
        }, 'F$' => isset($this->injectors[$key]['F$']) ? $this->injectors[$key]['F$'] : 1);
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
        if (!isset($this->injectors[$method]) || !($closure = $this->injectors[$method]) instanceof \Closure) {
            throw new \BadMethodCallException(sprintf('Call to undefined method "%s::%s()', get_called_class(), $method));
        }

        return call_user_func_array($closure, $args);
    }

    /**
     * Get injector
     *
     * @param string $key
     * @param mixed  $default
     * @return bool|mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->injectors;
        }

        return isset($this->injectors[$key]) ? $this->injectors[$key] : $default;
    }

    /**
     * Set injector
     *
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->injectors[$k] = $v;
            }
        } else {
            $this->injectors[$key] = $value;
        }

        return $this;
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

    /**
     * Has the key?
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->injectors);
    }

    /**
     * Append the multiple injectors
     *
     * @param array $injectors
     * @return $this
     */
    public function append(array $injectors)
    {
        $this->injectors = $injectors + $this->injectors;
        return $this;
    }

    /*
     * Implements the interface to support array access
     */

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function &offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }
}