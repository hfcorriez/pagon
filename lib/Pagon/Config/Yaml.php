<?php

namespace Pagon\Config;

use Symfony\Component\Yaml\Yaml as YamlParser;

class Yaml extends \Pagon\Config
{
    public function parse($yaml)
    {
        return YamlParser::parse($yaml);
    }
}
