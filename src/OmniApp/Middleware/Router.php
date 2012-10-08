<?php

namespace OmniApp\Middleware;

use OmniApp\App;
use OmniApp\Event;
use OmniApp\Route;
use OmniApp\Exception\Stop;

class Router extends \OmniApp\Middleware
{
    /**
     * Call for the middleware
     *
     * @throws \Exception
     */
    public function call()
    {
        try {
            ob_start();
            if (!Route::dispatch()) {
                App::notFound();
            }
            App::stop();
        } catch (Stop $e) {
            App::$response->write(ob_get_clean());
        }
    }
}