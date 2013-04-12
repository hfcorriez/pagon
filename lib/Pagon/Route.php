<?php

namespace Pagon;

class Route extends Middleware
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

    /**
     * Run
     *
     * @throws \RuntimeException
     */
    public function run()
    {
        throw new \RuntimeException('Need implements "' . get_called_class() . '::run()" method to run');
    }

    /**
     * @return mixed|void
     */
    public function call()
    {
        $this->before();
        $this->run($this->input, $this->output);
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

