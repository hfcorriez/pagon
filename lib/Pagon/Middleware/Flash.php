<?php

namespace Pagon\Middleware;

use Pagon\Middleware;

class Flash extends Middleware
{
    // Some options
    protected $injectors = array(
        'key' => 'flash'
    );

    /**
     * @var array Current messages to fetch
     */
    protected $current = array();

    /**
     * @var array Next messages to save
     */
    protected $flash = array();

    /**
     * Set message with type
     *
     * @param string       $type
     * @param string|array $message
     * @return Flash
     */
    public function set($type, $message)
    {
        if (!isset($this->flash[$type])) {
            $this->flash[$type] = array();
        }


        if (!is_array($message)) {
            $this->flash[$type][] = $message;
        } else {
            $this->flash[$type] = array_merge($this->flash[$type], $message);
        }
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
     * Get flash message at now
     */
    public function flashNow()
    {
        $this->current = $this->input->flash = $this->flash;
    }

    /**
     * Keep flash message
     */
    public function flashKeep()
    {
        $this->flash = array_merge_recursive($this->flash, $this->current);
    }

    /**
     * Call
     *
     * @return bool|void
     */
    public function call()
    {
        $this->current = $this->input->flash = (array)$this->input->session($this->injectors['key']);

        $self = & $this;

        $this->output->protect('flash', function ($type = null, $message = null) use ($self) {
            if ($type && $message) {
                return $self->set($type, $message);
            } elseif ($type) {
                return $self->get($type);
            }
            return $self;
        });

        $this->output->protect('flashNow', function () use ($self) {
            $self->flashNow();
        });

        $this->output->protect('flashKeep', function () use ($self) {
            $self->flashKeep();
        });

        $this->next();

        // Save to session
        $this->input->session($this->injectors['key'], $this->flash);
    }
}
