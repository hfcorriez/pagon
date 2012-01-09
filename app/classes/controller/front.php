<?php

namespace Controller;

class Front extends \OMniApp\Controller
{
    protected $params;
    
    public function __construct($params)
    {
        $this->params = $params;
    }
    
    public function before()
    {
        
    }
    
    public function after()
    {
        echo $this->body;
    }
}