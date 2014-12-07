<?php

namespace Web;

use Pagon\Route\Rest;

class Index extends Rest
{
    /**
     * Users for page rending
     *
     * @var \Model\User[]
     */
    public $users = array();

    /**
     * Get /
     */
    public function get()
    {
        // Load users
        //$users = \Model\User::dispense()->find_many();

        $this->app->render('index.php');
    }

    /**
     * POST /
     */
    public function post()
    {
        print_r($this->input->data);
    }
}