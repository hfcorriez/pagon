<?php

namespace Pagon\Middleware\Session;

use Pagon\Middleware\Session;

class Redis extends Session
{
    protected $injectors = array(
        'host'    => 'localhost',
        'port'    => '6379',
        'timeout' => 1,
        'name'    => 'session/:id'
    );

    /**
     * @var \Memcache
     */
    protected $redis;

    /*--------------------
    * Session Handlers
    ---------------------*/

    public function open($path, $name)
    {
        if (!class_exists('\Redis')) {
            throw new \RuntimeException("Use Session\Redis need redis extension installed.");
        }

        $this->redis = new \Redis();
        $this->redis->connect($this->injectors['host'], $this->injectors['port'], $this->injectors['timeout']);

        return true;
    }

    public function close()
    {
        $this->redis->close();
        return true;
    }

    public function read($id)
    {
        return $this->redis->get(strtr($this->injectors['name'], array(':id' => $id)));
    }

    public function write($id, $data)
    {
        if ($this->injectors['lifetime']) {
            return $this->redis->setex(strtr($this->injectors['name'], array(':id' => $id)), $this->injectors['lifetime'], $data);
        } else {
            return $this->redis->set(strtr($this->injectors['name'], array(':id' => $id)), $data);
        }
    }

    public function destroy($id)
    {
        return $this->redis->del(strtr($this->injectors['name'], array(':id' => $id)));
    }

    public function gc($lifetime)
    {
        return true;
    }
}
