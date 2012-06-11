<?php

namespace Omni\Orm;


class Module extends \Omni\Module
{
    public static function init($config)
    {
        require_once __DIR__ . '/orm.php';
    }
}