<?php

namespace OmniApp\Controller;

use OmniApp\Http\Request;

class Web extends \OmniApp\Controller
{

    /**
     * before run
     */
    function before()
    {
    }

    /**
     * after run
     */
    function after()
    {
    }

    /**
     * Run
     *
     * @return mixed
     */
    function run()
    {
        $method = Request::method();

        if (method_exists($this, strtolower($method))) {
            $this->{$method}();
        } elseif (method_exists($this, 'all')) {
            $this->{'all'}();
        }
    }
}