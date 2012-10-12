<?php

namespace OmniApp\Cli;

use OmniApp\App;

class Input
{
    public $app;

    protected $path;
    protected $env;

    /**
     * @param \OmniApp\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->env = $_SERVER;
    }

    /**
     * Get path
     *
     *
     * @return mixed
     */
    public function path()
    {
        if (null === $this->path) {
            $this->path = '/' . join('/', array_slice($GLOBALS['argv'], 1));
        }

        return $this->path;
    }

    /**
     * Env
     *
     * @param $key
     * @return null
     */
    public function env($key = null)
    {
        if (is_array($key)) {
            $this->env = $key;
            return;
        }

        if ($key === null) return $this->env;

        return isset($this->env[$key]) ? $this->env[$key] : null;
    }
}