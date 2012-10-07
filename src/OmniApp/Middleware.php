<?php

namespace OmniApp;

abstract class Middleware
{
    /**
     * @var Middleware
     */
    protected $next;

    abstract public function call();

    /**
     * Set next middleware
     *
     * @param Middleware $middleware
     */
    final public function setNext(Middleware $middleware)
    {
        $this->next = $middleware;
    }

    /**
     * Call next
     */
    final protected function next()
    {
        $this->next->call();
    }

    /**
     * Get next middleware
     *
     * @return Middleware
     */
    final public function getNext()
    {
        return $this->next;
    }
}
