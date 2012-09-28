<?php

namespace OmniApp;

class View
{
    private $file;
    private $data = array();

    protected static $path;
    protected static $ext = 'php';

    /**
     * Construct a view
     *
     * @param string $view
     * @param array  $params
     * @throws \Exception
     * @internal param \Exception $
     * @return View
     */
    public function __construct($view, $params = array())
    {
        $this->file = trim(static::$path) . '/' . strtolower(trim($view, '/')) . '.' . static::$ext;
        if (!is_file($this->file)) throw new \Exception('View file not exist: ' . $this->file);
        $this->data = $params;
    }

    /**
     * Set ext
     *
     * @param string $ext
     * @return void
     */
    public static function setExt($ext)
    {
        static::$ext = $ext;
    }

    /**
     * Set path
     *
     * @param string $path
     */
    public static function setPath($path)
    {
        static::$path = $path;
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
        ob_start();
        extract((array)$this->data);
        include($this->file);
        return ob_get_clean();
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

