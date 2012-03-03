<?php

namespace Omni\Database
{
    class Module extends \Omni\Module
    {
        public static $config;

        public static function init($config)
        {
            self::$config = $config;
            require_once __DIR__ . '/database.php';
        }
    }
}

namespace Omni
{
    class Database
    {
        public static function instance($name = 'default')
        {
            return new \Database(\Omni\Database\Module::$config[$name]);
        }
    }
}