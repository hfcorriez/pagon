<?php

namespace Pagon\Middleware;

use Pagon\Logger;
use Pagon\Middleware;
use Pagon\Utility\Cryptor;

class Booster extends Middleware
{
    /**
     * @var array Default options
     */
    protected $injectors = array(
        'logger'  => 'log',
        'cryptor' => 'crypt'
    );

    public function call()
    {
        $app = $this->app;

        // Set encoding
        iconv_set_encoding("internal_encoding", $app->charset);
        mb_internal_encoding($app->charset);

        // Configure timezone
        date_default_timezone_set($app->timezone);

        // Share the cryptor for the app
        if ($_crypt = $app->get($this->injectors['cryptor'])) {
            $app->cryptor = $app->share(function () use ($_crypt) {
                new Cryptor($_crypt);
            });
        }

        // Share the logger for the app
        if ($_log = $app->get($this->injectors['logger'])) {
            $app->logger = $app->share(function () use ($_log) {
                Logger::$default = new Logger($_log);
            });
        }

        // Configure debug
        if ($app->debug) {
            self::graft('PrettyException');
        } else {
            $this->next();
        }
    }
}