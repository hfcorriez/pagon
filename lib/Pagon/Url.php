<?php

namespace Pagon;

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
        $url_mode = App::self()->get('url_rewrite');
        if (isset($url_mode)) $url_mode = !$url_mode;
        return self::transform($path, $query, $full ? self::site($url_mode) : self::base($url_mode));
    }

    /**
     * Transform url
     *
     * @param string $path
     * @param array  $query
     * @param bool   $prefix
     * @return string
     */
    public static function transform($path, array $query = null, $prefix = false)
    {
        if (strpos($path, '://')) $url = $path;
        else {
            $url = rtrim(($prefix ? rtrim($prefix, '/') : '') . '/' . trim($path, '/'), '/');
            if (!$url) $url = '/';
        }

        return $url . ($query ? '?' . http_build_query($query) : '');
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
     * @return string
     */
    public static function asset($path, array $query = null)
    {
        $asset_url = App::self()->get('asset_url');

        return self::transform($path, $query, $asset_url ? $asset_url : self::base(false));
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
        return self::to($input->path(), (array)$query + $input->query, $full);
    }

    /**
     * Get site of application
     *
     * @param bool $basename
     * @return string
     */
    public static function site($basename = null)
    {
        return ($site = App::self()->get('site_url')) ? $site : App::self()->input->site($basename);
    }

    /**
     * Get base path
     *
     * @param bool $basename
     * @return string
     */
    public static function base($basename = null)
    {
        return App::self()->input->scriptName($basename);
    }
}