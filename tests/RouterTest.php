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

        $this->test_router = new Router(array('path' => '/test', 'app' => $this->app));
    }

    public function testAdd()
    {
        $closure = function ($req, $res) {
        };
        $this->router->map('/', $closure);
        $this->router->map('/', $closure);

        $this->assertEquals(
            array(array('path' => '/', 'route' => $closure, 'via' => null), array('path' => '/', 'route' => $closure, 'via' => null)),
            $this->app->routes
        );
    }

    public function testVia()
    {
        $closure = function ($req, $res) {
        };
        $this->router->map('/', $closure, 'GET');
        $this->assertEquals(
            array('path' => '/', 'route' => $closure, 'via' => array('GET')),
            end($this->app->routes)
        );
    }

    public function testRules()
    {
        $closure = function ($req, $res) {
        };
        $this->router->map('/:test', $closure)->rules(array('test' => '[a-z]+'));
        $this->assertEquals(
            array('path' => '/:test', 'route' => $closure, 'via' => null, 'rules' => array('test' => '[a-z]+')),
            end($this->app->routes)
        );
    }

    public function testDefaults()
    {
        $closure = function ($req, $res) {
        };
        $this->router->map('/:test', $closure)->defaults(array('test' => 'a'));
        $this->assertEquals(
            array('path' => '/:test', 'route' => $closure, 'via' => null, 'defaults' => array('test' => 'a')),
            end($this->app->routes)
        );
    }

    public function testHandle()
    {
        $run = false;
        $this->test_router->handle(array(function () use (&$run) {
            $run = true;
        }), function ($route) {
            return $route;
        });

        $this->assertEquals(true, $run);
    }

    public function testRun()
    {
        $run = false;
        $run = $this->test_router->run(function () use (&$run) {
            $run = true;
        });

        $this->assertEquals(true, $run);
    }
}
