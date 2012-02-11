<?php

namespace Controller\Front;

class Index extends \Controller\Front
{
    public function get()
    {
        $this->_body = \Omni\View::factory('index')->set(array('version' => \Omni\VERSION));
    }
}