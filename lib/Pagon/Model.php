<?php

namespace Pagon;

abstract class Model
{
    public $id;

    protected $_data = array();
    protected $_previous = array();
    protected $_properties = null;
    protected $_defaults = array();
    protected $_key = 'id';

    /**
     * Construct new model
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->buildProperties();

        // Set defaults to data
        if ($this->_defaults) {
            $data += $this->_defaults;
        }

        // Loop to set data
        foreach ($data as $key => $value) {
            if ($this->has($key)) {
                $this->{$key} = $value;
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * Get property
     *
     * @param $key
     * @return null|mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) return null;

        return $this->{$key};
    }

    /**
     * Set one property or multiple properties
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        if (is_string($key)) {
            if ($this->has($key)) $this->{$key} = $value;
        } elseif (is_array($key)) {
            foreach ($key as $k => $v) $this->set($k, $v);
        }
    }

    /**
     * Has property?
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->hasProperty($key);
    }

    /**
     * Del a property
     *
     * @param $key
     * @return bool
     */
    public function del($key)
    {
        return $this->delProperty($key);
    }

    /**
     * Clear all properties
     */
    public function clear()
    {
        foreach ($this->_properties as $key => $null) {
            $this->delProperty($key);
        }
    }

    /**
     * Get key
     */
    public function key()
    {
        return $this->{$this->_key};
    }

    /**
     * Save current values
     *
     * @return void
     */
    public function save()
    {
        if (!$this->hasChanged()) return;

        $this->_previous = $this->_data;
        $this->_data = $this->changed() + $this->_data;
    }

    /**
     * Get changed values
     *
     * @return array
     */
    public function changed()
    {
        $changed = array();
        foreach ($this->_properties as $key => $null) {
            if ($this->_key === $key) continue;
            if ($this->{$key} !== $this->data($key)) $changed[$key] = $this->$key;
        }
        return $changed;
    }

    /**
     * Get defaults
     *
     * @param string $key
     * @return array
     */
    public function defaults($key = null)
    {
        if ($key && !$this->has($key)) return null;

        if ($key) {
            return isset($this->_defaults[$key]) ? $this->_defaults[$key] : null;
        }

        return $this->_defaults;
    }

    /**
     * Get data
     *
     * @param string $key
     * @return array|null
     */
    public function data($key = null)
    {
        if ($key && !$this->has($key)) return null;

        if ($key) {
            return isset($this->_data[$key]) ? $this->_data[$key] : null;
        }

        return $this->_data;
    }

    /**
     * Get previous data
     *
     * @param $key
     * @return mixed
     */
    public function previous($key = null)
    {
        if ($key && !$this->has($key)) return null;

        if ($key) {
            return isset($this->_previous[$key]) ? $this->_previous[$key] : null;
        }

        return $this->_previous;
    }

    /**
     * Check if changed
     *
     * @param $key
     * @return bool
     */
    public function hasChanged($key = null)
    {
        if ($this->isNew()) return !empty($this->_data);
        if ($key && !$this->has($key)) return false;

        if ($key) {
            return $this->{$key} !== $this->_data[$key];
        } else {
            foreach ($this->_data as $key => $value) {
                if ($this->{$key} !== $value) return true;
            }
        }
        return false;
    }

    /**
     * Is valid?
     */
    public function isValid()
    {
        return true;
    }

    /**
     * If new object?
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->id ? true : false;
    }

    /**
     * Export as array
     *
     * @return array
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->_properties as $key => $null) {
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
     * Init properties
     */
    protected function buildProperties()
    {
        $this->_properties = array();
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!self::_isValidProperty($key)) continue;
            $this->_properties[$key] = $key;
            $this->_data[$key] = $value;
        }
    }

    /**
     * Get properties
     *
     * @return array|null
     */
    protected function getProperties()
    {
        if (null === $this->_properties) {
            $this->buildProperties();
        }
        return $this->_properties;
    }

    /**
     * Has property?
     *
     * @param $key
     * @return bool
     */
    protected function hasProperty($key)
    {
        $properties = &$this->getProperties();
        return in_array($key, $properties);
    }

    /**
     * Delete a property
     *
     * @param $key
     * @return bool
     */
    protected function delProperty($key)
    {
        if ($this->hasProperty($key)) {
            unset($this->{$key});
            unset($this->_properties[$key]);
            return true;
        }
        return false;
    }

    /**
     * Add a property
     *
     * @param $key
     * @return bool
     */
    protected function addProperty($key)
    {
        if (!self::_isValidProperty($key)) return false;

        $this->{$key} = null;
        $this->_properties[$key] = $key;
        return true;
    }

    /**
     * Valida property name
     *
     * @param $key
     * @return bool
     */
    private static function _isValidProperty($key)
    {
        return is_string($key) && $key{0} != '_' && !is_numeric($key{0});
    }

}
