<?php

namespace Controller;

class Cli extends \Omni\Controller
{
    protected $_body;
    
    public function __construct()
    {
        //todo something
    }
    
    public function before()
    {
        
    }
    
    public function after()
    {
        echo $this->_body;
    }
}