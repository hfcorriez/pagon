<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class HttpBasicAuth extends Middleware
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
        // Inject the error type
        $this->app->errors['401'] = array(401, 'Authorization Required!');

        // Try to get username and password
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

        // Check username and password
        if (empty($username) || empty($password) || !$this->input->session($this->options['session_name'])) {
            // Set session
            $this->input->session($this->options['session_name'], 1);

            // Set forbidden
            $this->output->header("WWW-Authenticate", "Basic realm=\"{$this->options['realm']}\"");
            $this->app->handleError(401);
        } else {
            if ($this->options['callback']
                && call_user_func($this->options['callback'], $username, $password)
                || $this->options['user'] == $username && $this->options['pass'] == $password
            ) {
                // If auth pass
                $this->next();
            } else {
                // Else redirect to current url
                $this->input->session($this->options['session_name'], 0);
                $this->output->header("Location", $this->input->url())->end();
            }
        }
    }
}