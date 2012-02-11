<?php

$config = array(
    'apppath' => APPPATH,
    'classpath' => APPPATH . '/classes',
    'viewpath' => APPPATH . '/views',
    'lang' => array('zh-CN'),
    'langpath' => APPPATH . '/langs',
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

$config['module'] = array('Log', 'I18n');

$config['log'] = array(
    'level' => 0,
    'dir' => APPPATH . '/logs',
);

return $config;