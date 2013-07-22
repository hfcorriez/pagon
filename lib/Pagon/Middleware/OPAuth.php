<?php

namespace Pagon\Middleware;

use Pagon\Middleware;
use Opauth as OPAuthService;

class OPAuth extends Middleware
{
    // Some options
    protected $options = array(
        'login_url'          => '/login',
        'callback_url'       => '/login/callback',
        'callback_transport' => 'post'
    );

    /**
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['callback'])) {
            throw new \InvalidArgumentException('OPAuth middleware need "callback" option');
        }

        parent::__construct($options);

        $this->options['path'] = $this->options['login_url'] . '/';
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
                $failureReason = $response['error'];
            } else {
                if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid'])) {
                    $failureReason = 'Missing key auth response components';
                } elseif (!$opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $failureReason)) {
                }
            }

            $req->auth = $response;
            $req->auth_success = !$failureReason;
            $req->auth_error_message = $failureReason;
            $next();
        };

        $init = function ($req, $res, $next) use ($options) {
            new OPAuthService($options);
        };

        $app->post($options['callback_url'], $callback, $options['callback']);
        $app->get($options['login_url'] . '/:strategy', $init);
        $app->all($options['login_url'] . '/:strategy/:return', $init);

        $this->next();
    }
}
