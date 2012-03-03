<?php

namespace Omni\Orm;


class Module extends \Omni\Module
{
    public static $config;

    public static function init()
    {
        require_once __DIR__ . '/orm.php';
    }
}