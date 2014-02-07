<?php

namespace Web;

use Pagon\Route\Rest;

class Index extends Rest
{
    public function get()
    {
        $this->app->render('index.php');
    }

    public function post()
    {
        print_r($this->input->data);
    }
}