<?php

$app = require_once __DIR__ . '/bootstrap.php';

use \Phpmig\Adapter,
    \Phpmig\Pimple\Pimple;

$container = new Pimple();

$container['pdo'] = $app->pdo;
$container['app'] = $app;

$container['phpmig.adapter'] = $container->share(function () use ($app) {
    return new Adapter\PDO\Sql($app->pdo, 'migrations');
});

$container['phpmig.migrations'] = function () {
    return glob(__DIR__ . DIRECTORY_SEPARATOR . 'migrations/*.php');
};

return $container;