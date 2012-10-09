<?php

namespace OmniApp\Cli;

class Output
{
    protected $body;
    protected $status = 0;

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
        if ($content !== null) self::write($content, 0);

        return $this->body;
    }

    /**
     * Write body
     *
     * @param string $body
     * @param int    $pos
     * @return string
     */
    public function write($body, $pos = 1)
    {
        if (!$body) return $this->body;

        if ($pos === 1) {
            $this->body .= $body;
        } elseif ($pos === -1) {
            $this->body = $body . $this->body;
        } else {
            $this->body = $body;
        }

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