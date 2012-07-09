<?php

namespace Omni;

class Orm extends Model
{
    /**
     * @var \Database
     */
    protected static $_db = 'default';
    protected static $_table;
    protected static $_key = 'id';

    protected $_data;

    /**
     * @static
     * @param $id
     * @return Orm
     */
    public static function load($id)
    {
        list($sql, $params) = static::db()->select('*', static::$_table, array(static::$_key => $id));
        $data = static::db()->row($sql, $params);
        return new self($data ? (array)$data : array(static::$_key => $id));
    }

    public static function db()
    {
        return \Omni\Database::instance(static::$_db);
    }

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) $this->{$key} = $value;
        }
        $this->_data = $data;
    }

    public function save()
    {
        if (!$this->isChanged()) return $this;
        $changed = $this->getChanged();
    }

    public function delete()
    {

    }

    public function getChanged()
    {
        $changed = array();
        foreach ($this->_data as $key => $value) {
            if (!property_exists($this, $key)) {
                $changed[$key] = null;
                continue;
            }
            if ($this->{$key} !== $value) $changed[$key] = $this->$key;
        }
        return $changed;
    }

    public function isChanged()
    {
        foreach ($this->_data as $key => $value) {
            if (!property_exists($this, $key)) return true;
            if ($this->{$key} !== $value) return true;
        }
        return false;
    }

    public function getAsArray()
    {

    }
}