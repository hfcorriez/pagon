<?php

namespace Controller;

class Front extends \Controller
{
    /**
     * @var \View
     */
    protected $_body;
    /**
     * @var \Request
     */
    protected $_request;
    /**
     * @var \Response
     */
    protected $_response;

    public function __construct()
    {
        //doing something before run
    }

    public function run()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $this->{$method}();
    }

    public function before()
    {
        //doing something after run
    }

    public function after()
    {
        echo $this->_body;
    }
}