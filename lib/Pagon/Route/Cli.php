<?php

namespace Pagon\Route;

use Pagon\Route;
use Pagon\Utility\ArgParser;

abstract class Cli extends Route
{
    protected $arguments = array();
    protected $usage = '';
    protected $params = array();

    /**
     * @throws \RuntimeException
     * @return mixed|void
     */
    public function call()
    {
        if (!$this->app->cli()) {
            throw new \RuntimeException("Daemon route can used under the CLI mode only!");
        }

        $argv = $this->injectors['input']->server('argv');
        $arg_parser = new ArgParser(array_slice($argv, 1), $this->usage);
        $arg_parser->program($argv[0] . ' ' . (isset($argv[1]) ? $argv[1] : ''));

        foreach ($this->arguments as $arg => $options) {
            $arg_parser->add(strpos($arg, '|') ? explode('|', $arg) : $arg, $options);
        }

        if (!$this->params = $arg_parser->parse()) {
            $this->injectors['output']->write($arg_parser->help());
            $this->injectors['output']->end();
        }

        $this->before();
        $this->run($this->injectors['input'], $this->injectors['output']);
        $this->after();
    }
}
