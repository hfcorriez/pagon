<?php

require dirname(__DIR__) . '/omni.php';

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
            print_r(\Omni\App::$env);
            print_r(__('ddd'));
        },
    ),
    
    'log' => array(
        'level' => 0,
        'dir' => APPPATH . '/logs',
    ),
));

\Omni\App::run();