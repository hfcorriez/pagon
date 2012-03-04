<?php

require __DIR__ . '/classes/omni/runtime.php';

define('APPPATH', __DIR__);

\Omni\App::init(include('config/config.php'));

\Omni\Route::on('about', function()
{
    echo 'OmniApp is very simple framework';
});

\Omni\Route::on('serverinfo', function()
{
    echo '<xmp>';
    print_r($_SERVER);
});

\Omni\App::run();