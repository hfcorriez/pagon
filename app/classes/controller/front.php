<?php

namespace Controller;

class Front extends \Omni\Controller
{
    protected $body;

    public function __construct()
    {
        //doing something on init
    }
    
    public function before()
    {
        
    }
    
    public function after()
    {
        echo \Omni\App::$response->sendHeaders()->body($this->body);
    }
}