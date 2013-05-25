<?php

namespace Pagon;

/**
 * Parser
 * Format Parser
 *
 * @package Pagon
 */
class Parser
{
    /**
     * Auto Detect File Extension
     */
    const LOAD_AUTODETECT = 0;

    /**
     * Parser string
     *
     * @param string $string
     * @param string $type
     * @param array  $option
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function parse($string, $type, array $option = array())
    {
        // Try to use custom parser
        if (!class_exists($class = __NAMESPACE__ . "\\Parser\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }

        return $class::parse($string, $option);
    }

    /**
     * Dump to array
     *
     * @param array  $array
     * @param string $type
     * @param array  $option
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function dump($array, $type, array $option = array())
    {
        // Try to use custom parser
        if (!class_exists($class = __NAMESPACE__ . "\\Parser\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }


        return $class::dump($array, $option);
    }

    /**
     * Load from file
     *
     * @param string $file
     * @param int    $type
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function load($file, $type = self::LOAD_AUTODETECT)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("Config load error with non-exists file \"$file\"");
        }

        if ($type === self::LOAD_AUTODETECT) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        if ($type !== 'php') {
            return self::parse(file_get_contents($file), $type);
        } else {
            return include($file);
        }
    }
}
