<?php

namespace OmniApp\Http;

use OmniApp\Data\MimeType;
use OmniApp\Registry;
use OmniApp\Exception\Stop;
use OmniApp\App;

class Response extends Registry
{
    public static $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    /**
     * @var \OmniApp\App
     */
    public $app;

    protected $status = 200;
    protected $headers = array();
    protected $body = '';
    protected $cookies = array();
    protected $content_type = 'text/html';
    protected $charset = 'utf-8';
    protected $length = 0;

    /**
     * @param \OmniApp\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string
     */
    public function body($content = null)
    {
        if ($content !== null) $this->write($content, 0);

        return $this->body;
    }

    /**
     * Get and set length
     *
     * @param  int|null $length
     * @return int
     */
    public function length($length = null)
    {
        if (!is_null($length)) {
            $this->length = (int)$length;
        }

        return $this->length;
    }

    /**
     * Get or set charset
     *
     * @param $charset
     * @return string
     */
    public function charset($charset = null)
    {
        if ($charset) {
            $this->charset = $charset;
        }
        return $this->charset;
    }

    /**
     * Write body
     *
     * @param string $body
     * @param int    $pos
     * @return string
     */
    public function write($body, $pos = 1)
    {
        if (!$body) return $this->body;

        if ($pos === 1) {
            $this->body .= $body;
        } elseif ($pos === -1) {
            $this->body = $body . $this->body;
        } else {
            $this->body = $body;
        }
        $this->length = strlen($this->body);

        return $this->body;
    }

    /**
     * Set status code
     *
     * @static
     * @param int $status
     * @return int|Response
     * @throws \Exception
     */
    public function status($status = null)
    {
        if ($status === null) {
            return $this->status;
        } elseif (array_key_exists($status, self::$messages)) {
            return $this->status = (int)$status;
        } else throw new \Exception('Unknown status :value', array(':value' => $status));
    }

    /**
     * Set or get header
     *
     * @param string $name
     * @param string $value
     * @param bool   $replace
     * @return array|null
     */
    public function header($name = null, $value = null, $replace = false)
    {
        if ($name === null) {
            return $this->headers;
        } else {
            $name = strtoupper(str_replace('_', '-', $name));
            if ($value === null) {
                return $this->headers[$name];
            } else {
                return $this->headers[$name] = !$replace && !empty($this->headers[$name]) ? $this->headers[$name] . "\n" . $value : $value;
            }
        }
    }

    /**
     * Set content type
     *
     * @param $mime_type
     * @return null
     */
    public function contentType($mime_type)
    {
        if ($mime_type) {
            if (!strpos($mime_type, '/')) {
                $mime_type = MimeType::load()->get($mime_type);
                $mime_type = $mime_type[0];
            }

            $this->header('Content-Type', $mime_type . '; charset=' . $this->charset());
        }

        return $this->header('Content-Type');
    }

    /**
     * Get or set cookie
     *
     * @param $key
     * @param $value
     * @return array|string|bool
     */
    public function cookie($key = null, $value = null)
    {
        if ($key === null) {
            return $_COOKIE;
        } elseif ($value) {
            setcookie($key, $value);
        }
        return $_COOKIE[$key];
    }

    /**
     * Get message by code
     *
     * @return string|null
     */
    public function message()
    {
        if (isset(self::$messages[$this->status])) {
            return self::$messages[$this->status];
        }
        return null;
    }

    /**
     * Send headers
     */
    public function sendHeader()
    {
        // Check headers
        if (headers_sent() === false) {
            // Send header
            header(sprintf('HTTP/%s %s %s', $this->app->request->protocol(), $this->status, $this->message()));

            // Loop headers to send
            if ($this->headers) {
                foreach ($this->headers as $name => $value) {
                    // Multiple line headers support
                    $h_values = explode("\n", $value);
                    foreach ($h_values as $h_val) {
                        header("$name: $h_val", false);
                    }
                }
            }
        }
    }

    /**
     * Set expires time
     *
     * @param string|int $time
     * @return array|null
     */
    public function expires($time = null)
    {
        if ($time) {
            if (is_string($time)) {
                $time = strtotime($time);
            }
            $this->header('Expires', gmdate(DATE_RFC1123, $time));
        }
        return $this->header('Expires');
    }

    /**
     * Render with template
     *
     * @param $template
     * @param $data
     */
    public function render($template, $data = array())
    {
        $this->app->render($template, $data);
    }

    /**
     * To json
     *
     * @param mixed $data
     */
    public function json($data)
    {
        $this->contentType('application/json');
        $this->body(json_encode($data));
    }

    /**
     * To jsonp
     *
     * @param mixed  $data
     * @param string $callback
     */
    public function jsonp($data, $callback = 'callback')
    {
        $this->contentType('application/javascript');
        $this->body($callback . '(' . json_encode($data) . ');');
    }

    /**
     * To xml
     *
     * @param object|array $data
     * @param string       $root
     * @param string       $item
     */
    public function xml($data, $root = 'root', $item = 'item')
    {
        $this->contentType('application/xml');
        $this->body(\OmniApp\Helper\XML::fromArray($data, $root, $item));
    }

    /**
     * Redirect url
     *
     * @param string $url
     * @param int    $status
     */
    public function redirect($url, $status = 302)
    {
        $this->status = $status;
        $this->headers['Location'] = $url;
    }

    /**
     * Stop
     *
     * @throws Stop
     */
    public static function stop()
    {
        throw new Stop();
    }

    /**
     * Is empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->status, array(201, 204, 304));
    }

    /**
     * Is 200 ok?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->status === 200;
    }

    /**
     * Is successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Is redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->status, array(301, 302, 303, 307));
    }

    /**
     * Is forbidden?
     *
     * @return bool
     */
    public function isForbidden()
    {
        return $this->status === 403;
    }

    /**
     * Is found?
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->status === 404;
    }

    /**
     * Is client error?
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Is server error?
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->status >= 500 && $this->status < 600;
    }
}