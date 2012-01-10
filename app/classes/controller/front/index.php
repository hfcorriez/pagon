<?php

namespace Controller\Front;

class Index extends \Controller\Front
{
    public function get()
    {
        $this->body = 'Welcome to omniapp';
    }
}