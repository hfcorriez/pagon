<?php

namespace OmniApp\Cli;

use OmniApp\App;

class Output
{
    public $app;

    protected $body;
    protected $status = 0;

    /**
     * @param \OmniApp\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Set or get status
     *
     * @param null $status
     * @return int|string
     */
    public function status($status = null)
    {
        if (is_numeric($status)) {
            $this->status = $status;
        }
        return $this->status;
    }

    /**
     * Set body
     *
     * @static
     * @param string $content
     * @return string
     */
    public function body($content = null)
    {
        if ($content !== null) {
            if (ob_get_level() !== 0) {
                ob_end_clean();
                ob_start();
            }
            $this->body = $content;
        }

        return $this->body;
    }

    /**
     * Write body
     *
     * @param string $data
     * @return string
     */
    public function write($data)
    {
        if (!$data) return $this->body;

        if (ob_get_level() !== 0) {
            $data = ob_get_clean() . $data;
            ob_start();
        }

        $this->body .= $data;

        return $this->body;
    }

    /**
     * Is ok?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->status === 0;
    }
}