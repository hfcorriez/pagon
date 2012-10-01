<?php

namespace OmniApp\Config;

class Dict extends \OmniApp\Config
{
    public function parse($input)
    {
        return (array)$input;
    }
}
