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
    public function &__get($key)
    {
        if (!isset($this->injectors[$key])) throw new \InvalidArgumentException(sprintf('Can not get non-exists injector "%s::%s"', get_called_class(), $key));

        if ($this->injectors[$key] instanceof \Closure) {
            $tmp = $this->injectors[$key]();
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
            throw new \InvalidArgumentException(sprintf('Injector "%s::%s" is not defined.', get_called_class(), $key));
        }

        $factory = $this->injectors[$key];

        if (!($factory instanceof \Closure)) {
            throw new \InvalidArgumentException(sprintf('Injector "%s::%s" does not contain an object definition.', get_called_class(), $key));
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

        throw new \BadMethodCallException(sprintf('Call to undefined protect injector "%s::%s()', get_called_class(), $method));
    }

    /**
     * Get or set injector
     *
     * @param string $key
     * @param mixed  $value
     * @return bool|mixed
     */
    public function raw($key = null, $value = null)
    {
        if ($key === null) {
            return $this->injectors;
        } elseif ($value !== null) {
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

    /**
     * Append the multiple injectors
     *
     * @param array $injectors
     */
    public function append(array $injectors)
    {
        $this->injectors = $injectors + $this->injectors;
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
     * @throws \InvalidArgumentException
     * @return mixed Can return all value types.
     */
    public function &offsetGet($offset)
    {
        if (!isset($this->injectors[$offset])) throw new \InvalidArgumentException(sprintf('Can not get non-exists injector "%s::%s"', get_called_class(), $offset));

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