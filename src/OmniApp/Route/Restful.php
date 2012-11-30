<?php

namespace OmniApp\Route;

class Restful extends \OmniApp\Route
{
    public function run()
    {
        $method = strtolower($this->input->method());

        // Check method
        if (method_exists($this, $method)) {
            $this->$method();
        } elseif ($this->getNext()) {
            $this->next();
        } else {
            $this->app->notFound();
        }
    }
}