<?php

namespace Pagon\Config;

class Ini extends \Pagon\Config
{
    /**
     * Parse ini string
     *
     * @param string $ini
     * @return mixed|void
     */
    public function parse($ini)
    {
        $ini = parse_ini_string($ini);

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
}
