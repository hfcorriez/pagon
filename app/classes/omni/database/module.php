<?php

namespace Omni\Database;

class Module extends \Omni\Module
{
    public static function init()
    {
        require_once __DIR__ . '/database.php';
    }
}