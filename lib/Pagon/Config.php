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
     * @var string Config dir
     */
    public static $dir;

    /**
     * @var array Config registers
     */
    protected static $imports = array(
        'mimes' => array('pagon/config/mimes.php', 0),
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
        list($path, $type) = static::$imports[$name];

        // Check file in path
        if (!$file = App::self()->path($path)) {
            throw new \InvalidArgumentException("Can not find file path \"$path\"");
        }

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
        return new self(self::from($file, $type));
    }

    /**
     * Load from file
     *
     * @param string $file
     * @param int    $type
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function from($file, $type = self::LOAD_AUTODETECT)
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

    /**
     * Config string
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
        if (!class_exists($class = __NAMESPACE__ . "\\Config\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }

        /** @var $class Config */
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
        if (!class_exists($class = __NAMESPACE__ . "\\Config\\" . ucfirst(strtolower($type)))
            && !class_exists($class = $type)
        ) {
            throw new \InvalidArgumentException("Non-exists parser for '$type'");
        }

        /** @var $class Config */
        return $class::dump($array, $option);
    }

    /**
     * Dump to string by given type
     *
     * @param string $type
     * @return array
     */
    public function string($type)
    {
        return self::dump($this->injectors, $type);
    }

    /**
     * Dump to file by given type
     *
     * @param string $file
     * @param string $type
     */
    public function file($file, $type)
    {
        file_put_contents($file, $this->string($type));
    }
}
