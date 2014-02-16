<?php

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(APP_DIR));

require dirname(__DIR__) . '/vendor/autoload.php';

use Pagon\Pagon;

// Create application
$app = Pagon::create(__DIR__ . '/config/default.php');

// Get current mode
$mode = $app->mode();

// Mount current dir as root dir
$app->mount(APP_DIR);
$app->mount('public', ROOT_DIR . '/public');

// Load mode config depends on ENV
if (is_file($conf_file = __DIR__ . '/config/' . $mode . '.php')) {
    $app->append(include($conf_file));
}

return $app;