<?php

require dirname(__DIR__) . '/omni.php';

define('APP_DIR', __DIR__);

\Omni\App::init(array(
    'apppath' => APP_DIR,
    'classpath' => APP_DIR . '/classes',
    'viewpath' => APP_DIR . '/views',

    'route' => array(
        '404' => '\Controller\Cli\Unknown',
        'error' => '\Controller\Cli\Unknown',
    	'exception' => '\Controller\Cli\Unknown',
    
        '' => '\Controller\Cli\Help',
    	'debug' => function(){
            print_r(func_get_args());
            print_r(\Omni\App::$env);
            print_r(__('ddd'));
        },
    ),
    
    'log' => array(
        'level' => \Omni\LOG_DEBUG,
        'dir' => APP_DIR . '/logs',
    ),
));
\Omni\App::run();