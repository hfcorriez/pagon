<?php
/**
 * ConfigTest.php.
 */

namespace Pagon;


class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testExportDefaults()
    {
        $config = Config::export('mimes');

        $mimes = include(dirname(__DIR__) . '/lib/Pagon/Config/mimes.php');

        $this->assertInstanceOf('Pagon\Config', $config);
        $this->assertEquals(array('text/plain'), $config->txt);

        $this->assertEquals($mimes, $config->raw());
    }

    public function testExportUnknown()
    {
        $this->setExpectedException('InvalidArgumentException');

        Config::export('abc');
    }

    public function testExportInstance()
    {
        $config = Config::export('mimes');

        $config1 = Config::export('mimes');

        $this->assertEquals($config, $config1);
    }

    public function testImport()
    {
        file_put_contents('/tmp/test.json', '{"test":"abc"}');

        Config::import('test', '/tmp/test.json');

        $config = Config::export('test');

        $this->assertEquals(array('test' => 'abc'), $config->raw());

        unlink('/tmp/test.json');
    }

    public function testParse()
    {
        file_put_contents('/tmp/test.json', '{"test":"abc"}');

        $config = Config::parse('/tmp/test.json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.json');
    }

    public function testParseGivenType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $config = Config::parse('/tmp/test.abc', 'json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.abc');
    }

    public function testParseUnknownType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $this->setExpectedException('InvalidArgumentException');
        Config::parse('/tmp/test.abc');

        unlink('/tmp/test.abc');
    }

    public function testDump()
    {
        $string = Config::dump(array('test' => 'abc'), 'json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testDumpUnknownType()
    {
        $this->setExpectedException('InvalidArgumentException');
        $string = Config::dump(array('test' => 'abc'), 'abc');
    }

    public function testDumpTo()
    {
        $config = new Config(array('test' => 'abc'));

        $string = $config->dumpTo('json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testLoadUnknown()
    {
        $this->setExpectedException('InvalidArgumentException');
        Config::load('/tmp/test.' . md5(microtime(true)));
    }
}
