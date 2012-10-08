<?php

namespace OmniApp\Middleware;

use OmniApp\App;
use OmniApp\Event;
use OmniApp\Route;
use OmniApp\Middleware;
use OmniApp\Exception\Stop;

class Router extends Middleware
{
    /**
     * Call for the middleware
     *
     * @throws \Exception
     */
    public function call()
    {
        ob_start();
        try {
            if (!Route::dispatch()) {
                App::notFound();
            }
        } catch (Stop $e) {
        }
        App::$response->write(ob_get_clean(), -1);
    }
}