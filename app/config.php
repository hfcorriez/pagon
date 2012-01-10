<?php

$config = array(
    'apppath' => APP_DIR,
    'classpath' => APP_DIR . DIRECTORY_SEPARATOR . 'classes',
    'viewpath' => APP_DIR . DIRECTORY_SEPARATOR . 'views',
);

$config['route'] = array(
    '404' => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',
	'exception' => '\Controller\Front\Page404',
	
    '' => '\Controller\Front\Index',
	'p' => function(){
	    echo '<xmp>';
	    var_dump(func_get_args());
	    var_dump(\Omni\App::$env);
        var_dump(__('ddd'));
	 },
);

$config['log'] = array(
    'level' => \OMni\App::LOG_LEVEL_DEBUG,
    'dir' => APP_DIR . '/logs',
);

return $config;