<?php

require __DIR__ . '/../framework.php';

define('APPPATH', __DIR__);

App::init(include('config/config.php'));
I18n::init();
Log::init();

Route::on('about', function()
{
    echo 'OmniApp is very simple framework';
});

Route::on('serverinfo', function()
{
    echo '<xmp>';
    print_r($_SERVER);
    Log::debug(var_export($_SERVER, true));
});

Route::on('twig', function(){
    Twig_Autoloader::register();
    $loader = new Twig_Loader_String();
    $twig = new Twig_Environment($loader);
    echo $twig->render('Hello {{ name }}!', array('name' => 'OmniApp'));
});

App::run();