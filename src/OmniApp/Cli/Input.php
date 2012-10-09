<?php

namespace OmniApp\Cli;

class Input
{
    protected $path;

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
}