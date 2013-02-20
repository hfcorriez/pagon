<?php

namespace Pagon\Config;

class Ini extends \Pagon\Config
{
    /**
     * Parse ini string
     *
     * @param array $ini
     * @return mixed|void
     */
    public function parse(array $ini)
    {
        foreach ($ini as $key => $value) {
            $config = & $this->config;
            $namespaces = explode('.', $key);
            foreach ($namespaces as $namespace) {
                if (!isset($config[$namespace])) $config[$namespace] = array();
                $config = & $config[$namespace];
            }
            $config = $value;
        }
    }

    /**
     * Load from string
     *
     * @param $string
     * @return Ini
     */
    public static function fromString($string)
    {
        return new self(parse_ini_string($string));
    }

    /**
     * Load from file
     *
     * @param $file
     * @return Ini
     */
    public static function fromFile($file)
    {
        return new self(parse_ini_file($file));
    }
}
