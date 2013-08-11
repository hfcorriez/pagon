<?php

use Pagon\Logger;

class LoggerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected $logger;

    public function setUp()
    {
        $this->logger = new Logger();
    }

    public function testVariables()
    {
        $token = $this->logger->token;
        $this->assertEquals(6, strlen($token));

        $new_token = $this->logger->token;
        $this->assertEquals($token, $new_token);
    }
}
