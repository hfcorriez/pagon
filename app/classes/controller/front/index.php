<?php

namespace Controller\Front;

class Index extends \Controller\Front
{
    public function get()
    {
        trigger_error('dd', E_USER_WARNING);
        $this->body = 'Welcome to omniapp';
    }
}