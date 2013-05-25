<?php
/**
 * ParserTest.php.
 */

namespace Pagon;


class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        file_put_contents('/tmp/test.json', '{"test":"abc"}');

        $config = Parser::load('/tmp/test.json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.json');
    }

    public function testParseGivenType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $config = Parser::load('/tmp/test.abc', 'json');

        $this->assertEquals(array('test' => 'abc'), $config);
        unlink('/tmp/test.abc');
    }

    public function testParseUnknownType()
    {
        file_put_contents('/tmp/test.abc', '{"test":"abc"}');

        $this->setExpectedException('InvalidArgumentException');
        Parser::load('/tmp/test.abc');

        unlink('/tmp/test.abc');
    }

    public function testDump()
    {
        $string = Parser::dump(array('test' => 'abc'), 'json');
        $this->assertEquals('{"test":"abc"}', $string);
    }

    public function testDumpUnknownType()
    {
        $this->setExpectedException('InvalidArgumentException');
        $string = Parser::dump(array('test' => 'abc'), 'abc');
    }

    public function testLoadUnknown()
    {
        $this->setExpectedException('InvalidArgumentException');
        Parser::load('/tmp/test.' . md5(microtime(true)));
    }
}
