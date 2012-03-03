<?php

namespace Omni;

abstract class Module
{
    /**
     * @static
     * @param Module[] $modules
     */
    final public static function load($modules = array())
    {
        foreach ($modules as $module => $config)
        {
            if ($module{0} !== '\\') $module = '\\' . __NAMESPACE__ . '\\' . $module;
            if (class_exists($module))
            {
                if ($config && is_string($config)) $config = include($config);
                $module::init($config);
            }
        }
    }
}
