<?php

/**
 * Rest route
 *
 * You can register route with get or other HTTP methods
 *
 *  $app->get('/', 'IndexRoute')
 *
 *  class IndexRoute extend Rest {
 *      function get() {
 *          // to do some thing
 *      }
 *  }
 *
 * Then you can visit
 *
 *  GET http://domain/
 *
 */

namespace Pagon\Route;

use Pagon\Route;

abstract class Rest extends Route
{
    protected $params;

    public function call()
    {
        $this->params = $this->input->params;
        $method = strtolower($this->input->method());

        /**
         * Check if exists `$method` of implements Route
         *
         * If not exists
         */
        if (method_exists($this, $method)) {
            $this->before();
            $this->$method($this->input, $this->output);
            $this->after();
        } else {
            $this->next();
        }
    }
}