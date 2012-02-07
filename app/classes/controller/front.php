<?php

namespace Controller;

class Front extends \Omni\Controller
{
    protected $params;
    
    public function __construct()
    {
        //todo something
    }
    
    public function before()
    {
        
    }
    
    public function after()
    {
        echo $this->body;
    }
}