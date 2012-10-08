<?php

namespace OmniApp\View;

class Base extends \OmniApp\View
{
    /**
     * Render
     *
     * @return string
     */
    public function render()
    {
        ob_start();
        if ($this->data) {
            extract((array)$this->data);
        }
        include($this->file);
        return ob_get_clean();
    }
}