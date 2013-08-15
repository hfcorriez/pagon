<?php

use Pagon\Router;

class RouterTest extends AppTest
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Router
     */
    protected $test_router;

    public function setUp()
    {
        parent::setUp();

        $this->router = $this->app->router;

        $this->test_router = new Router(array('path' => '/test'));
        $this->test_router->app = $this->app;
    }

    public function testAdd()
    {
        $closure = function ($req, $res) {
        };
        $this->router->add('/', $closure);
        $this->router->add('/', $closure);

        $this->assertEquals(array($closure, $closure), $this->app->routes['/']);
    }

    public function testVia()
    {
        $closure = function ($req, $res) {
        };
        $this->router->add('/', $closure)->via('*');
        $this->assertEquals(array($closure, 'via' => array()), $this->app->routes['/']);
    }

    public function testRules()
    {
        $closure = function ($req, $res) {
        };
        $this->router->add('/:test', $closure)->rules(array('test' => '[a-z]+'));
        $this->assertEquals(array($closure, 'rules' => array('test' => '[a-z]+')), $this->app->routes['/:test']);
    }

    public function testDefaults()
    {
        $closure = function ($req, $res) {
        };
        $this->router->add('/:test', $closure)->defaults(array('test' => 'a'));
        $this->assertEquals(array($closure, 'defaults' => array('test' => 'a')), $this->app->routes['/:test']);
    }

    public function testPass()
    {
        $run = false;
        $this->test_router->pass(array(function () use (&$run) {
            $run = true;
        }), function ($route) {
            return $route;
        });

        $this->assertEquals(true, $run);
    }

    public function testRun()
    {
        $run = false;
        $this->test_router->run(array(function () use (&$run) {
            $run = true;
        }));

        $this->assertEquals(true, $run);
    }
}
