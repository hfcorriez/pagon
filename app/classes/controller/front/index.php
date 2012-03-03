<?php

namespace Controller\Front;

class Index extends \Controller\Front
{
    public function get()
    {
        $this->_body = new \Omni\View('index');
        $this->_body->set(array('version' => \Omni\VERSION));

        $test = \Model\Test::load(1);
        $test->title = 'ddd';

        var_dump($test->isChanged());
    }
}