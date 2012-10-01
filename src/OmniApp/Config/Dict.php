<?php

namespace OmniApp\Config;

class Dict extends \OmniApp\Config
{
    public function parse($array)
    {
        return (array)$array;
    }
}
