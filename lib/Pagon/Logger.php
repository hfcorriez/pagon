<?php

namespace Pagon;

use Closure;

/**
 * @method debug(string $text)
 * @method info(string $text)
 * @method warn(string $text)
 * @method error(string $text)
 * @method critical(string $text)
 */
class Logger extends Logger\LoggerInterface
{
    /**
     * @var array Options
     */
    protected $options = array(
        'file'       => 'app.log',
        'auto_write' => false,
        'level'      => 'debug',
        'default'    => true,
        'streams '   => array()
    );

    /**
     * @var array Levels
     */
    protected static $levels = array('debug' => 0, 'info' => 1, 'warn' => 2, 'error' => 3, 'critical' => 4);

    /**
     * @var array Instances has dispensed
     */
    protected static $instances = array();


    /**
     * Dispense logger with config
     *
     * @param string $key Default key is "log"
     * @return Logger
     */
    public static function dispense($key = 'log')
    {
        if (empty(self::$instances[$key])) {
            self::$instances[$key] = new self(App::self()->get($key));
        }

        return self::$instances[$key];
    }

    /**
     *
     * Call static
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $arguments)
    {
        if (!isset(self::$levels[$method])) {
            throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method);
        }

        if (!isset(App::self()->logger)) {
            throw new \RuntimeException('App has no logger found.');
        }

        return call_user_func_array(array(App::self()->logger, $method), $arguments);
    }

    /**
     * @param array $options
     * @return self
     */
    public function __construct($options = array())
    {
        // Default handler
        if (is_bool($options)) {
            $options = array('default' => $options);
        }

        // Construct by parent
        parent::__construct($options);

        // Auto add current file logger to streams
        if ($this->options['level'] && $this->options['default']) {
            $this->add($this->options['level'], $this);
        }

        // The time injector
        $this->time = function () {
            return date('Y-m-d H:i:s') . ',' . substr(microtime(true) * 1000, 10, 3);
        };

        // Injector the token with share instance
        $this->token = $this->share(function () {
            return substr(sha1(uniqid()), 0, 6);
        });

        // The level
        $this->level = $this->protect(function ($level) {
            return str_pad(strtoupper(substr($level, 0, 6)), 6, ' ', STR_PAD_RIGHT);
        });

        $that = $this;
        register_shutdown_function(function () use ($that) {
            $that->emit('flush');
        });
    }

    /**
     * Add stream
     *
     * @param string                                $level
     * @param string|Closure|Logger\LoggerInterface $stream
     * @param array                                 $options
     * @throws \InvalidArgumentException
     */
    public function add($level, $stream, array $options = array())
    {
        if (!isset(self::$levels[$level])) {
            throw new \InvalidArgumentException('Given level "' . $level . '" is not acceptable');
        }

        if (!isset($this->options['streams'][$level])) {
            $this->options['streams'][$level] = array();
        }

        if ($stream instanceof Logger\LoggerInterface) {
            $this->on('flush', function () use ($stream) {
                $stream->write();
                $stream->clean();
            });
        }

        $this->options['streams'][$level][] = is_string($stream) ? array($stream, $options) : $stream;
    }

    /**
     * Log
     *
     * @param string     $text
     * @param int|string $level
     * @throws \InvalidArgumentException
     */
    public function log($text, $level = 'info')
    {
        if (!isset(self::$levels[$level])) {
            throw new \InvalidArgumentException('Given level "' . $level . '" is not acceptable');
        }

        // Default text and level
        $context = array('text' => $text, 'level' => $level);

        // The format matches to convert to context
        foreach ($this->injectors as $key => $injector) {
            $value = $this->$key;
            if ($value instanceof Closure) {
                $context[$key] = call_user_func($value, $context[$key]);
            } else {
                $context[$key] = $value;
            }
        }

        // Emit the log event
        $this->emit('log', $context);

        /**
         * Loop the streams to send message
         */
        foreach ($this->options['streams'] as $stream_level => &$streams) {
            if (self::$levels[$stream_level] > self::$levels[$level]) {
                continue;
            }

            foreach ($streams as &$stream) {
                if (is_array($stream)) {
                    $tries = array(__NAMESPACE__ . '\\Logger\\' . $stream[0], $stream[0]);

                    foreach ($tries as $try) {
                        if (!class_exists($try)) {
                            continue;
                        }

                        $stream = new $try($stream[1]);
                        $this->on('flush', function () use ($stream) {
                            $stream->write();
                            $stream->clean();
                        });
                    }
                } elseif ($stream instanceof \Closure) {
                    $stream($this->build($context), $context);
                    continue;
                }

                if ($stream instanceof Logger\LoggerInterface) {
                    $stream->store($context);
                }
            }
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
     * @return mixed|void
     */
    public function __call($method, $arguments)
    {
        if (isset(self::$levels[$method])) {
            if (!isset($arguments[1])) {
                $text = $arguments[0];
            } else if (is_array($arguments[1])) {
                $text = strtr($arguments[0], $arguments[1]);
            } else if (is_string($arguments[1])) {
                $text = vsprintf($arguments[0], array_slice($arguments, 1));
            } else {
                $text = $arguments[0];
            }
            $this->log($text, $method);
        }
    }

    /**
     * Write log to file
     */
    public function write()
    {
        file_put_contents($this->options['file'], join(PHP_EOL, $this->buildAll()) . PHP_EOL, FILE_APPEND);
    }
}
