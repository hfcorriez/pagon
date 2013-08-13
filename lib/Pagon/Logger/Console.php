<?php

namespace Pagon\Logger;

class Console extends LoggerInterface
{
    protected $options = array(
        'auto_write' => true
    );

    public function write()
    {
        print join(PHP_EOL, $this->formattedMessages()) . PHP_EOL;
    }
}