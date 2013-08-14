<?php

namespace Pagon\Logger;

use Pagon\EventEmitter;

abstract class LoggerInterface extends EventEmitter
{
    /**
     * @var array Options of logger
     */
    protected $injectors = array(
        'auto_write' => false
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
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        $this->injectors = $injectors + $this->injectors;

        if (isset($this->injectors['format'])) {
            $this->format = $this->injectors['format'];
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

        if ($this->injectors['auto_write']) {
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
    public function format(array $context)
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
    public function formattedMessages()
    {
        $that = $this;
        return array_map(function ($message) use ($that) {
            return $that->format($message);
        }, $this->messages);
    }

    /**
     * Try to write
     */
    public function tryToWrite()
    {
        if (empty($this->messages)) return;

        $this->write();
        $this->clean();
    }

    /**
     * @return mixed
     */
    abstract function write();
}