<?php

if(!function_exists('__'))
{
    function __($string, array $values = NULL, $lang = 'en')
    {
        if ($lang !== \Omni\I18n::$lang) $string = \Omni\I18n::get($string);
        return empty($values) ? $string : strtr($string, $values);
    }
}