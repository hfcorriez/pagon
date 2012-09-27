<?php

namespace OmniApp\Schema;

/**
 * Omni ArrayObject implements object get and set.
 */
class ArrayObject extends \ArrayObject
{
    public function __set($name, $val)
    {
        $this[$name] = $val;
    }

    public function &__get($name)
    {
        if (array_key_exists($name, $this)) {
            $ret = &$this[$name];
        } else $ret = null;
        return $ret;
    }

    public function __isset($name)
    {
        return isset($this[$name]);
    }

    public function __unset($name)
    {
        unset($this[$name]);
    }
}