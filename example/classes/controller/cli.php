<?php

namespace Controller;

class Cli extends \Controller
{
    protected $_body;

    public function __construct()
    {
        //todo something
    }

    public function before()
    {
        //do something before run
    }

    public function run()
    {
        echo 'dd';
    }

    public function after()
    {
        echo $this->_body;
    }
}