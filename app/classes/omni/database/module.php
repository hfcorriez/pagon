<?php

namespace Omni\Database
{

    class Module extends \Omni\Module
    {
        public static $config;

        public static function init()
        {
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