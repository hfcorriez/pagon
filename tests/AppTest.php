<?php

use Pagon\App;
use Pagon\Cli\Input;
use Pagon\Cli\Output;

class AppTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var App
     */
    protected $app;

    public function setUp()
    {
        $this->app = new App(array(
            'my'     => 'test',
            'en'     => false,
            'mt'     => array(
                'm' => 'a',
                't' => array(
                    't' => 'b'
                )
            ),
            'buffer' => false,
        ));
    }

    public function testInOut()
    {
        $this->assertTrue($this->app->input instanceof Input);
        $this->assertTrue($this->app->output instanceof Output);
    }

    public function testConfig()
    {
        $this->assertEquals('test', $this->app->get('my'));
        $this->assertEquals('test', $this->app->my);

        $this->assertEquals('a', $this->app->get('mt.m'));
        $this->assertEquals('b', $this->app->get('mt.t.t'));
    }

    public function testEnable()
    {
        $this->assertFalse($this->app->get('en'));
        $this->assertFalse($this->app->enabled('en'));
        $this->app->enable('en');
        $this->assertTrue($this->app->enabled('en'));

        $this->app->disable('en');
        $this->assertFalse($this->app->enabled('en'));
    }

    public function testParam()
    {
        $this->app->param(array('test' => 'abc'));

        $this->assertEquals('abc', $this->app->param('test'));
        $this->assertEquals(array('test' => 'abc'), $this->app->param());
    }
}
