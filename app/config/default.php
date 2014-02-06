<?php
/**
 *
 * This file is used to be define the config in global
 *
 * This `default` config will be load anyway. This config "key" will be overwrite when other config has the same "key".
 *
 */

return array(
    'timezone' => 'Asia/Shanghai',
    'views'    => dirname(__DIR__) . '/views',
    'autoload' => dirname(__DIR__) . '/src',
    'cookie'   => array(
        'path'     => '/',
        'domain'   => null,
        'secure'   => false,
        'httponly' => false,
        'timeout'  => 3600,
        'sign'     => false,
        'secret'   => 'cooke secret',
        'encrypt'  => false,
    ),
    'crypt'    => array(
        'key' => 'crypt key'
    )
);