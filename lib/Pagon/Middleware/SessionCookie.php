<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class SessionCookie extends Middleware
{
    protected $options = array(
        'name' => 'sessions'
    );

    public function __construct(array $options = array())
    {
        parent::__construct($options);

        ini_set('session.use_cookies', 0);
        session_cache_limiter(false);
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    public function call($option = array())
    {
        if (session_id() === '') {
            session_start();
        }

        $req = $this->input->env();
        $req['sessions'] = $_SESSION;
        // Check session through cookies header
        if ($_sessions = (array)$this->input->cookie($this->options['name'])) {
            $_SESSION = $_sessions;
        }

        $_output = & $this->output;
        $_options = & $this->options;
        // Use event emitter to send cookies as session
        $this->output->on('header', function () use ($_output, $_options, $_sessions) {
            // Check if session if same with old sessions
            if ($_SESSION != $_sessions) {
                // Write cookie
                $_output->cookie($_options['name'], $_SESSION, array('sign' => true));
                session_destroy();
            }
        });

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