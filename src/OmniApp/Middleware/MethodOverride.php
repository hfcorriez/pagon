<?php

namespace OmniApp\Middleware;

class MethodOverride extends \OmniApp\Middleware
{
    protected $options = array(
        'method' => '_METHOD'
    );

    public function call()
    {
        $env = $this->input->env();

        if (isset($env['X_HTTP_METHOD_OVERRIDE'])) {
            $env['REQUEST_METHOD'] = strtoupper($env['X_HTTP_METHOD_OVERRIDE']);
        } elseif (isset($env['REQUEST_METHOD']) && $env['REQUEST_METHOD'] === 'POST') {
            if ($method = $_POST[$this->options['method']]) {
                $env['original_request_method'] = $env['REQUEST_METHOD'];
                $env['REQUEST_METHOD'] = strtoupper($method);
            }
        }

        $this->next();
    }

}
