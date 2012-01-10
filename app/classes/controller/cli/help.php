<?php

namespace Controller\Cli;

class Help extends \Controller\Front
{
    public function run()
    {
        $this->body = 
'Welcome to omniapp:

debug	Test and debug.
help	Help list.

copyright (c) 2011 Omniapp framework.
';
    }
}