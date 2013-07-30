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

        // Set encoding
        iconv_set_encoding("internal_encoding", $app->charset);
        mb_internal_encoding($app->charset);

        // Configure timezone
        date_default_timezone_set($app->timezone);

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