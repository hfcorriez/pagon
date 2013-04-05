<?php

namespace Pagon\Helper;

use Pagon\App;

class Url
{
    /**
     * To url
     *
     * @param string $path
     * @param array  $query
     * @param bool   $full
     * @return string
     */
    public static function to($path, array $query = null, $full = false)
    {
        return
            ($full ? self::site() : '') .
            self::base() .
            '/' . ltrim($path, '/') .
            ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * Get current path with give query replaced
     *
     * @param array $query
     * @param bool  $full
     * @return string
     */
    public static function current(array $query = null, $full = false)
    {
        $input = App::self()->input;
        return self::to($input->path(), ($query ? $query : array()) + $input->query, $full);
    }

    /**
     * Get site of application
     *
     * @return mixed|string
     */
    public static function site()
    {
        return ($site = App::self()->get('site')) ? $site : App::self()->input->site();
    }

    /**
     * Get base path
     *
     * @return string
     */
    public static function base()
    {
        return App::self()->input->scriptName();
    }
}