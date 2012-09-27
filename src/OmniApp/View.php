<?php

namespace OmniApp;

class View
{
    private $file = NULL;

    /**
     * Factory a view
     *
     * @static
     * @param $view
     * @return mixed|View
     */
    final public static function factory($view)
    {
        return new $view();
    }

    /**
     * Construct a view
     *
     * @param $view
     * @throws \Exception
     */
    public function __construct($view)
    {
        if (!App::$config['views']) throw new \Exception('No views found.');
        $this->file = App::$config['views'] . '/' . strtolower(trim($view, '/')) . '.php';
        if (!is_file($this->file)) throw new \Exception('View file not exist: ' . $this->file);
    }

    /**
     * Set variable for view
     *
     * @param $array
     * @return View
     */
    public function set($array)
    {
        foreach ($array as $key => $value) $this->$key = $value;
        return $this;
    }

    public function render()
    {
        ob_start();
        extract((array)$this);
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
        $this->$key = $value;
    }

    /**
     * Get assign value for view
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     * Support output
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}

