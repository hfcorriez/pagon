<?php

namespace Pagon\Logger;

class Console extends LoggerInterface
{
    protected $options = array(
        'auto_write' => true
    );

    public function write()
    {
        if (empty($this->messages)) return;

        print join("\n", $this->buildAll()) . "\n";
    }
}