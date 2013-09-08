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
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        $this->injectors = $injectors + $this->injectors;
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
            && isset($this->injectors[$key]['fiber'])
            && isset($this->injectors[$key][0])
            && $this->injectors[$key][0] instanceof \Closure
        ) {
            switch ($this->injectors[$key]['fiber']) {
                case 1:
                    // share
                    $this->injectors[$key] = call_user_func($this->injectors[$key][0]);
                    $tmp = & $this->injectors[$key];
                    break;
                case 0:
                    // inject
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
     * Protect the closure
     *
     * @param \Closure|String $key
     * @param \Closure|String $closure
     * @return \Closure
     */
    public function inject($key, \Closure $closure = null)
    {
        if (!$closure) {
            $closure = $key;
            $key = false;
        }

        return $key ? ($this->injectors[$key] = array($closure, 'fiber' => 0)) : array($closure, 'fiber' => 0);
    }

    /**
     * Share the closure
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

        return $key ? ($this->injectors[$key] = array($closure, 'fiber' => 1)) : array($closure, 'fiber' => 1);
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
        $factory = isset($this->injectors[$key]) ? $this->injectors[$key] : null;
        $that = $this;
        return $this->injectors[$key] = array(function () use ($closure, $factory, $that) {
            return $closure(isset($factory[0]) && isset($factory['fiber']) && $factory[0] instanceof \Closure ? $factory[0]() : $factory, $that);
        }, 'fiber' => isset($factory['fiber']) ? $factory['fiber'] : 0);
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

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function &offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }
}