<?php
/**
 *
 * This file is used to be define the config which cause different by environment, such as database, redis, memcache, api service and so on.
 *
 * This `develop` config will be load when `PAGON_ENV` environment variable is not set or set to `develop`.
 *
 */


return array(
    'debug'    => true,
    'database' => array(
        'type'     => 'mysql',
        'host'     => 'localhost',
        'port'     => '3306',
        'dbname'   => 'test',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8',
        'options'  => array()
    )
);