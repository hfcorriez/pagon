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

        $mimes = include(dirname(__DIR__) . '/config/mimes.php');

        $this->assertInstanceOf('Pagon\Config', $config);
        $this->assertEquals(array('text/plain'), $config->txt);

        $this->assertEquals($mimes, $config->get());
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

        $this->assertEquals(array('test' => 'abc'), $config->get());

        unlink('/tmp/test.json');
    }

    public function testNonExistsFile()
    {
        Config::import('test', '/tmp/' . uniqid());

        $this->setExpectedException('InvalidArgumentException');

        Config::export('test');
    }

    public function testDump()
    {
        $config = new Config(array('test' => 'abc'));

        $string = $config->string('json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testLoadUnknown()
    {
        $this->setExpectedException('InvalidArgumentException');
        Config::load('/tmp/test.' . md5(microtime(true)));
    }

    public function testParse()
    {
        file_put_contents('/tmp/test.json', '{"test":"abc"}');

        $config = Config::from('/tmp/test.json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.json');
    }

    public function testParseGivenType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $config = Config::from('/tmp/test.abc', 'json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.abc');
    }

    public function testParsePHP()
    {
        file_put_contents('/tmp/test.php', '<?php return array("test" => "abc"); ?>');

        $config = Config::from('/tmp/test.php');

        $this->assertEquals(array('test' => 'abc'), $config);
    }

    public function testParseUnknownType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $this->setExpectedException('InvalidArgumentException');
        Config::load('/tmp/test.abc');

        unlink('/tmp/test.abc');
    }

    public function testString()
    {
        $string = Config::dump(array('test' => 'abc'), 'json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testDumpUnknownType()
    {
        $this->setExpectedException('InvalidArgumentException');
        $string = Config::dump(array('test' => 'abc'), 'abc');
    }
}
