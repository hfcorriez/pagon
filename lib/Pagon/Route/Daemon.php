<?php

namespace Pagon\Route;

use Pagon\Exception\Stop;

class Daemon extends \Pagon\Route
{
    protected $sleep_time = 1000000;

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
     * @throws \Pagon\Exception\Stop
     */
    public function stop()
    {
        throw new DaemonStop();
    }

    /**
     *
     */
    public function run()
    {
        throw new \RuntimeException('Method ' . get_called_class() . '->run() mast be implements');
    }
}

class DaemonStop extends Stop
{
}
