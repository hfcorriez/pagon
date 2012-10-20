<?php

namespace OmniApp\Cli;

use OmniApp\App;
use OmniApp\Config;

class Output
{
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

        $this->env = new Config(array(
            'status'       => 0,
            'body'         => '',
        ));
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
            if (ob_get_level() !== 0) {
                ob_end_clean();
                ob_start();
            }
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

        if (ob_get_level() !== 0) {
            $data = ob_get_clean() . $data;
            ob_start();
        }

        $this->env['body'] .= $data;

        return $this;
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
}