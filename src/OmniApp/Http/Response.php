<?php

namespace OmniApp\Http;

class Response
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

    protected static $status = 200;
    protected static $headers = array();
    protected static $body = '';
    protected static $cookies = array();
    protected static $content_type = 'text/html';
    protected static $charset = 'UTF-8';

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string
     */
    public static function body($content = NULL)
    {
        if ($content === NULL) return self::$body;

        return self::$body = (string)$content;
    }

    /**
     * Set status code
     *
     * @static
     * @param int $status
     * @return int|Response
     * @throws \Exception
     */
    public static function status($status = NULL)
    {
        if ($status === NULL) {
            return self::$status;
        } elseif (array_key_exists($status, self::$messages)) {
            return self::$status = (int)$status;
        }
        else throw new \Exception('Unknown status :value', array(':value' => $status));
    }

    /**
     * Set header
     *
     * @static
     * @param null $key
     * @param null $value
     * @return array
     */
    public static function header($key = NULL, $value = NULL)
    {
        if ($key === NULL) {
            return self::$headers;
        } elseif ($value === NULL) return self::$headers[$key];
        else {
            return self::$headers[$key] = $value;
        }
    }

    /**
     * Get lenth of body
     *
     * @static
     * @return int
     */
    public static function length()
    {
        return strlen(self::$body);
    }

    /**
     * Send headers
     *
     * @static
     * @param bool $replace
     */
    public static function sendHeaders($replace = FALSE)
    {

        header(Request::protocol() . ' ' . self::$status . ' ' . self::$messages[self::$status]);
        foreach (self::$headers as $key => $value) header($key . ': ' . $value, $replace);
    }

    /**
     * Send body
     *
     * @static
     */
    public static function sendBody()
    {
        echo self::$body;
    }

    /**
     * Send all
     *
     * @static
     */
    public static function send()
    {
        self::sendHeaders();
        self::sendBody();
    }
}