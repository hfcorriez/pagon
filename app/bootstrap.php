<?php

require dirname(__DIR__) . '/omni.php';

define('APP_DIR', __DIR__);

\Omni\Event::init(include('event.php'));
\Omni\Event::on(\Omni\EVENT_INIT, function(){var_dump('init.');});

\Omni\App::init(include('config.php'));
\Omni\App::run();

\Omni\Event::on(\Omni\EVENT_SHUTDOWN, function(){var_dump('shutdown at end.');});