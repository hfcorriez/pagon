<?php

namespace Pagon;

/**
 * Config
 * Manage config and parse config
 *
 * @package Pagon
 */
class Config extends Fiber
{
    /**
     * Auto Detect File Extension
     */
    const LOAD_AUTODETECT = 0;

    /**
     * @var array Config registers
     */
    protected static $imports = array(
        'mimes' => array('mimes.php', 0),
    );

    /**
     * Init config
     *
     * @param array $input
     * @return Config
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * Register a config
     *
     * @param string     $name
     * @param string     $file
     * @param int|string $type
     * @return void
     */
    public static function import($name, $file, $type = self::LOAD_AUTODETECT)
    {
        static::$imports[$name] = array($file, $type);
    }

    /**
     * Export the config
     *
     * @param string $name
     * @return Config
     * @throws \InvalidArgumentException
     */
    public static function export($name)
    {
        if (!isset(static::$imports[$name])) {
            throw new \InvalidArgumentException("Load config error with non-exists name \"$name\"");
        }

        // Check if config already exists?
        if (static::$imports[$name] instanceof Config) {
            return static::$imports[$name];
        }

        // Try to load
        list($file, $type) = static::$imports[$name];

        // Use data dir
        if ($file{0} != '/') $file = __DIR__ . '/Config/' . $file;

        return static::$imports[$name] = static::load($file, $type);
    }

    /**
     * Load from file
     *
     * @param string     $file
     * @param int|string $type
     * @throws \InvalidArgumentException
     * @return Config
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
            return new self(Parser::load($file, $type));
        } else {
            return new self(include($file));
        }
    }

    /**
     * Dump to given type
     *
     * @param string $type
     * @return array
     */
    public function dump($type)
    {
        return Parser::dump($this->injectors, $type);
    }
}
