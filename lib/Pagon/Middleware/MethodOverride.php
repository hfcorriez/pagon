<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class MethodOverride extends Middleware
{
    protected $injectors = array(
        'method' => '_METHOD'
    );

    public function call()
    {
        if ($override = $this->input->raw('X_HTTP_METHOD_OVERRIDE')) {
            $this->input->raw('REQUEST_METHOD', strtoupper($override));
        } elseif ($this->input->raw('REQUEST_METHOD') === 'POST'
            && isset($_POST[$this->injectors['method']])
            && ($method = $_POST[$this->injectors['method']])
        ) {
            $this->input->raw('ORIGIN_REQUEST_METHOD', 'POST');
            $this->input->raw('REQUEST_METHOD', strtoupper($method));
        }

        $this->next();
    }

}
