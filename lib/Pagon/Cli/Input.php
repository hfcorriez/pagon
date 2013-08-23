<?php

namespace Pagon\Cli;

use Pagon\App;
use Pagon\EventEmitter;
use Pagon\Exception\Pass;
use Pagon\Config;

/**
 * Cli Input
 *
 * @package Pagon\Cli
 * @property \Pagon\App app     Application to service
 * @property array      params
 */
class Input extends EventEmitter
{
    /**
     * @var \Pagon\App
     */
    public $app;

    /**
     * @param array $injectors
     */
    public function __construct(array $injectors = array())
    {
        parent::__construct($injectors + array('params' => array(), 'app' => null) + $_SERVER);

        $this->app = & $this->injectors['app'];
    }

    /**
     * Get or set id
     *
     * @param string|\Closure $id
     * @return mixed
     */
    public function id($id = null)
    {
        if (!isset($this->injectors['id'])) {
            $this->injectors['id'] = $id ? ($id instanceof \Closure ? $id() : (string)$id) : sha1(uniqid());
        }
        return $this->injectors['id'];
    }

    /**
     * Get path
     *
     *
     * @return mixed
     */
    public function path()
    {
        if (!isset($this->injectors['path_info'])) {
            if (!empty($GLOBALS['argv'])) {
                $argv = $GLOBALS['argv'];
                array_shift($argv);
                $this->injectors['path_info'] = join(' ', $argv);
            } else {
                $this->injectors['path_info'] = '';
            }
        }

        return $this->injectors['path_info'];
    }

    /**
     * Get method
     *
     * @return string
     */
    public function method()
    {
        return 'CLI';
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
        return isset($this->injectors['params'][$key]) ? $this->injectors['params'][$key] : $default;
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