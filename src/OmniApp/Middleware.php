<?php

namespace OmniApp;

class Middleware
{
    const _CLASS_ = __CLASS__;

    /**
     * @var Middleware
     */
    protected $next;

    /**
     * @var \Closure middleware
     */
    protected $closure;

    /**
     * @var Http\Request|Cli\Input
     */
    protected $request;

    /**
     * @var Http\Response|Cli\Output
     */
    protected $response;

    /**
     * @param callable $closure
     */
    public function __construct(\Closure $closure = null)
    {
        if ($closure) $this->closure = $closure;
    }

    /**
     *  Default call
     */
    public function call()
    {
        if ($this->closure) {
            $self = $this;
            call_user_func_array($this->closure, array($this->request, $this->response, function () use ($self) {
                $self->getNext()->call();
            }));
        } else {
            $this->next();
        }
    }

    /**
     * Set next middleware
     *
     * @param Middleware $middleware
     * @param Http\Request|Cli\Input $request
     * @param Http\Response|Cli\Output $response
     */
    final public function setNext(Middleware $middleware, $request, $response)
    {
        $this->next = $middleware;
        $this->request = $request;
        $this->response = $response;
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
