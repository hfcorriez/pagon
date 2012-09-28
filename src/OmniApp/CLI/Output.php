<?php

namespace OmniApp\CLI;

class Output
{
    protected static $body;
    protected static $status = 0;

    /**
     * Set or get status
     *
     * @param null $status
     * @return int|string
     */
    public static function status($status = null)
    {
        if (is_numeric($status)) {
            self::$status = $status;
        }
        return self::$status;
    }

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string
     */
    public static function body($content = null)
    {
        if ($content !== null) self::write($content);

        return self::$body;
    }

    /**
     * Write body
     *
     * @param      $body
     * @param bool $replace
     * @return string
     */
    public static function write($body, $replace = false)
    {
        if ($replace) {
            self::$body = $body;
        } else {
            self::$body .= (string)$body;
        }

        return self::$body;
    }

    /**
     * Is ok?
     *
     * @return bool
     */
    public static function isOk()
    {
        return self::$status === 0;
    }
}