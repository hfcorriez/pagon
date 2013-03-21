<?php

namespace Pagon\Route;

use Pagon\Utility\ArgParser;

class Cli extends \Pagon\Route
{
    protected $arguments = array();
    protected $usage = '';
    protected $params = array();

    /**
     * @return mixed|void
     */
    public function call()
    {
        $argv = $this->input->raw('argv');
        $arg_parser = new ArgParser(array_slice($argv, 1), $this->usage);
        $arg_parser->program($argv[0] . ' ' . (isset($argv[1]) ? $argv[1] : ''));

        foreach ($this->arguments as $arg => $options) {
            $arg_parser->add(strpos($arg, '|') ? explode('|', $arg) : $arg, $options);
        }

        if (!$this->params = $arg_parser->parse()) {
            $this->output->write($arg_parser->help());
            $this->output->end();
        }

        $this->before();
        $this->run();
        $this->after();
    }

    /**
     *
     */
    public function run()
    {
        throw new \RuntimeException('Method ' . get_called_class() . '->run() mast be implements');
    }
}
