<?php

namespace OmniApp;

abstract class Route extends Middleware
{
    /**
     * abstract before run
     *
     * @abstract
     */
    protected function before()
    {
        // Implements as you like
    }

    /**
     * abstract after run
     *
     * @abstract
     */
    protected function after()
    {
        // Implements as you like
    }

    abstract public function run();

    /**
     * @return mixed|void
     */
    public function call()
    {
        $this->before();
        $this->run();
        $this->after();
    }

    /**
     * Call next
     */
    public function next()
    {
        call_user_func($this->next);
    }
}

