<?php

namespace Model;

class Test extends \Omni\Orm
{
    public $id;
    public $title;

    protected static $_table = 'test';
}