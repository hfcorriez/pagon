<?php

if (empty($app)) {
    require dirname(__DIR__) . '/app/bootstrap.php';
}

// Start auto route
$app->autoRoute('Web');

$app->run();