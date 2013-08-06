<?php

namespace Pagon\Cache;

use Pagon\Cache;

class Redis extends Cache
{
    public $cache;

    protected $options = array(
        'host'    => 'localhost',
        'port'    => '6379',
        'timeout' => 1
    );

    /**
     * Init cache
     *
     * @param array $options
     * @throws \RuntimeException
     */
    public function __construct(array $options = array())
    {
        if (!class_exists('\Redis')) {
            throw new \RuntimeException("Use Cache\Redis need redis extension installed.");
        }

        $this->options = $options + $this->options;

        $this->cache = new \Redis();
        $this->cache->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
    }

    /**
     * Get value
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }

    /**
     * Set value
     *
     * @param string $key
     * @param string $value
     * @param int    $timeout
     * @return bool
     */
    public function set($key, $value, $timeout = 0)
    {
        if ($timeout) {
            return $this->cache->setex($key, $timeout, $value);
        } else {
            return $this->cache->set($key, $value);
        }
    }

    /**
     * Delete key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->cache->del($key);
    }

    /**
     * Exists the key?
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->cache->exists($key);
    }
}
