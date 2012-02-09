<?php

require dirname(__DIR__) . '/omni.php';

define('APPPATH', __DIR__);

//\Omni\Event::init(include('event.php'));
\Omni\Event::on(\Omni\EVENT_INIT, function(){/*init event*/});

\Omni\App::init(include('config.php'));
\Omni\Module::load(array('Log', 'I18n'));

\Omni\Event::on(\Omni\EVENT_SHUTDOWN, function(){/*shutdown event*/});
\Omni\App::run();
