<?php

namespace OmniApp\Middleware;

use OmniApp\App;

class RunTime extends \OmniApp\Middleware
{
    public function call()
    {
        // Call the app
        App::call();
    }
}