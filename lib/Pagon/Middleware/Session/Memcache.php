<?php

namespace Pagon\Middleware\Session;

use Pagon\Middleware\Session;

class Memcache extends Session
{
    protected $options = array(
        'host'    => 'localhost',
        'port'    => '11211',
        'timeout' => 1,
        'name'    => 'session/:id'
    );

    /**
     * @var \Memcache
     */
    protected $memcache;

    /*--------------------
    * Session Handlers
    ---------------------*/

    public function open($path, $name)
    {
        $this->memcache = new \Memcache();
        $this->memcache->connect($this->options['host'], $this->options['port'], $this->options['timeout']);

        return true;
    }

    public function close()
    {
        $this->memcache->close();
        return true;
    }

    public function read($id)
    {
        return $this->memcache->get(strtr($this->options['name'], array(':id' => $id)));
    }

    public function write($id, $data)
    {
        return $this->memcache->set(strtr($this->options['name'], array(':id' => $id)), $data, $this->lifetime);
    }

    public function destroy($id)
    {
        return $this->memcache->delete(strtr($this->options['name'], array(':id' => $id)));
    }

    public function gc($lifetime)
    {
        return true;
    }
}
