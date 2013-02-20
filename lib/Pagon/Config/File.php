<?php

namespace Pagon\Config;

class File extends \Pagon\Config
{
    public function parse($input)
    {
        if (is_file($input)) {
            return include($input);
        }
        return array();
    }
}
