<?php

namespace OmniApp;

class View
{
    const _CLASS_ = __CLASS__;

    /**
     * @var string File path
     */
    protected $path;

    /**
     * @var array The data for view
     */
    protected $data = array();

    /**
     * @var string Directory for the template path
     */
    protected $dir;

    /**
     * @var Object
     */
    protected $engine;

    /**
     * Create a new view
     *
     * @param string $path
     * @param array  $data
     * @return mixed
     */
    public static function factory($path, $data = array())
    {
        $view = get_called_class();
        return new $view($path, $data);
    }

    /**
     * Construct a view
     *
     * @param string $path
     * @param array  $data
     * @param array  $opt
     * @throws \Exception
     * @return View
     */
    public function __construct($path, $data = array(), $opt = array())
    {
        // Set dir for the view
        if (isset($opt['dir'])) $this->dir = $opt['dir'];

        // Set path
        $this->path = $this->dir . ($path{0} == '/' ? '' : '/') . $path;

        // If file exists?
        if (!is_file($this->path)) {
            // Set file path
            if ($path{0} == '/' && is_file($path)) {
                $this->path = $path;
            } else {
                throw new \Exception('Template file is not exist: ' . $this->path);
            }
        }

        // Set engine for the view
        if (isset($opt['engine'])) $this->engine = $opt['engine'];

        // Set data
        $this->data = $data;
    }

    /**
     * Set engine
     *
     * @param $engine
     * @return \OmniApp\View
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Set path
     *
     * @param string $dir
     * @return View
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * Set variable for view
     *
     * @param array $array
     * @return View
     */
    public function set(array $array = array())
    {
        $this->data = $array + $this->data;
        return $this;
    }

    /**
     * render
     *
     * @return string
     */
    public function render()
    {
        if (!$this->engine) {
            ob_start();
            if ($this->data) {
                extract((array)$this->data);
            }
            include($this->path);
            return ob_get_clean();
        }

        return $this->engine->render($this->path, $this->data);
    }

    /**
     * Assign value for view
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get assign value for view
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Is-set data?
     *
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Un-set data
     *
     * @param $key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Output object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}

