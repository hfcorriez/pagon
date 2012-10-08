<?php

namespace OmniApp;

class Registry
{
    protected $registry;

    public function __set($key, $value)
    {
        $this->registry[$key] = $value;
    }

    public function __get($key)
    {
        return isset($this->registry[$key]) ? $this->registry[$key] : null;
    }

    public function __isset($key)
    {
        return isset($this->registry[$key]);
    }

    public function __unset($key)
    {
        unset($this->registry[$key]);
    }

    public function __call($method, $args)
    {
        if (isset($this->registry[$method]) && $this->registry[$method] instanceof \Closure) {
            return call_user_func_array($this->registry[$method], $args);
        }
        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
    }
}
