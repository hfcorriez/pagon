<?php

namespace Pagon;

abstract class Cache
{
    /**
     * @var string The prefix for cache config
     */
    public static $prefix = 'cache.';

    /**
     * @var array Instances has dispensed
     */
    protected static $instances = array();

    /**
     * Factory the cache
     *
     * @param string $type
     * @param array  $config
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function factory($type, $config = array())
    {
        $class = __NAMESPACE__ . '\\Cache\\' . ucfirst(strtolower($type));

        if (!class_exists($class) && !class_exists($class = $type)) {
            throw new \InvalidArgumentException('Can not find given "' . $type . '" cache adapter');
        }

        return new $class($config);
    }

    /**
     * Dispense the instance
     *
     * @param string $name
     * @return Cache
     * @throws \InvalidArgumentException
     */
    public static function dispense($name = 'default')
    {
        if (empty(self::$instances[$name])) {
            $app = App::self();
            if (!$config = $app->get(self::$prefix . $name)) {
                throw new \InvalidArgumentException('Can not find "' . self::$prefix . $name . '" config');
            }

            self::$instances[$name] = self::factory($config['type'], $config);
        }

        return self::$instances[$name];
    }

    /**
     * Get cache
     *
     * @param string $key
     * @return mixed
     */
    abstract public function get($key);

    /**
     * Set cache
     *
     * @param string       $key
     * @param array|string $value
     * @param int          $timeout
     * @return mixed
     */
    abstract public function set($key, $value, $timeout = 0);

    /**
     * Exists the key?
     *
     * @param string $key
     * @return mixed
     */
    abstract public function exists($key);

    /**
     * Delete the key?
     *
     * @param string $key
     * @return mixed
     */
    abstract public function delete($key);
}