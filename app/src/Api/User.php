<?php

namespace Api;

class User extends Api
{
    /**
     * @var \Model\User
     */
    public $user;

    /**
     * GET /
     */
    public function get()
    {
        $id = $this->params['id'];

        $post = \Model\User::dispense()->find_one($id);

        if (!$post) {
            $this->error('Unknown User');
        }

        $this->data = $post->as_array();
    }
}