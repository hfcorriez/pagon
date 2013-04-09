<?php

namespace Pagon;

class Config extends Fiber
{
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
     * Load config by name
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
            throw new \InvalidArgumentException("Config load error with non-exists file");
        }

        if ($type === self::LOAD_AUTODETECT) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        if ($type !== 'php') {
            return new self(self::parse($file, $type));
        } else {
            return new self(include($file));
        }
    }

    /**
     * Parser file
     *
     * @param string     $file
     * @param int|string $type
     * @throws \InvalidArgumentException
     * @return Config
     */
    public static function parse($file, $type = self::LOAD_AUTODETECT)
    {
        $type == self::LOAD_AUTODETECT && $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Try to use custom parser
        if (class_exists($type)) {
            $class = $type;
        } else {
            $class = __NAMESPACE__ . "\\Config\\Parser\\" . ucfirst($type);
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("There is no parser '$class' for '$type'");
        }

        return new self($class::parse(file_get_contents($file)));
    }
}
