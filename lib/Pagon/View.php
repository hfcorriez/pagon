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
    protected $injectors = array(
        'dir'    => '',
        'engine' => null,
        'data'   => array()
    );

    /**
     * Construct a view
     *
     * @param string $path
     * @param array  $data
     * @param array  $injectors
     * @throws \Exception
     * @return View
     */
    public function __construct($path, $data = array(), $injectors = array())
    {
        // Set dir for the view
        $injectors = array('data' => (array)$data, 'path' => $path) + $injectors + $this->injectors;

        // Set path
        $injectors['path'] = ltrim($path, '/');

        // If file exists?
        if (!is_file($injectors['dir'] . '/' . $injectors['path'])) {
            // Try to load file from absolute path
            if ($path{0} == '/' && is_file($path)) {
                $injectors['path'] = $path;
                $injectors['dir'] = '';
            } else if ($injectors['app'] && ($_path = $injectors['app']->path($injectors['path']))) {
                $injectors['path'] = $_path;
                $injectors['dir'] = '';
            } else {
                throw new \Exception('Template file is not exist: ' . $injectors['path']);
            }
        }

        // Set data
        parent::__construct($injectors);
    }

    /**
     * Set variable for view
     *
     * @param array $array
     * @return View
     */
    public function data(array $array = array())
    {
        return $this->injectors['data'] = $array + $this->injectors['data'];
    }

    /**
     * render
     *
     * @return string
     */
    public function render()
    {
        $engine = $this->injectors['engine'];

        // Mark rendering flag
        self::$rendering = true;

        $this->emit('render');

        if (!$engine) {
            if ($this->injectors) {
                extract((array)$this->injectors['data']);
            }
            ob_start();
            include($this->injectors['dir'] . ($this->injectors['path']{0} == '/' ? '' : '/') . $this->injectors['path']);
            $html = ob_get_clean();
        } else if (is_callable($engine)) {
            $html = $engine($this->injectors['path'], $this->injectors['data'], $this->injectors['dir']);
        } else {
            $html = $engine->render($this->injectors['path'], $this->injectors['data'], $this->injectors['dir']);
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
