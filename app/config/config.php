<?php

$config = array();

$config['app'] = array(
    'apppath' => APPPATH,
    'classpath' => APPPATH . '/classes',
    'viewpath' => APPPATH . '/views',
    'error' => true,
);

$config['route'] = array(
    '404' => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',

    '' => '\Controller\Front\Index',
    'debug' => function()
    {
        var_dump(func_get_args());
        var_dump(__('ddd'));
    },
);

$config['event'] = array(
    'init' => array(
        function()
        { /*var_dump('before init');*/
        }
    ),
    'shutdown' => array(
        function()
        {
            echo '<div style="height: 20px; line-height: 20px; background-color: #ccc;"> Modules: ';
            foreach (\Omni\App::$config['module'] as $module => $config)
            {
                echo $module . ' ';
            }
            echo '</div>';
        },
    ),
);

$config['log'] = APPPATH . '/config/log.php';

$config['i18n'] = array(
    'lang' => array('zh-CN'),
    'langpath' => APPPATH . '/langs',
);

$config['database\module'] = array(
    'default' => array(
        'dns' => "mysql:host=127.0.0.1;port=3306;dbname=test",
        'username' => 'root',
        'password' => '',
        'params' => array()
    ),
);

$config['orm\module'] = true;

return $config;