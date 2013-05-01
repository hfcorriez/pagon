<?php

use Pagon\Router;

class RouterTest extends AppTest
{
    /**
     * @var Router
     */
    protected $router;

    public function setUp()
    {
        parent::setUp();

        $this->router = $this->app->router;
    }

    public function testSetGet()
    {
        $closure = function ($req, $res) {
        };
        $this->router->set('/', $closure);

        $this->assertEquals($closure, $this->router->get('/'));
    }
}
