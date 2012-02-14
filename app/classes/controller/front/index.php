<?php

namespace Controller\Front;

class Index extends \Controller\Front
{
    public function get()
    {
        $this->_body = new \Omni\View('index');
        $this->_body->set(array('version' => \Omni\VERSION));
        throw new \Omni\Exception('dd');
    }
}