<?php

namespace Pagon\Cache;

class Memcached
{
    public $cache;

    protected $options = array(array(
        'host'   => 'localhost',
        'port'   => '6379',
        'weight' => 0
    ));

    /**
     * Init cache
     *
     * @param array $options
     * @throws \RuntimeException
     */
    public function __construct(array $options = array())
    {
        if (isset($options[0]) && is_array($options[0])) {
            $this->options = $options + $this->options;
        } else {
            $this->options = array($options + $this->options[0]);
        }

        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not install!');
        }

        $this->cache = new \Memcached();
        foreach ($this->options as $server) {
            $this->cache->addServer($server['host'], $server['port'], $server['weight']);
        }
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
