<?php
namespace Pagon;

class EventEmitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventEmitter
     */
    protected $event;

    public function setUp()
    {
        $this->event = new EventEmitter();
    }

    public function tearDown()
    {
        $GLOBALS = array();
    }

    public function testSimple()
    {
        $closure = function () {
        };

        $this->event->on('test', $closure);

        $this->assertEquals(array($closure), $this->event->listeners('test'));
    }

    public function testEmit()
    {
        $GLOBALS['emit_result'] = 0;
        $GLOBALS['emit_arg'] = '';
        $closure = function ($arg) {
            $GLOBALS['emit_result'] = 1;
            $GLOBALS['emit_arg'] = $arg;
        };

        $this->event->on('test', $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->assertEquals('', $GLOBALS['emit_arg']);
        $this->event->emit('test', 'go');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->assertEquals('go', $GLOBALS['emit_arg']);
    }

    public function testOnce()
    {
        $GLOBALS['emit_result'] = 0;
        $closure = function () {
            $GLOBALS['emit_result']++;
        };

        $this->event->once('test', $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->event->emit('test');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('test');
        $this->assertEquals(1, $GLOBALS['emit_result']);
    }

    public function testMany()
    {
        $GLOBALS['emit_result'] = 0;
        $closure = function () {
            $GLOBALS['emit_result']++;
        };

        $this->event->many('test', 2, $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->event->emit('test');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('test');
        $this->assertEquals(2, $GLOBALS['emit_result']);
        $this->event->emit('test');
        $this->assertEquals(2, $GLOBALS['emit_result']);
    }

    public function testPattern()
    {
        $GLOBALS['emit_result'] = '';
        $GLOBALS['emit_count'] = 0;
        $closure = function ($event) {
            $GLOBALS['emit_result'] = $event;
            $GLOBALS['emit_count']++;
        };

        $this->event->on('test.*', $closure);

        $this->assertEquals('', $GLOBALS['emit_result']);
        $this->assertEquals(0, $GLOBALS['emit_count']);
        $this->event->emit('test.1');
        $this->assertEquals('test.1', $GLOBALS['emit_result']);
        $this->assertEquals(1, $GLOBALS['emit_count']);
        $this->event->emit('test.2');
        $this->assertEquals('test.2', $GLOBALS['emit_result']);
        $this->assertEquals(2, $GLOBALS['emit_count']);
        $this->event->emit('test');
        $this->assertEquals('test.2', $GLOBALS['emit_result']);
        $this->assertEquals(2, $GLOBALS['emit_count']);
    }

    public function testMulti()
    {
        $GLOBALS['emit_result'] = 0;
        $closure = function () {
            $GLOBALS['emit_result']++;
        };

        $this->event->on(array('a', 'b', 'c'), $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('c');
        $this->assertEquals(2, $GLOBALS['emit_result']);
        $this->event->emit('b');
        $this->assertEquals(3, $GLOBALS['emit_result']);
    }

    public function testOff()
    {
        $closure = function () {
        };
        $closure1 = function () {
        };

        $this->event->addListener('test', $closure);
        $this->event->addListener('test', $closure1);

        $this->assertEquals(array($closure, $closure1), $this->event->listeners('test'));

        $this->event->removeListener('test', $closure);

        $this->assertEquals(array(1 => $closure1), $this->event->listeners('test'));
    }

    public function testRemoveAll()
    {
        $closure = function () {
        };
        $closure1 = function () {
        };

        $this->event->on('test', $closure);
        $this->event->on('test', $closure1);
        $this->event->on('test1', $closure1);

        $this->assertEquals(array($closure, $closure1), $this->event->listeners('test'));
        $this->assertEquals(array($closure1), $this->event->listeners('test1'));

        $this->event->removeAllListeners('test');

        $this->assertEquals(array(), $this->event->listeners('test'));

        $this->event->removeAllListeners();

        $this->assertEquals(array(), $this->event->listeners('test1'));
    }

    public function testOnceMulti()
    {
        $GLOBALS['emit_result'] = 0;
        $closure = function () {
            $GLOBALS['emit_result']++;
        };

        $this->event->once(array('a', 'b'), $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('b');
        $this->assertEquals(2, $GLOBALS['emit_result']);
        $this->event->emit('b');
        $this->assertEquals(2, $GLOBALS['emit_result']);
    }

    public function testManyMulti()
    {
        $GLOBALS['emit_result'] = 0;
        $closure = function () {
            $GLOBALS['emit_result']++;
        };

        $this->event->many(array('a', 'b'), 2, $closure);

        $this->assertEquals(0, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(1, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(2, $GLOBALS['emit_result']);
        $this->event->emit('a');
        $this->assertEquals(2, $GLOBALS['emit_result']);
        $this->event->emit('b');
        $this->assertEquals(3, $GLOBALS['emit_result']);
        $this->event->emit('b');
        $this->assertEquals(4, $GLOBALS['emit_result']);
    }

    public function testOffMulti()
    {
        $closure = function () {
        };
        $closure1 = function () {
        };

        $this->event->on(array('a', 'b'), $closure);
        $this->event->addListener(array('a', 'b'), $closure1);

        $this->assertEquals(array($closure, $closure1), $this->event->listeners('a'));
        $this->assertEquals(array($closure, $closure1), $this->event->listeners('b'));

        $this->event->off(array('a', 'b'), $closure);

        $this->assertEquals(array(1 => $closure1), $this->event->listeners('a'));
        $this->assertEquals(array(1 => $closure1), $this->event->listeners('b'));
    }
}
