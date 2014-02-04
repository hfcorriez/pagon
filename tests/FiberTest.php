<?php

use Pagon\Fiber;

class FiberTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Fiber
     */
    protected $di;

    public function setUp()
    {
        $this->di = new Fiber(array(
            'a' => 'a'
        ));
    }

    public function testGet()
    {
        $this->assertTrue(isset($this->di->a));
        $this->assertEquals('a', $this->di->a);
        unset($this->di->a);
        $this->assertFalse(isset($this->di->a));

        $this->assertFalse(isset($this->di->b));
    }

    public function testSet()
    {
        $this->di->b = 'b';
        $this->assertEquals('b', $this->di->b);
    }

    public function testShareBare()
    {
        $obj = function () {
            static $i = 0;
            return ++$i;
        };

        $this->di->obj = $obj;
        $value = $this->di->obj;

        $this->assertEquals($value, $this->di->obj);
        $this->assertNotEquals($obj, $this->di->obj);
    }

    public function testShare()
    {
        $this->di->share('s', function () {
            return rand();
        });

        $first = $this->di->s;
        $this->assertEquals($first, $this->di->s);
    }

    public function testExtend()
    {
        $this->di->e = function () {
            return 'extend';
        };

        $this->di->extend('e', function ($i) {
            return array($i);
        });

        $this->assertEquals(array('extend'), $this->di->e);
    }

    public function testExtendNonExists()
    {
        $this->di->extend('b', function ($b) {
            return $b ? 'exists' : 'non-exists';
        });
        $this->assertEquals('non-exists', $this->di->b);
    }

    public function testInject()
    {
        $this->di->inject('md5', function ($key) {
            return md5($key);
        });

        $this->assertEquals(md5('a'), $this->di->md5('a'));
    }

    public function testInjectBadCall()
    {
        $this->di->md6 = 'md6';

        $this->setExpectedException('BadMethodCallException');
        $this->di->md6('a');
    }

    public function testRaw()
    {
        $this->assertEquals('a', $this->di->get('a'));

        $closure = function () {
        };

        $this->di->set('b', $closure);

        $this->assertEquals($closure, $this->di->get('b'));
    }

    public function testKeys()
    {
        $this->assertEquals(array('a'), $this->di->keys());
    }

    public function testAppend()
    {
        $this->di->append(array('b' => 'b'));

        $this->assertEquals(array('b', 'a'), $this->di->keys());
    }

    public function testGetWithSub()
    {
        $this->assertTrue(isset($this->di['a']));
        $this->assertEquals('a', $this->di['a']);
        unset($this->di['a']);
        $this->assertFalse(isset($this->di['a']));
    }

    public function testSetWithSub()
    {
        $this->di['b'] = 'b';
        $this->assertEquals('b', $this->di['b']);
    }
}
