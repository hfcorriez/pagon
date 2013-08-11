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

    /**
     * @throws \RuntimeException
     */
    public function call()
    {
        if ($this->app->isCli()) {
            throw new \RuntimeException("Daemon route can not use under the CLI mode!");
        }

        $this->params = $this->input->params;
        $method = strtolower($this->input->method());

        $this->before();
        $this->$method($this->input, $this->output);
        $this->after();
    }
}