<?php

namespace Pagon\Cli;

use Pagon\App;
use Pagon\Exception\Pass;
use Pagon\Config;

class Input extends \Pagon\EventEmitter
{
    /**
     * @var \Pagon\App App
     */
    public $app;

    /**
     * @param \Pagon\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        parent::__construct(array('param' => array()) + $_SERVER);
    }

    /**
     * Get path
     *
     *
     * @return mixed
     */
    public function pathInfo()
    {
        if (!isset($this->injectors['path_info'])) {
            $this->injectors['path_info'] = isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1]{0} != '-' ? $GLOBALS['argv'][1] : '';
        }

        return $this->injectors['path_info'];
    }

    /**
     * Get root of application
     *
     * @return string
     */
    public function root()
    {
        return getcwd();
    }

    /**
     * Get body
     *
     * @return string
     */
    public function body()
    {
        if (!isset($this->injectors['body'])) {
            $this->injectors['body'] = @(string)file_get_contents('php://input');
        }
        return $this->injectors['body'];
    }

    /**
     * Get any params from get or post
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return isset($this->injectors['param'][$key]) ? $this->injectors['param'][$key] : $default;
    }

    /**
     * Pass
     *
     * @throws Pass
     */
    public function pass()
    {
        ob_get_level() && ob_clean();
        throw new Pass();
    }
}