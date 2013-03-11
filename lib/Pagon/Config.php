<?php

namespace Pagon;

class Config extends \ArrayObject
{
    /**
     * @var array Config registers
     */
    protected static $registers = array(
        'mimes' => array('mimes.php', 'file'),
    );

    /**
     * Init config
     *
     * @param array|object $input
     */
    public function __construct($input)
    {
        parent::__construct($this->parse($input));
    }

    /**
     * Register a config
     *
     * @param string $name
     * @param string $path
     * @param string $type
     * @return void
     */
    public static function register($name, $path, $type = 'file')
    {
        static::$registers[$name] = array($path, $type);
    }

    /**
     * Load config by name
     */
    public static function load($name)
    {
        if (!isset(static::$registers[$name])) {
            throw new \InvalidArgumentException("Load config error with non-exists name \"$name\"");
        }

        // Check if config already exists?
        if (static::$registers[$name] instanceof Config) {
            return static::$registers[$name];
        }

        // Try to load
        list($path, $type) = static::$registers[$name];

        $class = __NAMESPACE__ . '\Config\\' . ucfirst(strtolower($type));
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Load config with error type \"$type\"");
        }

        // Use data dir
        if ($path{0} != '/') $path = __DIR__ . '/Config/data/' . $path;

        return static::$registers[$name] = new $class($path);
    }

    /**
     * Load from file
     *
     * @param string $file
     * @return Config
     * @throws \RuntimeException
     */
    protected function fromFile($file)
    {
        if (!is_file($file)) {
            throw new \RuntimeException("Config load error with non-exists file");
        }

        if (get_called_class() == __CLASS__) {
            return new self(include($file));
        } else {
            return new self(file_get_contents($file));
        }
    }

    /**
     * Parse the input, Config must implements this method
     *
     * @param $input
     * @return mixed
     */
    protected function parse($input)
    {
        return (array)$input;
    }

    /**
     * Get the key
     *
     * @param string $key
     * @param mixed  $default
     * @return array|Config
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) return $this->getArrayCopy();

        $tmp = $default;
        if (strpos($key, '.') !== false) {
            $ks = explode('.', $key);
            $tmp = $this;
            foreach ($ks as $k) {
                if (!isset($tmp[$k])) return $default;

                $tmp = & $tmp[$k];
            }
        } else {
            if (isset($this[$key])) {
                $tmp = & $this[$key];
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
            $config = & $this;
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
        if (!isset($this[$name])) {
            $this[$name] = null;
        }
        $ret = & $this[$name];
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
