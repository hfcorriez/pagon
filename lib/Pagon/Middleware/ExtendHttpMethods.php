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
                    call_user_func_array(array($app->router, 'set'), func_get_args());
                } else {
                    $app->router->set($path, $route);
                }
            });

            // Register method check
            $input = $this->input;

            $this->input->protect('is' . ucfirst($method), function () use ($input, $method) {
                return $input->is($method);
            });
        }

        $this->next();
    }
}
