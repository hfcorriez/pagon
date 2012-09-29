<?php

namespace OmniApp;

abstract class View
{
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
     * @var string View module
     */
    protected static $view = 'Base';

    /**
     * Construct a view
     *
     * @param string $file
     * @param array  $params
     * @throws \Exception
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
            throw new \Exception('template file not exist: ' . $this->file);
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
        $view = self::getView();
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
     * @param $view
     */
    public static function setView($view)
    {
        static::$view = $view;
    }

    /**
     * Get view class
     *
     * @return string
     */
    public static function getView()
    {
        $view = self::$view;
        if ($view{0} != '\\') {
            $view = __NAMESPACE__ . '\\View\\' . self::$view;
        }
        return $view;
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

