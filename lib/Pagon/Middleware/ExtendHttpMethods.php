<?php

namespace Pagon\Middleware;

class ExtendHttpMethods extends \Pagon\Middleware
{
    protected $options = array(
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
        foreach ($this->options['methods'] as $method) {
            $app = $this->app;

            // Register route
            $this->app->protect($method, function ($path, $route, $more = null) use ($app, $method) {
                if ($app->isCli() || !$app->input->is($method)) return;

                if ($more !== null) {
                    call_user_func_array(array($app->router, 'on'), func_get_args());
                } else {
                    $app->router->on($path, $route);
                }
            });

            // Register method check
            $input = $this->input;
            $_method = 'is' . ucfirst($method);
            $this->input->protect($_method, function () use ($input, $method) {
                return $input->is($method);
            });
        }

        $this->next();
    }
}
