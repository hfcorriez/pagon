<?php

namespace Pagon;

/**
 * View
 * base view and manage render
 *
 * @package Pagon
 */
class View extends EventEmitter
{
    const _CLASS_ = __CLASS__;

    /**
     * Is Rendering now?
     *
     * @var bool
     */
    public static $rendering = false;

    /**
     * @var string File path
     */
    protected $path;

    /**
     * @var array
     */
    protected $options = array(
        'dir'    => '',
        'engine' => null,
    );

    /**
     * Construct a view
     *
     * @param string $path
     * @param array  $data
     * @param array  $options
     * @throws \Exception
     * @return View
     */
    public function __construct($path, $data = array(), $options = array())
    {
        // Set dir for the view
        $this->options = $options + $this->options;

        // Set path
        $this->path = ltrim($path, '/');

        // If file exists?
        if (!is_file($options['dir'] . '/' . $this->path)) {
            // Try to load file from absolute path
            if ($path{0} == '/' && is_file($path)) {
                $this->path = $path;
                $this->options['dir'] = '';
            } else {
                throw new \Exception('Template file is not exist: ' . $this->path);
            }
        }

        // Set data
        parent::__construct($data);
    }

    /**
     * Set engine
     *
     * @param $engine
     * @return \Pagon\View
     */
    public function setEngine($engine)
    {
        $this->options['engine'] = $engine;
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
        $this->options['dir'] = $dir;
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
        return $this->append($array);
    }

    /**
     * render
     *
     * @return string
     */
    public function render()
    {
        $engine = $this->options['engine'];

        // Mark rendering flag
        self::$rendering = true;

        $this->emit('render');

        if (!$engine) {
            if ($this->injectors) {
                extract((array)$this->injectors);
            }
            ob_start();
            include($this->options['dir'] . ($this->path{0} == '/' ? '' : '/') . $this->path);
            $html = ob_get_clean();
        } else {
            $html = $engine->render($this->path, $this->injectors, $this->options['dir']);
        }

        $this->emit('rendered');

        // Release rendering flag
        self::$rendering = false;

        return $html;
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
