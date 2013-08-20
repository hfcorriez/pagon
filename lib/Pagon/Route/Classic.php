<?php

/**
 * Classic route
 *
 * It's classic "controller/action" path mapping
 *
 * You can set route
 *
 *  $app->all('man/:action', 'ManController')
 *
 * Or
 *
 *  $app->autoRoute(function($path) use ($app) {
 *      list($ctrl, $act) = explode('/', $path);
 *      if (!$act) return false;
 *
 *      $app->param('action', $act);
 *      return ucfirst($ctrl);
 *  });
 *
 * Then add method to your 'ManController'
 *
 *  class ManController extend \Pagon\Route\Classic {
 *      function actionLogin() {
 *          // to do some thing
 *      }
 *  }
 *
 * Then you can visit
 *
 *  GET http://domain/man/login
 *
 */

namespace Pagon\Route;

use Pagon\Route;

abstract class Classic extends Route
{
    protected $params;

    public function call()
    {
        $action = $this->input->param('action');

        $this->params = $this->input->params;

        if (!$action) {
            throw new \RuntimeException('Route need ":action" param');
        }

        $method = 'action' . $action;

        // Check method
        $this->before();
        $this->$method($this->input, $this->output);
        $this->after();
    }
}