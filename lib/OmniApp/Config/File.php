<?php

namespace OmniApp\Config;

class File extends \OmniApp\Config
{
    public function parse($input)
    {
        if (is_file($input)) {
            return include($input);
        }
        return array();
    }
}
