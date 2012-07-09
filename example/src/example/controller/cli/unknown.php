<?php

namespace Example\Controller\Cli;

class Unknown extends \Example\Controller\Cli
{
    public function run()
    {
        $this->_body = 'Please use ' . $GLOBALS['argv'][0] . ' help.';
    }
}