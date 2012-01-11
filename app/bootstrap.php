<?php

require dirname(__DIR__) . '/omni.php';

define('APP_DIR', __DIR__);

\OMni\App::init(include('config.php'));
\OMni\App::run();
     
\OMni\Event::on('shutdown', function(){var_dump('shutdown at end.');});