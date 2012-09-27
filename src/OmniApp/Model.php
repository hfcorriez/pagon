<?php

namespace OmniApp;

abstract class Model
{
    /**
     * @var \Database
     */
    protected $_data;

    /**
     * Construct new model
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            if ($this->__isValidProperty($key)) {
                $this->{$key} = $value;
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * Save current values
     *
     * @return void
     */
    public function save()
    {
        if (!$this->isChanged()) return;
        $this->_data = $this->getChanged() + $this->_data;
    }

    /**
     * Get changed values
     *
     * @return array
     */
    public function getChanged()
    {
        $changed = array();
        foreach ($this->_data as $key => $value) {
            if ($this->{$key} !== $value) $changed[$key] = $this->$key;
        }
        return $changed;
    }

    /**
     * Check if changed
     *
     * @return bool
     */
    public function isChanged()
    {
        foreach ($this->_data as $key => $value) {
            if ($this->{$key} !== $value) return true;
        }
        return false;
    }

    /**
     * Export as array
     *
     * @return array
     */
    public function getAsArray()
    {
        $data = array();
        foreach ($this->_data as $key => $value) {
            $data[$key] = $this->{$key};
        }
        return $data;
    }

    /**
     * Forbidden set non-exists property
     *
     * @param        $key
     * @param string $value
     * @throws \Exception
     */
    public function __set($key, $value = null)
    {
        throw new \Exception(__CLASS__ . " has no property named '$key'.");
    }

    /**
     * Forbidden get non-exists property
     *
     * @param $key
     * @throws \Exception
     */
    public function __get($key)
    {
        throw new \Exception(__CLASS__ . " has no property named '$key'.");
    }

    /**
     * Valida property name
     *
     * @param $key
     * @return bool
     */
    private function __isValidProperty($key)
    {
        return $key{0} != '_' && property_exists($this, $key);
    }
}
