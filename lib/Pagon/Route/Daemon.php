<?php

namespace Pagon\Route;

use Pagon\Exception\Stop;
use Pagon\Route;

abstract class Daemon extends Route
{
    protected $sleep_time = 1000000;

    /**
     * @throws \RuntimeException
     */
    public function call()
    {
        if (!$this->app->isCli()) {
            throw new \RuntimeException("Daemon route can used under the CLI mode only!");
        }

        $this->before();
        try {
            while (1) {
                // Run
                $this->run($this->input, $this->output);

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
