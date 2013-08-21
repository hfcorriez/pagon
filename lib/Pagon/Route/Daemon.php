<?php

namespace Pagon\Route;

use Pagon\Cli\Input;
use Pagon\Cli\Output;
use Pagon\Exception\Stop;
use Pagon\Route;

/**
 * Daemon base route
 *
 * @package Pagon\Route
 * @method run(Input $input, Output $output)
 */
abstract class Daemon extends Route
{
    protected $sleep_time = 1000000;

    /**
     * @throws \RuntimeException
     */
    public function call()
    {
        if (!$this->app->cli()) {
            throw new \RuntimeException("Daemon route can used under the CLI mode only!");
        }

        $this->before();
        try {
            // Fallback call all
            if (!method_exists($this, $method = 'run') && method_exists($this, 'all')) {
                $method = 'all';
            }

            while (1) {
                // Run
                $this->$method($this->input, $this->output);

                // Sleep after run
                if ($this->sleep_time) usleep($this->sleep_time);
            }
        } catch (DaemonStop $e) {
        }
        $this->after();
    }

    /**
     * Stop the daemon
     *
     * @throws \Pagon\Exception\Stop
     */
    public function stop()
    {
        throw new DaemonStop();
    }
}

class DaemonStop extends Stop
{
}
