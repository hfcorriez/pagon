<?php

namespace Pagon\Middleware;

use Pagon\Exception\Pass;
use Pagon\Exception\Stop;
use Pagon\Middleware;

class PrettyException extends Middleware
{
    public function call()
    {
        try {
            $self = & $this;

            // Register crash event
            $this->input->app->on('crash', function ($error) use ($self) {
                $self->render(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            });
            // Next middleware
            $this->next();
        } catch (\Exception $e) {
            if ($e instanceof Pass || $e instanceof Stop) {
                throw $e;
            }
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
        // Check if buffer start
        if ($this->app->buffer) ob_clean();

        if ($e->getFile() === dirname(__DIR__) . '/Fiber.php') {
            $trace = $e->getTrace();
            $err = array_shift($trace);
            $vars = array(
                'file'    => $err['file'],
                'line'    => $err['line'],
                'code'    => $e->getCode(),
                'trace'   => $trace,
                'message' => 'Can not get non-exists injector "' . $err['args'][0] . '"',
                'type'    => $err['class'],
                'info'    => $e->getTraceAsString(),
            );
        } else {
            $vars = array(
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'code'    => $e->getCode(),
                'trace'   => $e->getTrace(),
                'message' => $e->getMessage(),
                'type'    => get_class($e),
                'info'    => $e->getTraceAsString()
            );
        }

        // Render the error
        $this->input->app->render('pagon/views/exception/' . ($this->app->isCli() ? 'cli' : 'web') . '.php', $vars);
    }
}
