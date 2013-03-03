<?php

namespace Pagon\Middleware\Session;

use Pagon\Middleware\Session;

class Cookie extends Session
{
    protected $cookie = false;

    protected $options = array(
        'name' => 'session'
    );

    /*--------------------
    * Session Handlers
    ---------------------*/

    public function open($path, $name)
    {
        // Save session data to cookie before header send
        $this->output->on('header', function () {
            session_write_close();
        });
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return $this->input->cookie($this->options['name']);
    }

    public function write($id, $data)
    {
        $this->output->cookie($this->options['name'], $data, array('sign' => true, 'expires' => $this->lifetime));
        return true;
    }

    public function destroy($id)
    {
        $this->output->cookie($this->options['name'], '');
        return true;
    }

    public function gc($lifetime)
    {
        return true;
    }
}
