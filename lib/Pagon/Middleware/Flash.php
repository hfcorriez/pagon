<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class Flash extends Middleware
{
    // Some options
    protected $options = array(
        'key' => 'flash'
    );

    /**
     * @var array Current messages to fetch
     */
    protected $current = array();

    /**
     * @var array Next messages to save
     */
    protected $next = array();

    /**
     * Set message with type
     *
     * @param string $type
     * @param string $message
     * @return Flash
     */
    public function set($type, $message)
    {
        if (!isset($this->next[$type])) {
            $this->next[$type] = array();
        }

        $this->next[$type][] = $message;
        return $this;
    }

    /**
     * Get message with type
     *
     * @param string|null $type
     * @return array
     */
    public function get($type = null)
    {
        if (!$type) return $this->current;

        return isset($this->current[$type]) ? $this->current[$type] : array();
    }

    /**
     * Call
     *
     * @return bool|void
     */
    public function call()
    {
        $this->current = (array)$this->input->session($this->options['key']);

        $self = & $this;

        /** @noinspection PhpUndefinedFieldInspection */
        $this->output->protect('flash', function ($type = null, $message = null) use ($self) {
            if ($type && $message) {
                return $self->set($type, $message);
            } elseif ($type) {
                return $self->get($type);
            }
            return $self;
        });

        $this->next();

        // Save to session
        $this->input->session($this->options['key'], $this->next);
    }
}
