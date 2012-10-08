<?php

namespace OmniApp;

abstract class View
{
    const _CLASS_ = __CLASS__;

    /**
     * @var string File path
     */
    protected $file;

    /**
     * @var array The data for view
     */
    protected $data = array();

    /**
     * @var string Directory for the template path
     */
    protected static $dir;

    /**
     * @var string Extension for file
     */
    protected static $ext = 'php';

    /**
     * Construct a view
     *
     * @param string $file
     * @param array  $params
     * @throws Exception
     * @return View
     */
    public function __construct($file, $params = array())
    {
        // Set file path
        if ($file{0} == '/') {
            $this->file = $file;
        } else {
            $this->file = static::$dir . '/' . strtolower(trim($file, '/')) . static::$ext;
        }

        // If file exists?
        if (!is_file($this->file)) {
            throw new Exception('Template file is not exist: ' . $this->file);
        }

        // Set data
        $this->data = $params;
    }

    /**
     * Create a new view
     *
     * @param       $file
     * @param array $params
     * @return mixed
     */
    public static function factory($file, $params = array())
    {
        $view = get_called_class();
        return new $view($file, $params);
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
     * @param string $dir
     */
    public static function setDir($dir)
    {
        static::$dir = $dir;
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
    abstract public function render();

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

