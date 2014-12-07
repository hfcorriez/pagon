<?php

if (empty($app)) {
    require dirname(__DIR__) . '/app/bootstrap.php';
}

/**
 * Config route
 */
$app->get('/', 'Web\\Index');
$app->get('/api/users/:id', 'Api\\User');

// Start application
$app->run();