<?php

namespace Pagon;

use Pagon\Http\Input;
use Pagon\Http\Output;

/**
 * Route
 * structure of base route
 *
 * @package Pagon
 * @method run(Input $input, Output $output)
 */
abstract class Route extends Middleware
{
    /**
     * abstract before run
     *
     * @abstract
     */
    protected function before()
    {
        // Implements if you need
    }

    /**
     * abstract after run
     *
     * @abstract
     */
    protected function after()
    {
        // Implements if you need
    }

    /**
     * @return mixed|void
     */
    public function call()
    {
        $this->before();
        // Fallback call all
        if (!method_exists($this, 'run') && method_exists($this, 'missing')) {
            call_user_func(array($this, 'missing'), $this->input, $this->output);
        } else {
            $this->run($this->input, $this->output);
        }
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

