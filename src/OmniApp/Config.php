<?php

namespace OmniApp;

class Config extends \ArrayObject
{
    public function __construct($input)
    {
        parent::__construct($this->parse($input));
    }

    /**
     * Get the key
     *
     * @param bool  $key
     * @param mixed $default
     * @return array|null|Config
     */
    public function get($key = false, $default = null)
    {
        if ($key === false) return $this->getArrayCopy();

        $tmp = $default;
        if (strpos($key, '.') !== false) {
            $ks = explode('.', $key);
            $tmp = $this;
            foreach ($ks as $k) {
                if (!array_key_exists($k, $tmp)) return $default;

                $tmp = &$tmp[$k];
            }
        } else {
            if (isset($this[$key])) {
                $tmp = &$this[$key];
            }
        }
        return $tmp;
    }

    /**
     * Set the key
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } elseif (strpos($key, '.') !== false) {
            $config = &$this;
            $namespaces = explode('.', $key);
            foreach ($namespaces as $namespace) {
                if (!isset($config[$namespace])) $config[$namespace] = array();
                $config = & $config[$namespace];
            }
            $config = $value;
        } else {
            $this[$key] = $value;
        }
    }

    /**
     * Parse the input
     *
     * @param $input
     * @return mixed
     */
    protected function parse($input) {
        return (array)$input;
    }

    /**
     * Set the key
     *
     * @param $name
     * @param $val
     */
    public function __set($name, $val)
    {
        $this[$name] = $val;
    }

    /**
     * Get the key
     *
     * @param $name
     * @return null
     */
    public function &__get($name)
    {
        if (!array_key_exists($name, $this)) {
            $this[$name] = null;
        }
        $ret = &$this[$name];
        return $ret;
    }

    /**
     * Isset the key?
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this[$name]);
    }

    /**
     * Unset the key
     *
     * @param $name
     */
    public function __unset($name)
    {
        unset($this[$name]);
    }
}
