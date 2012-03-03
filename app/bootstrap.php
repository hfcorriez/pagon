<?php

require __DIR__ . '/classes/omni/runtime.php';

define('APPPATH', __DIR__);

\Omni\Event::on(\Omni\EVENT_INIT, function(){/*init event*/});

\Omni\App::init(include('config/config.php'));

\Omni\Event::on(\Omni\EVENT_SHUTDOWN, function(){/*shutdown event*/});
\Omni\App::run();
