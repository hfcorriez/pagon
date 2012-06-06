<?php

require __DIR__ . '/../framework.php';

define('APPPATH', __DIR__);

App::init(include('config/config.php'));
I18n::init();

Route::on('about', function()
{
    echo 'OmniApp is very simple framework';
});

Route::on('serverinfo', function()
{
    echo '<xmp>';
    print_r($_SERVER);
});

App::run();