<?php

namespace Controller\Cli;

class Unknown extends \Controller\Cli
{
    public function run()
    {
        $this->_body =
'
Please use ' . \Omni\App::$env->_ . ' help.
';
    }
}