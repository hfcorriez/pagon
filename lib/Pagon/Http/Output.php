<?php

namespace Pagon\Http;

use Pagon\Config;
use Pagon\Config\Xml;
use Pagon\EventEmitter;
use Pagon\Exception\Stop;
use Pagon\View;

/**
 * Http Output
 *
 * @package Pagon\Http
 * @property \Pagon\App app     Application to service
 * @property int        status
 * @property string     body
 * @property int        length
 * @property string     charset
 * @property array      headers
 * @property array      cookies
 */
class Output extends EventEmitter
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
     * @var \Pagon\App
     */
    public $app;

    /**
     * @var array Local variables
     */
    public $locals;

    /**
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array(
            'status'       => 200,
            'body'         => '',
            'content_type' => 'text/html',
            'length'       => false,
            'charset'      => $injectors['app']->charset,
            'headers'      => array(),
            'cookies'      => array(),
            'app'          => null,
        ));

        $this->app = & $this->injectors['app'];
        $this->locals = & $this->app->locals;
    }

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string|Output
     */
    public function body($content = null)
    {
        if ($content !== null) {
            $this->injectors['body'] = $content;
            $this->injectors['length'] = strlen($this->injectors['body']);
            return $this;
        }

        return $this->injectors['body'];
    }

    /**
     * Write body
     *
     * @param string $data
     * @return string|Output
     */
    public function write($data)
    {
        $this->injectors['body'] .= $data;
        $this->injectors['length'] += strlen($data);

        return $this;
    }

    /**
     * End the response
     *
     * @param string $data
     * @throws \Pagon\Exception\Stop
     * @return void
     */
    public function end($data = '')
    {
        $this->write($data);
        throw new Stop();
    }

    /**
     * Set status code
     *
     * @static
     * @param int $status
     * @return int|Output
     * @throws \Exception
     */
    public function status($status = null)
    {
        if ($status === null) {
            return $this->injectors['status'];
        } elseif (isset(self::$messages[$status])) {
            $this->injectors['status'] = (int)$status;
            return $this;
        } else {
            throw new \Exception('Unknown status :value', array(':value' => $status));
        }
    }

    /**
     * Set or get header
     *
     * @param string $name
     * @param string $value
     * @param bool   $replace
     * @return array|Output
     */
    public function header($name = null, $value = null, $replace = true)
    {
        if ($name === null) {
            return $this->injectors['headers'];
        } elseif (is_array($name)) {
            // Batch set headers
            foreach ($name as $k => $v) {
                // Force replace
                $this->header($k, $v, $replace);
            }
        } else {
            if ($value === null) {
                return $this->injectors['headers'][$name][1];
            } else {
                if (!$replace && !empty($this->injectors['headers'][$name])) {
                    if (is_array($this->injectors['headers'][$name])) {
                        $this->injectors['headers'][$name][] = $value;
                    } else {
                        $this->injectors['headers'][$name] = array($this->injectors['headers'][$name], $value);
                    }
                } else {
                    $this->injectors['headers'][$name] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Get or set charset
     *
     * @param $charset
     * @return string|Output
     */
    public function charset($charset = null)
    {
        if ($charset) {
            $this->injectors['charset'] = $charset;
            return $this;
        }
        return $this->injectors['charset'];
    }

    /**
     * Set LAST-MODIFIED Header
     *
     * @param int|null $time
     * @return string|Output
     */
    public function lastModified($time = null)
    {
        if ($time !== null) {
            if (is_integer($time)) {
                $this->header('Last-Modified', date(DATE_RFC1123, $time));
                if ($time === strtotime($this->app->input->header('If-Modified-Since'))) {
                    $this->app->halt(304);
                }
            }

            return $this;
        }

        return $this->header('Last-Modified');
    }

    /**
     * Set ETAG Header
     *
     * @param  string|null $value
     * @return string|Output
     */
    public function etag($value = null)
    {
        if ($value !== null) {
            //Set etag value
            $value = 'W/"' . $value . '"';
            $this->header('Etag', $value);

            //Check conditional GET
            if ($etag = $this->app->input->header('If-None-Match')) {
                $etags = preg_split('@\s*,\s*@', $etag);
                if (in_array($value, $etags) || in_array('*', $etags)) {
                    $this->app->halt(304);
                }
            }

            return $this;
        }

        return $this->header('Etag');
    }

    /**
     * Set expires for header
     *
     * @param int|string|null $time
     * @return string|Output
     */
    public function expires($time = null)
    {
        if ($time !== null) {
            if (!is_numeric($time) && is_string($time)) {
                $time = strtotime($time);
            } elseif (is_numeric($time) && strlen($time) != 10) {
                $time = time() + (int)$time;
            }

            $this->header('Expires', gmdate(DATE_RFC1123, $time));
            return $this;
        }

        return $this->header('Expires');
    }

    /**
     * Set content type
     *
     * @param string $mime_type
     * @throws \InvalidArgumentException
     * @return string|Output
     */
    public function type($mime_type = null)
    {
        if ($mime_type) {
            if (!strpos($mime_type, '/')) {
                if (!$type = Config::export('mimes')->{$mime_type}[0]) {
                    throw new \InvalidArgumentException("Unknown mime type '{$mime_type}'");
                }
                $mime_type = $type;
            }
            $this->injectors['content_type'] = $mime_type;

            return $this;
        }

        return $this->injectors['content_type'];
    }

    /**
     * Get or set cookie
     *
     * @param string            $key
     * @param array|string|bool $value
     * @param array             $option
     * @return array|string|Output
     */
    public function cookie($key = null, $value = null, $option = array())
    {
        if ($value !== null) {
            if ($value !== false) {
                $this->injectors['cookies'][$key] = array($value, $option);
            } else {
                unset($this->injectors['cookies'][$key]);
            }
            return $this;
        }

        if ($key === null) return $this->injectors['cookies'];
        return isset($this->injectors['cookies'][$key]) ? $this->injectors['cookies'][$key] : null;
    }

    /**
     * Get message by code
     *
     * @return string
     */
    public function message()
    {
        $status = $this->injectors['status'];
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        }
        return null;
    }

    /**
     * Send header
     */
    public function sendHeader()
    {
        // Check header
        if (headers_sent() === false) {
            $this->emit('header');

            // Send header
            header(sprintf('HTTP/%s %s %s', $this->app->input->protocol(), $this->injectors['status'], $this->message()));

            // Set content type if not exists
            if (!isset($this->injectors['headers']['Content-Type'])) {
                $this->injectors['headers']['Content-Type'] = $this->injectors['content_type'] . '; charset=' . $this->injectors['charset'];
            }

            // Content length check and set
            if (!isset($this->injectors['headers']['Content-Length'])
                && is_numeric($this->injectors['length'])
            ) {
                // Set content length
                $this->injectors['headers']['Content-Length'] = $this->injectors['length'];
            }

            // Loop header to send
            if ($this->injectors['headers']) {
                foreach ($this->injectors['headers'] as $name => $value) {
                    // Multiple line header support
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            header("$name: $v", false);
                        }
                    } else {
                        header("$name: $value");
                    }
                }
            }

            // Set cookie
            if ($this->injectors['cookies']) {
                $_default = $this->app->cookie;
                if (!$_default) {
                    $_default = array(
                        'path'     => '/',
                        'domain'   => null,
                        'secure'   => false,
                        'httponly' => false,
                        'timeout'  => 0,
                        'sign'     => false,
                        'secret'   => '',
                        'encrypt'  => false,
                    );
                }
                // Loop for set
                foreach ($this->injectors['cookies'] as $key => $value) {
                    $_option = (array)$value[1] + $_default;
                    $value = $value[0];
                    // Json object cookie
                    if (is_array($value)) {
                        $value = 'j:' . json_encode($value);
                    }

                    // Sign cookie
                    if ($_option['sign'] && $_default['secret']) {
                        $value = 's:' . $value . '.' . hash_hmac('sha1', $value, $_default['secret']);
                    }

                    // Encrypt
                    if ($_option['encrypt']) {
                        $value = 'c:' . $this->app->cryptor->encrypt($value);
                    }

                    // Set cookie
                    setcookie($key, $value, time() + $_option['timeout'], $_option['path'], $_option['domain'], $_option['secure'], $_option['httponly']);
                }
            }
        }
        return $this;
    }

    /**
     * Render with template
     *
     * @param string $template
     * @param array  $data
     * @param array  $options
     * @return Output
     */
    public function render($template, array $data = null, array $options = array())
    {
        $this->app->render($template, $data, $options);
        return $this;
    }

    /**
     * Compile the template
     *
     * @param string $template
     * @param array  $data
     * @param array  $options
     * @return View
     */
    public function compile($template, array $data = null, array $options = array())
    {
        return $this->app->compile($template, $data, $options);
    }

    /**
     * To json
     *
     * @param mixed $data
     * @return Output
     */
    public function json($data)
    {
        $this->type('application/json');
        $this->body(json_encode($data));
        return $this;
    }

    /**
     * To jsonp
     *
     * @param mixed  $data
     * @param string $callback
     * @return Output
     */
    public function jsonp($data, $callback = 'callback')
    {
        $this->type('application/javascript');
        $this->body($callback . '(' . json_encode($data) . ');');
        return $this;
    }

    /**
     * To xml
     *
     * @param object|array $data
     * @param array        $option
     * @return Output
     */
    public function xml($data, array $option = array())
    {
        $this->type('application/xml');
        $this->body(Xml::dump($data, $option));
        return $this;
    }

    /**
     * Download file
     *
     * @param string $file
     * @param string $name
     */
    public function download($file, $name = null)
    {
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename=' . ($name ? $name : basename($file)));
        $this->header('Content-Transfer-Encoding', 'binary');
        $this->header('Expires', '0');
        $this->header('Cache-Control', 'must-revalidate');
        $this->header('Pragma', 'public');
        $this->injectors['length'] = filesize($file);
        ob_clean();
        readfile($file);
        $this->end('');
    }

    /**
     * Redirect url
     *
     * @param string $url
     * @param int    $status
     * @return $this
     */
    public function redirect($url, $status = 302)
    {
        $this->injectors['status'] = $status;
        $this->injectors['headers']['location'] = $url == 'back' ? $this->app->input->refer() : $url;
        return $this;
    }

    /**
     * Clear the response
     */
    public function clear()
    {
        $this->injectors = array(
                'status'       => 200,
                'body'         => '',
                'content_type' => 'text/html',
                'length'       => false,
                'charset'      => $this->app->charset,
                'headers'      => array(),
                'cookies'      => array(),
            ) + $this->injectors;
    }

    /**
     * Is response cachable?
     *
     * @return bool
     */
    public function isCachable()
    {
        return $this->injectors['status'] >= 200 && $this->injectors['status'] < 300 || $this->injectors['status'] == 304;
    }

    /**
     * Is empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->injectors['status'], array(201, 204, 304));
    }

    /**
     * Is 200 ok?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->injectors['status'] === 200;
    }

    /**
     * Is successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->injectors['status'] >= 200 && $this->injectors['status'] < 300;
    }

    /**
     * Is redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->injectors['status'], array(301, 302, 303, 307));
    }

    /**
     * Is forbidden?
     *
     * @return bool
     */
    public function isForbidden()
    {
        return $this->injectors['status'] === 403;
    }

    /**
     * Is found?
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->injectors['status'] === 404;
    }

    /**
     * Is client error?
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->injectors['status'] >= 400 && $this->injectors['status'] < 500;
    }

    /**
     * Is server error?
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->injectors['status'] >= 500 && $this->injectors['status'] < 600;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->injectors['body'];
    }
}