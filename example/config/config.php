<?php

$config = array(
    'classpath' => array(
        ''     => APPPATH . '/classes',
        'Twig_' => APPPATH . '/vendor',
    ),
    'viewpath'  => APPPATH . '/views',
    'error'     => true,
);

$config['route'] = array(
    '404'   => '\Controller\Front\Page404',
    'error' => '\Controller\Front\Page404',

    ''      => '\Controller\Front\Index',
    'debug' => function()
    {
        var_dump(func_get_args());
        var_dump(__('ddd'));
    },
);

$config['event'] = array(
    'shutdown' => array(
        function()
        {
            echo '<div style="height: 20px; line-height: 20px; background-color: #ccc; text-align: center">';
            echo 'Copyright (c) 2012 Omniapp';
            echo '</div>';
        },
    ),
);

$config['log'] = array(
    'level' => Log::LEVEL_DEBUG,
    'dir'   => APPPATH . '/logs',
);

$config['i18n'] = array(
    'lang'  => array('zh-CN'),
    'dir'   => APPPATH . '/langs',
);

return $config;