<?php

namespace Pagon\Middleware;

/**
 * @method debug(string $text)
 * @method info(string $text)
 * @method warning(string $text)
 * @method error(string $text)
 * @method critical(string $text)
 */
class Logger extends \Pagon\Middleware
{
    protected $options = array(
        'file'         => 'pagon.log',
        'write_on_log' => false,
    );

    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;

    protected static $params;
    protected static $levels = array('debug', 'info', 'warning', 'error', 'critical');
    protected static $format = '[$time] $token - $level - $text';
    protected static $messages = array();

    /**
     * @return mixed|void
     */
    public function call()
    {
        self::$params = array(
            'time'  => function () {
                return date('Y-m-d H:i:s');
            },
            'token' => function () {
                return substr(sha1(uniqid()), 0, 6);
            },
            'level' => function ($level) {
                return str_pad($level, 8, ' ', STR_PAD_BOTH);
            }
        );

        $this->app->logger = $this;
        $that = $this;

        $this->app->on('shutdown', function () use ($that) {
            $that->write();
        });

        $this->next();
    }

    /**
     * Log
     *
     * @param string $text
     * @param int    $level
     */
    public function log($text, $level = self::INFO)
    {
        $message = array('text' => $text, 'level' => self::$levels[$level]);

        if (preg_match_all('/\$(\w+)/', self::$format, $matches)) {
            $matches = $matches[1];
            foreach ($matches as $match) {
                if (!isset(self::$params[$match])) continue;
                if (is_callable(self::$params[$match])) {
                    $message[$match] = call_user_func(self::$params[$match], isset($message[$match]) ? $message[$match] : null);
                } else {
                    $message[$match] = self::$params[$match];
                }
            }
        }

        foreach ($message as $k => $v) {
            unset($message[$k]);
            $message['$' . $k] = $v;
        }

        self::$messages[] = strtr(self::$format, $message);

        if ($this->options['write_on_log']) {
            $this->write();
        }
    }

    /**
     * Support level method call
     *
     * @example
     *
     *  $logger->debug('test');
     *  $logger->info('info');
     *  $logger->info('this is %s', 'a');
     *  $logger->info('this is :id', array(':id' => 1));
     *
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, self::$levels)) {
            if (!isset($arguments[1])) {
                $text = $arguments[0];
            } else if (is_array($arguments[1])) {
                $text = strtr($arguments[0], $arguments[1]);
            } else if (is_string($arguments[1])) {
                $text = vsprintf($arguments[0], array_slice($arguments, 1));
            } else {
                $text = $arguments[0];
            }
            $this->log($text, array_search($method, self::$levels));
        }
    }

    /**
     * Write log to file
     */
    public function write()
    {
        foreach (self::$messages as $index => $message) {
            unset(self::$messages[$index]);
            file_put_contents($this->options['file'], $message . PHP_EOL, FILE_APPEND);
        }
    }
}
