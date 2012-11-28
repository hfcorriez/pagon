<?php

namespace OmniApp\Cli;

use OmniApp\Exception\Stop;

class Daemon extends \OmniApp\Route
{
    protected $sleep_time = 0;

    /**
     * Re construct the call
     */
    public function call()
    {
        $this->before();
        try {
            while (1) {
                // Run
                $this->run();

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
     * @throws \OmniApp\Exception\Stop
     */
    public function stop()
    {
        throw new DaemonStop();
    }
}

class DaemonStop extends Stop
{
}
