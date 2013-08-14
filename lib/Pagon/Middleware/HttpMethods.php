<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class HttpMethods extends Middleware
{
    protected $injectors = array(
        'methods' => array(
            'head',
            'options',
            'trace',
            'copy',
            'lock',
            'mkcol',
            'move',
            'propfind',
            'proppatch',
            'unlock',
            'report',
            'mkactivity',
            'checkout',
            'merge',
            'm-search',
            'notify',
            'subscribe',
            'unsubscribe',
            'patch'
        )
    );

    public function call()
    {
        foreach ($this->injectors['methods'] as $method) {
            $app = $this->app;

            // Register route
            $this->app->protect($method, function ($path, $route, $more = null) use ($app, $method) {
                if ($more !== null) {
                    call_user_func_array(array($app->router, 'set'), func_get_args())->via(strtoupper($method));
                } else {
                    $app->router->set($path, $route)->via(strtoupper($method));
                }
            });
        }

        $this->next();
    }
}
