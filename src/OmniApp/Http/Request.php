<?php

namespace OmniApp\Http;

class Request
{
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
        static $path = null;
        if (null === $path) $path = parse_url(getenv('REQUEST_URI'), PHP_URL_PATH);

        return $path;
    }

    /**
     * Get request uri
     *
     * @return string
     */
    public static function uri()
    {
        return getenv('REQUEST_URI');
    }

    /**
     * Get script name
     *
     * @return string
     */
    public function scriptName()
    {
        static $script_name = null;
        if (null === $script_name) {
            $script_name = getenv('SCRIPT_NAME');
            if (strpos(getenv('REQUEST_URI'), $script_name) !== 0) {
                $script_name = str_replace('\\', '/', dirname($script_name));
            }
            $script_name = rtrim($script_name, '/');
        }
        return $script_name;
    }

    /**
     * Get root uri
     *
     * @return string
     */
    public static function rootUri()
    {
        return self::scriptName();
    }

    /**
     * Get path info
     *
     * @return string
     */
    public static function pathInfo()
    {
        static $path_info = null;
        if (null === $path_info) {
            $path_info = getenv('PATH_INFO');
            $path_info = substr_replace(self::uri(), '', 0, strlen(self::scriptName()));
            if (strpos($path_info, '?') !== false) {
                $path_info = substr_replace($path_info, '', strpos($path_info, '?')); //query string is not removed automatically
            }
        }
        return $path_info;
    }

    /**
     * LEGACY: Get Resource URI (alias for pathInfo)
     *
     * @return string
     */
    public function resourceUri()
    {
        return self::pathInfo();
    }

    /**
     * Get url
     *
     * @static
     * @return mixed
     */
    public static function url()
    {
        static $url = null;

        if (null === $url) {
            $url = self::scheme() . '://' . self::host();
            if ((self::scheme() === 'https' && self::port() !== 443) || (self::scheme() === 'http' && self::port() !== 80)) {
                $url .= sprintf(':%s', self::port());
            }
        }

        return $url;
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
     * @param string $method
     * @return bool
     */
    public static function is($method)
    {
        return self::method() == strtoupper($method);
    }

    /**
     * Is get method?
     *
     * @return bool
     */
    public static function isGet()
    {
        return self::is('get');
    }

    /**
     * Is post method?
     *
     * @return bool
     */
    public static function isPost()
    {
        return self::is('post');
    }

    /**
     * Is put method?
     *
     * @return bool
     */
    public static function isPut()
    {
        return self::is('put');
    }

    /**
     * Is delete method?
     *
     * @return bool
     */
    public static function isDelete()
    {
        return self::is('delete');
    }

    /**
     * Is head method?
     *
     * @return bool
     */
    public static function isHead()
    {
        return self::is('head');
    }

    /**
     * Is options method?
     *
     * @return bool
     */
    public static function isOptions()
    {
        return self::is('options');
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
     * Get IP
     *
     * @return string
     */
    public function ip()
    {
        if ($ip = getenv('X_FORWARDED_FOR')) {
            return $ip;
        } elseif ($ip = getenv('CLIENT_IP')) {
            return $ip;
        }

        return getenv('REMOTE_ADDR');
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
        static $track_id = null;

        if (null === $track_id) {
            $track_id = sha1(uniqid());
        }

        return $track_id;
    }

    /**
     * Get host
     *
     * @return string
     */
    public static function host()
    {
        if ($host = getenv('HTTP_HOST')) {
            if (strpos($host, ':') !== false) {
                $hostParts = explode(':', $host);

                return $hostParts[0];
            }

            return $host;
        }
        return getenv('SERVER_NAME');
    }

    /**
     * Get domain
     *
     * @static
     * @return string
     */
    public static function domain()
    {
        return self::host();
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public static function scheme()
    {
        return !getenv('HTTPS') || getenv('HTTPS') === 'off' ? 'http' : 'https';
    }

    /**
     * Get port
     *
     * @return int
     */
    public static function port()
    {
        return (int)getenv('SERVER_PORT');
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
     * Get content type
     *
     * @return string
     */
    public static function contentType()
    {
        return getenv('CONTENT_TYPE');
    }

    /**
     * Get media type
     *
     * @return null|string
     */
    public function mediaType()
    {
        $contentType = self::contentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }
        return null;
    }

    /**
     * @return null
     */
    public function contentCharset()
    {
        $mediaTypeParams = self::mediaType();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }
        return null;
    }

    /**
     * Get Content-Length
     *
     * @return int
     */
    public function contentLength()
    {
        if ($len = getenv('CONTENT_LENGTH')) {
            return (int)$len;
        }
        return 0;
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
        static $headers = null;
        if (null === $headers) {
            $headers = array();
            foreach ($_SERVER as $key => $value) {
                $value = is_string($value) ? trim($value) : $value;
                if ('HTTP_' === substr($key, 0, 5)) {
                    $headers[strtolower(substr($key, 5))] = $value;
                } elseif ('X_' == substr($key, 0, 2)) {
                    $headers[strtolower(substr($key, 2))] = $value;
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
        }

        if ($key === null) return $headers;
        return $headers[$key];
    }

    /**
     * Get cookie
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public static function cookie($key, $default = null)
    {
        if (func_num_args() === 0) {
            return $_COOKIE;
        }
        return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : $default;
    }

    /**
     * Get body
     *
     * @return string
     */
    public static function body()
    {
        static $rawInput = null;
        if (null === $rawInput) {
            $rawInput = @file_get_contents('php://input');
            if (!$rawInput) $rawInput = '';
        }
        return $rawInput;
    }
}
