<?php

namespace Pagon;

class Config extends \ArrayObject
{
    const LOAD_AUTODETECT = 0;

    /**
     * @var array Config registers
     */
    protected static $registers = array(
        'mimes' => array('mimes.php', 'file'),
    );

    /**
     * Init config
     *
     * @param array $input
     * @return Config
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * Register a config
     *
     * @param string $name
     * @param string $path
     * @param string $type
     * @return void
     */
    public static function import($name, $path, $type = 'file')
    {
        static::$registers[$name] = array($path, $type);
    }

    /**
     * Load config by name
     */
    public static function export($name)
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
     * @param string     $file
     * @param int|string $type
     * @throws \InvalidArgumentException
     * @return Config
     */
    public static function load($file, $type = self::LOAD_AUTODETECT)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("Config load error with non-exists file");
        }

        if ($type === self::LOAD_AUTODETECT) {
            $type = pathinfo($file, PATHINFO_EXTENSION);
        }
        $type = strtolower($type);

        if ($type !== 'php') {
            // Try to use custom parser
            if (class_exists($type)) {
                $class = $type;
            } else {
                $class = __NAMESPACE__ . "\\Parser\\" . ucfirst($type);
            }

            if (!class_exists($class)) {
                throw new \InvalidArgumentException("There is no parser '$class' for '$type'");
            }

            return new self($class::parse(file_get_contents($file)));
        } else {
            return new self(include($file));
        }
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
     * Set defaults for array
     *
     * @param array $config
     * @return Config
     */
    public function defaults(array $config)
    {
        $this->exchangeArray($this->getArrayCopy() + $config);
        return $this;
    }

    /**
     * Add array for config
     *
     * @param array $config
     * @return Config
     */
    public function add(array $config)
    {
        $this->exchangeArray($config + $this->getArrayCopy());
        return $this;
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
