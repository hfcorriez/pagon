<?php

$config = array(
    'apppath' => APP_DIR,
    'classpath' => APP_DIR . '/classes',
    'viewpath' => APP_DIR . '/views',
    'lang' => array('zh-CN'),
    'error' => true,
);

$config['route'] = array(
    '404' => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',
	
    '' => '\Controller\Front\Index',
	'debug' => function(){
	    var_dump(func_get_args());
	    var_dump(\Omni\App::$env);
        var_dump(__('ddd'));
	 },
);

$config['log'] = array(
    'level' => \Omni\LOG_DEBUG,
    'dir' => APP_DIR . '/logs',
);

return $config;