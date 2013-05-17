<?php

namespace Pagon\Cache;

class Memcache
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
        $this->options = $options + $this->options;

        if (!extension_loaded('memcache')) {
            throw new \RuntimeException('Memcache extension is not install!');
        }

        $this->cache = new \Memcache();
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
        return $this->cache->set($key, $value, $timeout);
    }

    /**
     * Delete key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->cache->delete($key);
    }

    /**
     * Exists the key?
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return !!$this->cache->get($key);
    }
}
