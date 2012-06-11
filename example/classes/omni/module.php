<?php
/**
 * OmniApp Framework
 *
 * @package OmniApp
 * @author Corrie Zhao <hfcorriez@gmail.com>
 * @copyright (c) 2011 - 2012 OmniApp Framework
 * @license http://www.apache.org/licenses/LICENSE-2.0
 *
 */

namespace Omni;

/**
 * Abstract Module
 */
abstract class Module
{
    /**
     * Load module
     *
     * @static
     * @param Module[] $modules
     */
    final public static function load($modules = array())
    {
        foreach ($modules as $module => $config) {
            if ($module{0} !== '\\') $module = '\\' . __NAMESPACE__ . '\\' . $module;
            if (class_exists($module)) {
                if (!$config) continue;
                if (is_string($config)) {
                    $config = include($config);
                }
                $module::init($config);
            }
        }
    }
}
