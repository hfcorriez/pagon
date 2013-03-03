<?php

namespace Pagon\Cli;

use Pagon\App;
use Pagon\Exception\Pass;
use Pagon\Config;

class Input extends \Pagon\EventEmitter
{
    /**
     * @var array Route params
     */
    public $params = array();

    /**
     * @var \Pagon\App App
     */
    public $app;

    /**
     * @var \Pagon\Config Env
     */
    protected $env;

    /**
     * @param \Pagon\App $app
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
    public function pathInfo()
    {
        if (!isset($this->env['path_info'])) {
            $this->env['path_info'] = isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : '';
        }

        return $this->env['path_info'];
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public function root()
    {
        return getcwd();
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