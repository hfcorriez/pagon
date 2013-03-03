<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class Session extends Middleware
{
    protected $cookie = true;
    protected $lifetime;

    public function call()
    {
        $env = $this->input->env();

        $this->lifetime = ini_get('session.gc_maxlifetime');

        if (get_called_class() !== __CLASS__) {
            if (!$this->cookie) {
                ini_set('session.use_cookies', 0);
                session_cache_limiter(false);
            }

            session_set_save_handler(array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc'));
        }

        if (!session_id()) {
            session_start();
        }

        $env['sessions'] = $_SESSION;

        $this->next();
    }

    /*--------------------
    * Session Handlers
    ---------------------*/

    public function open($path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return '';
    }

    public function write($id, $data)
    {
        return true;
    }

    public function destroy($id)
    {
        return true;
    }

    public function gc($lifetime)
    {
        return true;
    }
}