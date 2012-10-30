<?php

namespace OmniApp\Middleware;

use OmniApp\Middleware;

class PrettyException extends Middleware
{
    public function call()
    {
        try {
            $self = & $this;
            // Register crash event
            $this->input->app->emitter->on('crash', function ($error) use ($self) {
                $self->render(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            });
            // Next middleware
            $this->next();
        } catch (\Exception $e) {
            // Catch exception and render
            $this->render($e);
        }
    }

    /**
     * Render exception
     *
     * @param \Exception $e
     */
    public function render(\Exception $e)
    {
        ob_clean();
        $this->input->app->render(__DIR__ . '/views/error.php', array(
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTrace(),
            'message' => $e->getMessage(),
            'type'    => get_class($e)
        ));
    }
}