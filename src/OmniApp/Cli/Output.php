<?php

namespace OmniApp\Cli;

use OmniApp\App;
use OmniApp\Config;
use OmniApp\Exception\Stop;

class Output extends \OmniApp\BaseEmitter
{
    /**
     * @var array Local variables
     */
    public $locals = array();

    /**
     * @var App App
     */
    public $app;

    /**
     * @var Config Env
     */
    protected $env;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->env = new Config(array(
            'status' => 0,
            'body'   => '',
        ));

        $this->locals = & $this->app->locals;
    }

    /**
     * Set or get status
     *
     * @param null $status
     * @return int|Output
     */
    public function status($status = null)
    {
        if (is_numeric($status)) {
            $this->env['status'] = $status;
            return $this;
        }
        return $this->env['status'];
    }

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string|Output
     */
    public function body($content = null)
    {
        if ($content !== null) {
            $this->env['body'] = $content;
            return $this;
        }

        return $this->env['body'];
    }

    /**
     * Write body
     *
     * @param string $data
     * @return string|Output
     */
    public function write($data)
    {
        if (!$data) return $this->env['body'];

        $this->env['body'] .= $data;

        return $this;
    }

    /**
     * End the response
     *
     * @param string $data
     * @throws Stop
     * @return void
     */
    public function end($data = '')
    {
        $this->write($data);
        throw new Stop();
    }

    /**
     * Is ok?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->env['status'] === 0;
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->env['body'];
    }
}