<?php

require dirname(__DIR__) . '/omni.php';

define('APP_DIR', __DIR__);
\OMni\Event::on(\Omni\EVENT_INIT, function(){var_dump('init.');});

\OMni\App::init(include('config.php'));
\OMni\App::run();

\OMni\Event::on(\Omni\EVENT_SHUTDOWN, function(){var_dump('shutdown at end.');});