<?php

namespace Pagon\Middleware;

use Pagon\Middleware;
use Opauth as OPAuthService;

class OPAuth extends Middleware
{
    // Some options
    protected $options = array(
        'login'              => '/login',
        'callback'           => '/login/callback',
        'callback_transport' => 'post'
    );

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->options['path'] = $this->options['login'] . '/';
        $this->options['callback_url'] = $this->options['callback'];
    }

    /**
     * Call
     *
     * @return bool|void
     */
    public function call()
    {
        $options = $this->options;
        $app = $this->app;
        $that = $this;

        $callback = function ($req, $res, $next) use ($that, $app, $options) {
            $opauth = new OPAuthService($options, false);

            $response = unserialize(base64_decode($app->input->data('opauth')));

            $failureReason = null;

            if (isset($response['error'])) {
                $that->emit('error', $response['error'], $response);
            } else {
                if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid'])) {
                    $that->emit('error', 'Missing key auth response components', $response);
                } elseif (!$opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $failureReason)) {
                    $that->emit('error', $failureReason, $response);
                } else {
                    $that->emit('success', $response);
                }
            }

            $req->opauth = $response;
            $next();
        };

        $init = function ($req, $res, $next) use ($options) {
            new OPAuthService($options);
        };

        $app->post($options['callback'], $callback, $options['route']);
        $app->get($options['login'] . '/:strategy', $init);
        $app->all($options['login'] . '/:strategy/:return', $init);

        $this->next();
    }
}
