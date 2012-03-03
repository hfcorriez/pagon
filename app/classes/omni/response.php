<?php

namespace Omni;

class Response extends Instance
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

    protected $_status = 200;
    protected $_headers = array();
    protected $_body = '';
    protected $_cookies = array();
    protected $_protocol;
    protected $_content_type = 'text/html';
    protected $_charset = 'UTF-8';

    public function __construct()
    {
        $this->_protocol = Request::instance()->protocol;
        $this->_headers['x-powered-by'] = 'OmniApp ' . VERSION;
    }

    public function __toString()
    {
        return $this->_body;
    }

    public function body($content = NULL)
    {
        if ($content === NULL) return $this->_body;

        $this->_body = (string)$content;
        return $this;
    }

    public function protocol($protocol = NULL)
    {
        if ($protocol)
        {
            $this->_protocol = strtoupper($protocol);
            return $this;
        }

        return $this->_protocol;
    }

    public function status($status = NULL)
    {
        if ($status === NULL) return $this->_status;
        elseif (array_key_exists($status, self::$messages))
        {
            $this->_status = (int)$status;
            return $this;
        }
        else throw new Exception(__METHOD__ . ' unknown status :value', array(':value' => $status));
    }

    public function header($key = NULL, $value = NULL)
    {
        if ($key === NULL) return $this->_headers;
        elseif ($value === NULL) return $this->_headers[$key];
        else
        {
            $this->_headers[$key] = $value;
            return $this;
        }
    }

    public function length()
    {
        return strlen($this->body());
    }

    public function sendHeaders($replace = FALSE)
    {
        header($this->_protocol . ' ' . $this->_status . ' ' . self::$messages[$this->_status]);
        foreach ($this->_headers as $key => $value) header($key . ': ' . $value, $replace);
        return $this;
    }
}