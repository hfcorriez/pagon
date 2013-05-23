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

    public function testDump()
    {
        $config = new Config(array('test' => 'abc'));

        $string = $config->dump('json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testLoadUnknown()
    {
        $this->setExpectedException('InvalidArgumentException');
        Config::load('/tmp/test.' . md5(microtime(true)));
    }
}
