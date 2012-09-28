<?php

namespace OmniApp\Middleware;

use OmniApp\Http\Response;
use OmniApp\App;

class Debug extends \OmniApp\Middleware
{
    public function call()
    {
        try {
            $this->next->call();
        } catch (\Exception $e) {
            App::render(__DIR__ . '/views/error', array(
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'code'    => $e->getCode(),
                'trace'   => $e->getTrace(),
                'message' => $e->getMessage(),
                'type'    => get_class($e)
            ));
        }
    }
}