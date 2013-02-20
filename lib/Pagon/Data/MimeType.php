<?php

namespace Pagon\Data;

class MimeType extends \Pagon\Config
{
    protected static $instance;

    /**
     * Load config from file
     */
    public function __construct()
    {
        parent::__construct(include(__DIR__ . '/data/mime_types.php'));
    }

    /**
     * Load mime types data
     *
     * @return MimeType
     */
    public static function load()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
