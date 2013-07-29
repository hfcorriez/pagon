<?php

namespace Pagon\Middleware;

use Pagon\Logger;
use Pagon\Middleware;
use Pagon\Utility\Cryptor;

class Booster extends Middleware
{
    public function call()
    {
        $app = $this->app;

        // configure timezone
        if (isset($app->timezone)) date_default_timezone_set($app->timezone);

        // configure debug
        if ($app->enabled('debug')) $app->add(new Middleware\PrettyException());

        // Share the cryptor for the app
        if (!empty($app->crypt)) {
            $app->cryptor = new Cryptor($app->crypt);
        }

        // Share the logger for the app
        if (!empty($app->log)) {
            $app->logger = Logger::dispense();
        }

        $this->next();
    }
}