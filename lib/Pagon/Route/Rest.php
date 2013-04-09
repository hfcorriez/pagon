<?php

namespace Pagon\Route;

class Rest extends \Pagon\Route
{
    protected $params;

    public function run()
    {
        $this->params = $this->input->params;
        $method = strtolower($this->input->method());

        // Check method
        if (method_exists($this, $method)) {
            $this->$method($this->input, $this->output);
        } elseif ($this->next) {
            $this->next();
        } else {
            $this->app->handleError('404');
        }
    }
}