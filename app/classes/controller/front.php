<?php

namespace Controller;

class Front extends \Omni\Controller
{
    /**
     * @var \Omni\View
     */
    protected $_body;
    /**
     * @var \Omni\Request
     */
    protected $_request;
    /**
     * @var \Omni\Response
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
        $this->_request = \Omni\Request::instance();
        $this->_response = \Omni\Response::instance();
    }
    
    public function before()
    {
        //doing something after run
    }
    
    public function after()
    {
        echo $this->_response->sendHeaders()->body($this->_body);
    }
}