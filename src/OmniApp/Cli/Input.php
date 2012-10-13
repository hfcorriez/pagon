<?php

namespace OmniApp\Cli;

use OmniApp\App;
use OmniApp\Exception\Pass;
use OmniApp\Config;

class Input
{
    public $app;

    protected $body;
    protected $path;
    protected $env;

    /**
     * @param \OmniApp\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->env = new Config($_SERVER);
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
            $this->path = '/' . join('/', array_slice($this->env('argv'), 1));
        }

        return $this->path;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function body()
    {
        if (null === $this->body) {
            $this->body = @(string)file_get_contents('php://input');
        }
        return $this->body;
    }

    /**
     * Pass
     *
     * @throws Pass
     */
    public function pass()
    {
        $this->app->cleanBuffer();
        throw new Pass();
    }

    /**
     * Env
     *
     * @param $key
     * @return mixed
     */
    public function env($key = null)
    {
        if (is_array($key)) {
            $this->env = new Config($key);
            return;
        }

        if ($key === null) return $this->env;

        return isset($this->env[$key]) ? $this->env[$key] : null;
    }
}