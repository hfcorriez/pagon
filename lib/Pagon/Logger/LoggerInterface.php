<?php

namespace Pagon\Logger;

use Pagon\Fiber;

abstract class LoggerInterface extends Fiber
{
    /**
     * @var array Options of logger
     */
    protected $options = array(
        'auto_write' => false,
    );

    /**
     * @var string Log format
     */
    protected $format = '[$time] $token - $level - $text';

    /**
     * @var array Saved messages
     */
    protected $messages = array();

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;

        if (isset($this->options['format'])) {
            $this->format = $this->options['format'];
        }
    }

    /**
     * Send message to logger
     *
     * @param $message
     */
    public function store($message)
    {
        $this->messages[] = $message;

        if ($this->options['auto_write']) {
            $this->write();
            $this->clean();
        }
    }

    /**
     * Clear
     */
    public function clean()
    {
        $this->messages = array();
    }

    /**
     * Build the message
     *
     * @param array $context
     * @return string
     */
    public function build(array $context)
    {
        /**
         * Prepare the variable to replace
         */
        foreach ($context as $k => $v) {
            unset($context[$k]);
            $context['$' . $k] = $v;
        }

        // Build message
        return strtr($this->format, $context);
    }

    /**
     * Build all messages
     *
     * @return array
     */
    public function buildAll()
    {
        $that = $this;
        return array_map(function ($message) use ($that) {
            return $that->build($message);
        }, $this->messages);
    }

    /**
     * @return mixed
     */
    abstract function write();
}