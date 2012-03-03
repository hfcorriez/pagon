<?php

error_reporting(E_ALL & ~E_NOTICE);
require __DIR__ . '/classes/omni/runtime.php';

define('APPPATH', __DIR__);

\Omni\App::init(array(
    'apppath' => APPPATH,
    'classpath' => APPPATH . '/classes',
    'viewpath' => APPPATH . '/views',

    'route' => array(
        '404' => '\Controller\Cli\Unknown',
        'error' => '\Controller\Cli\Unknown',
    	'exception' => '\Controller\Cli\Unknown',
    
        '' => '\Controller\Cli\Help',
    	'debug' => function(){
            print_r(func_get_args());
        },
    ),
    
    'log' => array(
        'level' => 0,
        'dir' => APPPATH . '/logs',
    ),
));

\Omni\App::run();