<?php

$config = array();

$config['app'] = array(
    'dir' => APP_DIR,
    'classpath' => APP_DIR . DIRECTORY_SEPARATOR . 'classes',
    'viewpath' => APP_DIR . DIRECTORY_SEPARATOR . 'views',
);

$config['route'] = array(
    '' => '\Controller\Front\Index',
    '404' => '\Controller\Front\Page404',
);

$config['log'] = array(
    'level' => \OMniApp\Core::LOG_LEVEL_DEBUG,
    'dir' => APP_DIR . '/logs',
);

return $config;