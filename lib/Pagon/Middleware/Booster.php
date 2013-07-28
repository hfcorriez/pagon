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
        $app->share('cryptor', function ($app) {
            if (empty($app->crypt)) {
                throw new \RuntimeException('Cryptor booster config["crypt"]');
            }
            return new Cryptor($app->crypt);
        });

        // Share the logger for the app
        $app->share('logger', function ($app) {
            if (empty($app->log)) {
                throw new \RuntimeException('Logger booster need config["log"]');
            }
            return Logger::dispense('log');
        });

        $this->next();
    }
}