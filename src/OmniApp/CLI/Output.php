<?php

namespace OmniApp\CLI;

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
        if ($content !== null) self::write($content, true);

        return $this->body;
    }

    /**
     * Write body
     *
     * @param      $body
     * @param bool $replace
     * @return string
     */
    public function write($body, $replace = false)
    {
        if ($replace) {
            $this->body = $body;
        } else {
            $this->body .= (string)$body;
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