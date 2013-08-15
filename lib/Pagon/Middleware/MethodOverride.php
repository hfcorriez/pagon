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
        if ($override = $this->input->get('X_HTTP_METHOD_OVERRIDE')) {
            $this->input->set('REQUEST_METHOD', strtoupper($override));
        } elseif ($this->input->get('REQUEST_METHOD') === 'POST'
            && isset($_POST[$this->injectors['method']])
            && ($method = $_POST[$this->injectors['method']])
        ) {
            $this->input->set('ORIGIN_REQUEST_METHOD', 'POST');
            $this->input->set('REQUEST_METHOD', strtoupper($method));
        }

        $this->next();
    }

}
