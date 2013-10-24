<?php

namespace Pagon;

use Pagon\App;

/**
 * Url
 * functional usage for build URL
 *
 * @package Pagon
 */
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
            (!strpos($path, '://') ?
                ($full ? self::site() : self::base()) . '/' . ltrim($path, '/')
                : $path) .
            ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * To url with route
     *
     * @param string $name
     * @param array  $params
     * @param array  $query
     * @param bool   $full
     * @return string
     */
    public static function route($name, array $params = null, array $query = null, $full = false)
    {
        $app = App::self();

        $path = $app->router->path($name);

        if ($params) {
            foreach ($params as $param => $value) {
                if (is_string($param)) {
                    $path = preg_replace('/:' . $param . '/', $value, $path, 1);
                } else {
                    $path = preg_replace('/\(.+?\)|\*/', $value, $path, 1);
                }
            }
        }

        return self::to($path, $query, $full);
    }

    /**
     * To asset url
     *
     * @param string $path
     * @param array  $query
     * @param bool   $full
     * @return string
     */
    public static function asset($path, array $query = null, $full = false)
    {
        if (strpos($path, '://')) return self::to($path, $query, $full);

        $asset_url = App::self()->get('asset_url');

        return
            ($asset_url ? $asset_url : ($full ? self::site() : self::base())) .
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
     * @return string
     */
    public static function site()
    {
        return ($site = App::self()->get('site_url')) ? $site : App::self()->input->site();
    }

    /**
     * Get base path
     *
     * @return string
     */
    public static function base()
    {
        return ($base = App::self()->get('base_uri')) ? $base : App::self()->input->scriptName();
    }
}