<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class BasicAuth extends Middleware
{
    protected $options = array(
        // Username and Password to authorize
        'user'         => null,
        'pass'         => null,

        // If give callback function, will use return value as authorize result
        'callback'     => null,

        //  Title of Basic Auth
        'realm'        => 'BasicAuth Login',

        // Authorized session key
        'session_name' => '_auth'
    );

    public function call()
    {
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

        if (!isset($username) || !isset($password) || !$this->input->session($this->options['session_name'])) {
            $this->input->session($this->options['session_name'], 1);

            $this->output->status(401);
            $this->output->header("WWW-Authenticate", "Basic realm=\"{$this->options['realm']}\"");
            $this->output->end('
<!DOCTYPE html>
<html>
<head>
<title>Authorization Required!</title>
<body><h1>401 Authorization Required!</h1></body>
</head>
</html>');
        } else {
            if ($this->options['callback'] && call_user_func($this->options['callback'], $username, $password) ||
                $this->options['user'] == $username && $this->options['pass'] == $password
            ) {
                $this->next();
            } else {
                $this->input->session($this->options['session_name'], 0);
                header("Location: " . $_SERVER['PHP_SELF']);
            }
        }
    }
}