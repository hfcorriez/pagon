<?php

namespace Pagon\Http;

use Pagon\EventEmitter;
use Pagon\Exception\Pass;
use Pagon\Exception\Stop;
use Pagon\Config;
use Pagon\Html;
use Pagon\View;

/**
 * Http Input
 *
 * @package Pagon\Http
 * @property \Pagon\App app     Application to service
 * @property array      params
 * @property array      query
 * @property array      data
 */
class Input extends EventEmitter
{
    /**
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array(
            'params' => array(),
            'query'  => &$_GET,
            'data'   => &$_POST,
            'files'  => &$_FILES,
            'server' => &$_SERVER
        ));
    }

    /**
     * Get protocol
     *
     *
     * @return string
     */
    public function protocol()
    {
        return $this->injectors['server']['SERVER_PROTOCOL'];
    }

    /**
     * Get request uri
     *
     * @return string
     */
    public function uri()
    {
        return $this->injectors['server']['REQUEST_URI'];
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->injectors['server']['DOCUMENT_ROOT'], '/') . rtrim($this->scriptName(), '/');
    }

    /**
     * Get script name
     *
     * @return string
     */
    public function scriptName()
    {
        if (!isset($this->injectors['script_name'])) {
            $_script_name = $this->injectors['server']['SCRIPT_NAME'];
            if (strpos($this->injectors['server']['REQUEST_URI'], $_script_name) !== 0) {
                $_script_name = str_replace('\\', '/', dirname($_script_name));
            }
            $this->injectors['script_name'] = rtrim($_script_name, '/');
        }
        return $this->injectors['script_name'];
    }

    /**
     * Get path info
     *
     * @return string
     */
    public function path()
    {
        if (!isset($this->injectors['path_info'])) {
            $_path_info = substr_replace($this->injectors['server']['REQUEST_URI'], '', 0, strlen($this->scriptName()));
            if (strpos($_path_info, '?') !== false) {
                // Query string is not removed automatically
                $_path_info = substr_replace($_path_info, '', strpos($_path_info, '?'));
            }
            $this->injectors['path_info'] = (!$_path_info || $_path_info{0} != '/' ? '/' : '') . $_path_info;
        }
        return $this->injectors['path_info'];
    }

    /**
     * Get url
     *
     * @param bool $full
     * @return mixed
     */
    public function url($full = true)
    {
        if (!$full) return $this->injectors['server']['REQUEST_URI'];

        return $this->site() . $this->injectors['server']['REQUEST_URI'];
    }

    /**
     * Get site url
     *
     * @return string
     */
    public function site()
    {
        return $this->scheme() . '://' . $this->host();
    }

    /**
     * Get method
     *
     * @return string
     */
    public function method()
    {
        return $this->injectors['server']['REQUEST_METHOD'];
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
     * Is ajax
     *
     *
     * @return bool
     */
    public function isAjax()
    {
        return !$this->header('x-requested-with') && 'XMLHttpRequest' == $this->header('x-requested-with');
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

    /**
     * Is upload request?
     *
     * @return bool
     */
    public function isUpload()
    {
        return !empty($this->injectors['files']);
    }

    /**
     * Get IP
     *
     * @return string
     */
    public function ip()
    {
        if ($ips = $this->proxy()) {
            return $ips[0];
        }
        return $this->injectors['server']['REMOTE_ADDR'];
    }

    /**
     * Get forward ips
     *
     * @return array
     */
    public function proxy()
    {
        if (empty($this->injectors['server']['HTTP_X_FORWARDED_FOR'])) return array();

        $ips = $this->injectors['server']['HTTP_X_FORWARDED_FOR'];
        return strpos($ips, ', ') ? explode(', ', $ips) : array($ips);
    }

    /**
     * Get sub domains array
     *
     * @return array
     */
    public function subDomains()
    {
        $parts = explode('.', $this->host());
        return array_reverse(array_slice($parts, 0, -2));
    }

    /**
     * Get refer url
     *
     *
     * @return string
     */
    public function refer()
    {
        return isset($this->injectors['server']['HTTP_REFERER']) ? $this->injectors['server']['HTTP_REFERER'] : null;
    }

    /**
     * Get host
     *
     * @param bool $port
     * @return string
     */
    public function host($port = false)
    {
        if (isset($this->injectors['server']['HTTP_HOST']) && ($host = $this->injectors['server']['HTTP_HOST'])) {
            if ($port) return $host;

            if (strpos($host, ':') !== false) {
                $hostParts = explode(':', $host);

                return $hostParts[0];
            }

            return $host;
        }
        return $this->injectors['server']['SERVER_NAME'];
    }

    /**
     * Get domain. Alias for host
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
        return !isset($this->injectors['server']['HTTPS']) || $this->injectors['server']['HTTPS'] == 'off' ? 'http' : 'https';
    }

    /**
     * Get port
     *
     * @return int
     */
    public function port()
    {
        return empty($this->injectors['server']['SERVER_PORT']) ? null : (int)$this->injectors['server']['SERVER_PORT'];
    }

    /**
     * Get user agent
     *
     * @return string
     */
    public function userAgent()
    {
        return $this->ua();
    }

    /**
     * Get user agent
     *
     * @return string
     */
    public function ua()
    {
        return !empty($this->injectors['server']['HTTP_USER_AGENT']) ? $this->injectors['server']['HTTP_USER_AGENT'] : null;
    }

    /**
     * Get media type
     *
     * @return null|string
     */
    public function type()
    {
        if (empty($this->injectors['server']['CONTENT_TYPE'])) return null;
        $type = $this->injectors['server']['CONTENT_TYPE'];

        $parts = preg_split('/\s*[;,]\s*/', $type);

        return strtolower($parts[0]);
    }

    /**
     * @return string
     */
    public function charset()
    {
        if (empty($this->injectors['server']['CONTENT_TYPE'])) return null;
        $type = $this->injectors['server']['CONTENT_TYPE'];

        if (!preg_match('/charset=([a-z0-9\-]+)/', $type, $match)) return null;

        return strtolower($match[1]);
    }

    /**
     * Get Content-Length
     *
     * @return int
     */
    public function length()
    {
        if (empty($this->injectors['server']['CONTENT_LENGTH'])) return 0;

        return (int)$this->injectors['server']['CONTENT_LENGTH'];
    }

    /**
     * Get the file from $_FILE
     *
     * @param string $key
     * @return mixed
     */
    public function file($key)
    {
        return isset($this->injectors['files'][$key]) ? $this->injectors['files'][$key] : null;
    }

    /**
     * Get the param from query string
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function query($key, $default = null)
    {
        if (isset($this->injectors['query'][$key])) {
            return $this->injectors['app']->enabled('safe_query') && View::$rendering ? Html::encode($this->injectors['query'][$key]) : $this->injectors['query'][$key];
        }
        return $default;
    }

    /**
     * Get post data by key
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function data($key, $default = null)
    {
        if (isset($this->injectors['data'][$key])) {
            return $this->injectors['app']->enabled('safe_query') && View::$rendering ? Html::encode($this->injectors['data'][$key]) : $this->injectors['data'][$key];
        }
        return $default;
    }

    /**
     * Get server variable
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function server($key, $default = null)
    {
        return isset($this->injectors['server'][$key]) ? $this->injectors['server'][$key] : $default;
    }

    /**
     * Get any params from get or post
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return isset($this->injectors['params'][$key]) ? $this->injectors['params'][$key] : $default;
    }

    /**
     * Get header or header
     *
     * @param string $name
     * @return mixed
     */
    public function header($name = null)
    {
        if (!isset($this->injectors['headers'])) {
            $_header = array();
            foreach ($this->injectors['server'] as $key => $value) {
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

                // Set header
                $_header[strtolower(str_replace('_', '-', $_name))] = trim($value);
            }

            if (isset($this->injectors['server']['PHP_AUTH_USER'])) {
                $pass = isset($this->injectors['server']['PHP_AUTH_PW']) ? $this->injectors['server']['PHP_AUTH_PW'] : '';
                $_header['authorization'] = 'Basic ' . base64_encode($this->injectors['server']['PHP_AUTH_USER'] . ':' . $pass);
            }
            $this->injectors['headers'] = $_header;
            unset($_header);
        }

        if ($name === null) return $this->injectors['headers'];

        $name = strtolower(str_replace('_', '-', $name));
        return isset($this->injectors['headers'][$name]) ? $this->injectors['headers'][$name] : null;
    }

    /**
     * Get cookie
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function cookie($key = null, $default = null)
    {
        if (!isset($this->injectors['cookies'])) {
            $this->injectors['cookies'] = $_COOKIE;
            $_option = $this->injectors['app']->cookie;
            foreach ($this->injectors['cookies'] as &$value) {
                if (!$value) continue;

                // Check crypt
                if (strpos($value, 'c:') === 0) {
                    $value = $this->injectors['app']->cryptor->decrypt(substr($value, 2));
                }

                // Parse signed cookie
                if ($value && strpos($value, 's:') === 0 && $_option['secret']) {
                    $_pos = strrpos($value, '.');
                    $_data = substr($value, 2, $_pos - 2);
                    if (substr($value, $_pos + 1) === hash_hmac('sha1', $_data, $_option['secret'])) {
                        $value = $_data;
                    } else {
                        $value = false;
                    }
                }

                // Parse json cookie
                if ($value && strpos($value, 'j:') === 0) {
                    $value = json_decode(substr($value, 2), true);
                }
            }
        }
        if ($key === null) return $this->injectors['cookies'];
        return isset($this->injectors['cookies'][$key]) ? $this->injectors['cookies'][$key] : $default;
    }

    /**
     * Get session
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function session($key = null, $value = null)
    {
        if ($value !== null) {
            return $_SESSION[$key] = $value;
        } elseif ($key !== null) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }

        return $_SESSION;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function body()
    {
        if (!isset($this->injectors['body'])) {
            $this->injectors['body'] = @(string)file_get_contents('php://input');
        }
        return $this->injectors['body'];
    }

    /**
     * Check If accept mime type?
     *
     * @param array|string $type
     * @return array|null|string
     */
    public function accept($type = null)
    {
        if (!isset($this->injectors['accept'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT'])) {
                $this->injectors['accept'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT']);
            } else {
                $this->injectors['accept'] = array();
            }
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->injectors['accept'];
        // Support get best match
        if ($type === true) {
            reset($this->injectors['accept']);
            return key($this->injectors['accept']);
        }

        // If type is 'txt', 'xml' and so on, use smarty stracy
        if (is_string($type) && !strpos($type, '/')) {
            $type = Config::export('mimes')->{$type};
            if (!$type) return null;
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->injectors['accept'] as $mime => $q) {
            if ($q && in_array($mime, $type)) return $mime;
        }

        // All match
        if (isset($this->injectors['accept']['*/*'])) return $type[0];
        return null;
    }

    /**
     * Check accept giving encoding?
     *
     * @param string|array $type
     * @return array|null|string
     */
    public function encoding($type = null)
    {
        if (!isset($this->injectors['accept_encoding'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT_ENCODING'])) {
                $this->injectors['accept_encoding'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT_ENCODING']);
            } else {
                $this->injectors['accept_encoding'] = array();
            }
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->injectors['accept_encoding'];
        // Support get best match
        if ($type === true) {
            reset($this->injectors['accept_encoding']);
            return key($this->injectors['accept_encoding']);
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->injectors['accept_encoding'] as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    /**
     * Check if accept given language?
     *
     * @param string|array $type
     * @return array|null|string
     */
    public function language($type = null)
    {
        if (!isset($this->injectors['accept_language'])) {
            if (!empty($this->injectors['server']['HTTP_ACCEPT_LANGUAGE'])) {
                $this->injectors['accept_language'] = self::parseAcceptsMap($this->injectors['server']['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $this->injectors['accept_language'] = array();
            }
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->injectors['accept_language'];
        // Support get best match
        if ($type === true) {
            reset($this->injectors['accept_language']);
            return key($this->injectors['accept_language']);
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->injectors['accept_language'] as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    /**
     * Pass
     *
     * @throws Pass
     */
    public function pass()
    {
        $this->injectors['app']->pass();
    }

    /**
     * Stop
     *
     * @throws Stop
     */
    public function stop()
    {
        $this->injectors['app']->stop();
    }

    /**
     * Build accept map
     *
     * @param $string
     * @return array
     */
    protected static function parseAcceptsMap($string)
    {
        $_accept = array();

        // Accept header is case insensitive, and whitespace isn’t important
        $accept = strtolower(str_replace(' ', '', $string));
        // divide it into parts in the place of a ","
        $accept = explode(',', $accept);
        foreach ($accept as $a) {
            // the default quality is 1.
            $q = 1;
            // check if there is a different quality
            if (strpos($a, ';q=')) {
                // divide "mime/type;q=X" into two parts: "mime/type" i "X"
                list($a, $q) = explode(';q=', $a);
            }
            // mime-type $a is accepted with the quality $q
            // WARNING: $q == 0 means, that mime-type isn’t supported!
            $_accept[$a] = $q;
        }
        arsort($_accept);
        return $_accept;
    }
}
