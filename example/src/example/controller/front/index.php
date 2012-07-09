<?php

namespace Example\Controller\Front;

class Index extends \Example\Controller\Front
{
    public function get()
    {
        $this->_body = new \View('index');
        $this->_body->set(array('version' => \VERSION));
    }
}