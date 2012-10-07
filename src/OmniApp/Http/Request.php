<?php

namespace OmniApp\Http;

class Request
{
    public $params = array();

    protected $path;
    protected $track_id;
    protected $script_name;
    protected $path_info;
    protected $url;
    protected $body;
    protected $headers;

    /**
     * Get protocol
     *
     *
     * @return string
     */
    public function protocol()
    {
        return getenv('SERVER_PROTOCOL');
    }

    /**
     * Get path
     *
     *
     * @return mixed
     */
    public function path()
    {
        if (null === $this->path) $this->path = parse_url(getenv('REQUEST_URI'), PHP_URL_PATH);

        return $this->path;
    }

    /**
     * Get request uri
     *
     * @return string
     */
    public function uri()
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
        if (null === $this->script_name) {
            $_script_name = getenv('SCRIPT_NAME');
            if (strpos(getenv('REQUEST_URI'), $_script_name) !== 0) {
                $_script_name = str_replace('\\', '/', dirname($_script_name));
            }
            $this->script_name = rtrim($_script_name, '/');
        }
        return $this->script_name;
    }

    /**
     * Get root uri
     *
     * @return string
     */
    public function rootUri()
    {
        return $this->scriptName();
    }

    /**
     * Get path info
     *
     * @return string
     */
    public function pathInfo()
    {
        if (null === $this->path_info) {
            $_path_info = substr_replace($this->uri(), '', 0, strlen($this->scriptName()));
            if (strpos($_path_info, '?') !== false) {
                $_path_info = substr_replace($_path_info, '', strpos($_path_info, '?')); //query string is not removed automatically
            }
            $this->path_info = $_path_info;
        }
        return $this->path_info;
    }

    /**
     * LEGACY: Get Resource URI (alias for pathInfo)
     *
     * @return string
     */
    public function resourceUri()
    {
        return $this->pathInfo();
    }

    /**
     * Get url
     *
     *
     * @return mixed
     */
    public function url()
    {
        if (null === $this->url) {
            $_url = $this->scheme() . '://' . $this->host();
            if (($this->scheme() === 'https' && $this->port() !== 443) || ($this->scheme() === 'http' && $this->port() !== 80)) {
                $_url .= sprintf(':%s', $this->port());
            }
            $this->url = $_url;
        }

        return $this->url;
    }

    /**
     * Get method
     *
     *
     * @return string
     */
    public function method()
    {
        return getenv('REQUEST_METHOD');
    }

    /**
     * Is given method?
     *
     * @param string $method
     * @return bool
     */
    public function is($method)
    {
        return $this->method() == strtoupper($method);
    }

    /**
     * Is get method?
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->is('get');
    }

    /**
     * Is post method?
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->is('post');
    }

    /**
     * Is put method?
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->is('put');
    }

    /**
     * Is delete method?
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->is('delete');
    }

    /**
     * Is head method?
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->is('head');
    }

    /**
     * Is options method?
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->is('options');
    }

    /**
     * Is ajax
     *
     *
     * @return bool
     */
    public function isAjax()
    {
        return !getenv('X-Requested-With') && 'XMLHttpRequest' == getenv('X-Requested-With');
    }

    /**
     * Is Ajax?
     *
     * @return bool
     */
    public function isXhr()
    {
        return $this->isAjax();
    }

    /**
     * Is secure connection?
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->scheme() === 'https';
    }

    public function accept($type = null)
    {

    }

    public function acceptEncoding($type = null)
    {

    }

    public function acceptLanguage($type = null)
    {

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
     *
     * @return string
     */
    public function refer()
    {
        return getenv('HTTP_REFERER');
    }

    /**
     * Get track id
     *
     *
     * @return mixed
     */
    public function trackId()
    {
        if (null === $this->track_id) {
            $this->track_id = sha1(uniqid());
        }

        return $this->track_id;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function host()
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
     * @return string
     */
    public function domain()
    {
        return $this->host();
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public function scheme()
    {
        return !getenv('HTTPS') || getenv('HTTPS') === 'off' ? 'http' : 'https';
    }

    /**
     * Get port
     *
     * @return int
     */
    public function port()
    {
        return (int)getenv('SERVER_PORT');
    }

    /**
     * Get user agent
     *
     *
     * @return string
     */
    public function userAgent()
    {
        return getenv('HTTP_USER_AGENT');
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function contentType()
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
        $contentType = $this->contentType();
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
        $mediaTypeParams = $this->mediaType();
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
     * Get the param from query string
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public function get($key, $default = null)
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
    public function post($key, $default = null)
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
    public function param($key, $default = null)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    /**
     * Get header or headers
     *
     * @param null $key
     * @return mixed
     */
    public function header($name = null)
    {
        if (null === $this->headers) {
            $_headers = array();
            foreach ($_SERVER as $key => $value) {
                $_name = false;
                if ('HTTP_' === substr($key, 0, 5)) {
                    $_name = substr($key, 5);
                } elseif ('X_' == substr($key, 0, 2)) {
                    $_name = substr($key, 2);
                } elseif (in_array($key, array('CONTENT_LENGTH',
                    'CONTENT_MD5',
                    'CONTENT_TYPE'))
                ) {
                    $_name = $key;
                }
                if (!$_name) continue;

                // Set headers
                $_headers[strtoupper(str_replace('_', '-', $_name))] = trim($value);
            }

            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $_headers['AUTHORIZATION'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $pass);
            }
            $this->headers = $_headers;
            unset($_headers);
        }

        if ($name === null) return $this->headers;
        $name = strtoupper($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Get cookie
     *
     * @param      $key
     * @param null $default
     * @return null
     */
    public function cookie($key, $default = null)
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
    public function body()
    {
        if (null === $this->body) {
            $this->body = @(string)file_get_contents('php://input');
        }
        return $this->body;
    }
}
