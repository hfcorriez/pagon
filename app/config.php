<?php

$config = array(
    'apppath' => APP_DIR,
    'classpath' => APP_DIR . DIRECTORY_SEPARATOR . 'classes',
    'viewpath' => APP_DIR . DIRECTORY_SEPARATOR . 'views',
    'lang' => array('zh-CN'),
);

$config['route'] = array(
    '404' => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',
	'exception' => '\Controller\Front\Page404',
	
    '' => '\Controller\Front\Index',
	'debug' => function(){
	    var_dump(func_get_args());
	    var_dump(\Omni\App::$env);
        var_dump(__('ddd'));
	 },
);

$config['event'] = array(
    'init' => array(
        function(){var_dump('before init');}
     ),
    'shutdown' => array(
        function(){var_dump('shutdown0');},
        function(){var_dump('shutdown1');},
        function(){var_dump('shutdown2');},
     ),
);

$config['log'] = array(
    'level' => \OMni\LOG_LEVEL_DEBUG,
    'dir' => APP_DIR . '/logs',
);

return $config;