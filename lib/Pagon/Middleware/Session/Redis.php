<?php

namespace Pagon\Middleware\Session;

use Pagon\Middleware\Session;

class Redis extends Session
{
    protected $options = array(
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
        $this->redis = new \Redis();
        $this->redis->connect($this->options['host'], $this->options['port'], $this->options['timeout']);

        return true;
    }

    public function close()
    {
        $this->redis->close();
        return true;
    }

    public function read($id)
    {
        return $this->redis->get(strtr($this->options['name'], array(':id' => $id)));
    }

    public function write($id, $data)
    {
        if ($this->lifetime) {
            return $this->redis->setex(strtr($this->options['name'], array(':id' => $id)), $this->lifetime, $data);
        } else {
            return $this->redis->set(strtr($this->options['name'], array(':id' => $id)), $data);
        }
    }

    public function destroy($id)
    {
        return $this->redis->del(strtr($this->options['name'], array(':id' => $id)));
    }

    public function gc($lifetime)
    {
        return true;
    }
}
