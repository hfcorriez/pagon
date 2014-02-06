<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Pagon\App;

$app = App::create(__DIR__ . '/config/default.php');
$mode = $app->mode();

if (is_file($conf_file = __DIR__ . '/config/' . $mode . '.php')) {
    $app->append(include($conf_file));
}