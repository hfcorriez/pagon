<?php

namespace Pagon\Config;

class Json
{
    /**
     * Parse ini string
     *
     * @param string $json
     * @return array
     */
    public static function parse($json)
    {
        return json_decode($json, true);
    }

    /**
     * Build String from array
     *
     * @param array $json
     * @return string
     */
    public static function dump(array $json)
    {
        return json_encode($json);
    }
}
