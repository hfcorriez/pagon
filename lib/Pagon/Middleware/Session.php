<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class Session extends Middleware
{
    protected $cookie = true;

    public function call()
    {
        $env = $this->input->env();

        if (get_called_class() == __CLASS__) {
            if (!session_id()) {
                session_start();
                $env['session'] = $_SESSION;
            }
        } else {
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

            session_start();
            session_regenerate_id(true);

            $env['sessions'] = $_SESSION;
        }

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