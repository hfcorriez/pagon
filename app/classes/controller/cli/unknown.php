<?php

namespace Controller\Cli;

class Unknown extends \Controller\Cli
{
    public function run()
    {
        $this->_body = 'Please use ' . $GLOBALS['argv'][0] . ' help.';
    }
}