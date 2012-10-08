<?php

namespace OmniApp;

class Registry
{
    protected $registry;

    /**
     * Add registry
     *
     * @param string         $key
     * @param mixed|\Closure $value
     */
    public function __set($key, $value)
    {
        $this->registry[$key] = $value;
    }

    /**
     * Get registry
     *
     * @param string $key
     * @return null
     */
    public function __get($key)
    {
        return isset($this->registry[$key]) ? $this->registry[$key] : null;
    }

    /**
     * Exists registry?
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->registry[$key]);
    }

    /**
     * Delete registry?
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->registry[$key]);
    }

    /**
     * Call closure registry
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (isset($this->registry[$method]) && $this->registry[$method] instanceof \Closure) {
            return call_user_func_array($this->registry[$method], $args);
        }
        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
    }
}
