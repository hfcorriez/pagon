<?php

namespace OmniApp\Http;

class Request
{
    protected static $path;
    protected static $url;
    protected static $track_id;
    protected static $headers;

    /**
     * Get protocol
     *
     * @static
     * @return string
     */
    public static function protocol()
    {
        return getenv('SERVER_PROTOCOL');
    }

    /**
     * Get path
     *
     * @static
     * @return mixed
     */
    public static function path()
    {
        if (!self::$path) self::$path = parse_url(getenv('REQUEST_URI'), PHP_URL_PATH);

        return self::$path;
    }

    /**
     * Get url
     *
     * @static
     * @return mixed
     */
    public static function url()
    {
        if (!self::$url) {
            self::$url = (strtolower(getenv('HTTPS')) == 'on' ? 'https' : 'http') . '://' . getenv('HTTP_HOST') . getenv('REQUEST_URI');
        }

        return self::$url;
    }

    /**
     * Get method
     *
     * @static
     * @return string
     */
    public static function method()
    {
        return getenv('REQUEST_METHOD');
    }

    /**
     * Is given method?
     *
     * @param $method
     * @return bool
     */
    public static function is($method)
    {
        return self::method() == strtoupper($method);
    }

    /**
     * Is ajax
     *
     * @static
     * @return bool
     */
    public static function isAjax()
    {
        return !getenv('X-Requested-With') && 'XMLHttpRequest' == getenv('X-Requested-With');
    }

    /**
     * Get refer url
     *
     * @static
     * @return string
     */
    public static function refer()
    {
        return getenv('HTTP_REFERER');
    }

    /**
     * Get track id
     *
     * @static
     * @return mixed
     */
    public static function trackId()
    {
        if (!self::$track_id) {
            self::$track_id = md5(uniqid());
        }

        return self::$track_id;
    }

    /**
     * Get domain
     *
     * @static
     * @return string
     */
    public static function domain()
    {
        return getenv('HTTP_HOST');
    }

    /**
     * Get user agent
     *
     * @static
     * @return string
     */
    public static function userAgent()
    {
        return getenv('HTTP_USER_AGENT');
    }

    /**
     * Get the param from querystring
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public static function get($key, $default = null)
    {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    /**
     * Get post data by key
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public static function post($key, $default = null)
    {
        return array_key_exists($key, $_POST) ? $_POST[$key] : $default;

    }

    /**
     * Get any params from get or post
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public static function param($key, $default = null)
    {
        return array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : $default;
    }

    /**
     * Get header or headers
     *
     * @static
     * @param null $key
     * @return mixed
     */
    public static function header($key = null)
    {
        if (!self::$headers) {
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' === substr($key, 0, 5)) {
                    $headers[strtolower(substr($key, 5))] = $value;
                } elseif (in_array($key, array('CONTENT_LENGTH',
                    'CONTENT_MD5',
                    'CONTENT_TYPE'))
                ) {
                    $headers[strtolower($key)] = $value;
                }
            }

            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $pass);
            }
            self::$headers = $headers;
        }

        if ($key === null) return self::$headers;
        return self::$headers[$key];
    }
}
