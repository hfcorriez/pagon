<?php

namespace Pagon\Route;

class Rest extends \Pagon\Route
{
    public function run()
    {
        $method = strtolower($this->input->method());

        // Check method
        if (method_exists($this, $method)) {
            $this->$method();
        } elseif ($this->next) {
            $this->next();
        } else {
            $this->app->notFound();
        }
    }
}