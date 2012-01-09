<?php

require dirname(__DIR__) . '/omniapp.php';

define('APP_DIR', __DIR__);

\OMniApp\Core::init(include('config.php'));
\OMniApp\Core::start();