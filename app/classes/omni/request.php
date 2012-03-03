<?php

namespace Omni;

class Request extends Instance
{
    public $path;
    public $url;
    public $method;
    public $protocol;
    public $is_ajax;
    public $headers;
    public $referer;
    public $track_id;
    public $domain;
    public $user_agent;

    public function __construct()
    {
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        $this->is_ajax = !empty($_SERVER['X-Requested-With']) && 'XMLHttpRequest' == $_SERVER['X-Requested-With'];
        $this->referer = empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'];
        $this->track_id = !empty($_SERVER['HTTP_TRACK_ID']) ? $_SERVER['HTTP_TRACK_ID'] : md5(uniqid());
        $this->domain = $_SERVER['HTTP_HOST'];
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];

        $headers = array();
        foreach ($_SERVER as $key => $value)
        {
            if ('HTTP_' === substr($key, 0, 5)) $headers[strtolower(substr($key, 5))] = $value;
            elseif (in_array($key, array('CONTENT_LENGTH',
                                        'CONTENT_MD5',
                                        'CONTENT_TYPE'))
            ) $headers[strtolower($key)] = $value;
        }

        if (isset($_SERVER['PHP_AUTH_USER']))
        {
            $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $pass);
        }
        $this->headers = $headers;
    }
}
