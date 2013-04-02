<?php

namespace Pagon\Parser;

use Symfony\Component\Yaml\Yaml as YamlParser;

class Yaml
{
    /**
     * Parser yaml
     *
     * @param string $yaml
     * @return array
     */
    public static function parse($yaml)
    {
        return YamlParser::parse($yaml);
    }

    /**
     * Dump array to yaml
     *
     * @param array $array
     * @return string
     */
    public static function dump(array $array)
    {
        return YamlParser::dump($array);
    }
}
