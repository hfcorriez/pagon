<?php

namespace OmniApp\Cli;

use OmniApp\App;
use OmniApp\Exception\Pass;
use OmniApp\Config;

class Input
{
    /**
     * @var array Route params
     */
    public $params = array();

    /**
     * @var \OmniApp\App App
     */
    public $app;

    /**
     * @var \OmniApp\Config Env
     */
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
        if (!isset($this->env['path'])) {
            $this->env['path'] = '/' . join('/', array_slice($this->env('argv'), 1));
        }

        return $this->env['path'];
    }

    /**
     * Get body
     *
     * @return string
     */
    public function body()
    {
        if (!isset($this->env['body'])) {
            $this->env['body'] = @(string)file_get_contents('php://input');
        }
        return $this->env['body'];
    }

    /**
     * Get any params from get or post
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }

    /**
     * Pass
     *
     * @throws Pass
     */
    public function pass()
    {
        ob_get_level() && ob_clean();
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
            return $this->env;
        }

        if ($key === null) return $this->env;

        return isset($this->env[$key]) ? $this->env[$key] : null;
    }
}