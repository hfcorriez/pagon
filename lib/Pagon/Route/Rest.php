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

use Pagon\Http\Input;
use Pagon\Http\Output;
use Pagon\Route;

/**
 * Rest base route
 *
 * @package Pagon\Route
 * @method get(Input $input, Output $output)
 * @method post(Input $input, Output $output)
 * @method put(Input $input, Output $output)
 * @method delete(Input $input, Output $output)
 */
abstract class Rest extends Route
{
    protected $params;

    /**
     * @throws \RuntimeException
     */
    public function call()
    {
        if ($this->app->cli()) {
            throw new \RuntimeException("Daemon route can not use under the CLI mode!");
        }

        $this->params = $this->input->params;
        $method = strtolower($this->input->method());

        $this->before();
        $this->$method($this->input, $this->output);
        $this->after();
    }
}