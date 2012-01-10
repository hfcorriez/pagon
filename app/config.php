<?php

$config = array();

$config['app'] = array(
    'dir' => APP_DIR,
    'classpath' => APP_DIR . DIRECTORY_SEPARATOR . 'classes',
    'viewpath' => APP_DIR . DIRECTORY_SEPARATOR . 'views',
);

$config['route'] = array(
    '404' => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',
	'exception' => '\Controller\Front\Page404',
	
    '' => '\Controller\Front\Index',
	'p' => function($params){var_dump($params);},
);

$config['log'] = array(
    'level' => \OMniApp\Core::LOG_LEVEL_DEBUG,
    'dir' => APP_DIR . '/logs',
);

return $config;