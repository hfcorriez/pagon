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

/**
 * Classic base route
 *
 * @package Pagon\Route
 */
abstract class Classic extends Route
{
    public function call()
    {
        // Set params
        $this->params = $this->input->params;

        $action = $this->input->param('action');

        if (!$action) {
            throw new \RuntimeException('Route need ":action" param');
        }

        $method = 'action' . $action;

        // Check method
        $this->before();

        // Fallback call all
        if (!method_exists($this, $method) && method_exists($this, 'missing')) {
            $method = 'missing';
        }
        $this->$method($this->input, $this->output);
        $this->after();
    }
}