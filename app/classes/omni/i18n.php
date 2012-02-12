<?php

namespace Omni
{
    /**
     * 多语言支持
     *
     * @author corriezhao
     */
    class I18n extends Module
    {
        public static $lang = 'en-US';

        private static $_cache = array();
        private static $_config;

        public static function init($config = array())
        {
            self::$_config = $config;
            if (!self::$_config['lang'] || !self::$_config['langpath']) throw new Exception('Config->lang and Config->langpath must be set.');

            Event::on(EVENT_RUN, function()
            {
                I18n::lang(I18n::preferedLanguage(App::$config->lang));
            });
        }

        public static function lang($lang = NULL)
        {
            if ($lang) self::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
            return self::$lang;
        }

        public static function get($string, $lang = NULL)
        {
            if (!$lang) $lang = self::$lang;
            $table = self::_load($lang);
            return isset($table[$string]) ? $table[$string] : $string;
        }

        public static function preferedLanguage($languages = array())
        {
            if (isset($_COOKIE['lang'])) return $_COOKIE['lang'];
            if (!$languages) return 'en-US';

            preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" . "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $hits, PREG_SET_ORDER);
            $bestlang = $languages[0];
            $bestqval = 0;

            foreach ($hits as $arr)
            {
                $langprefix = strtolower($arr[1]);
                if (!empty($arr[3])) {
                    $langrange = strtolower($arr[3]);
                    $language = $langprefix . "-" . $langrange;
                }
                else $language = $langprefix;
                $qvalue = 1.0;
                if (!empty($arr[5])) $qvalue = floatval($arr[5]);

                if (in_array($language, $languages) && ($qvalue > $bestqval)) {
                    $bestlang = $language;
                    $bestqval = $qvalue;
                }
                else if (in_array($langprefix, $languages) && (($qvalue * 0.9) > $bestqval)) {
                    $bestlang = $langprefix;
                    $bestqval = $qvalue * 0.9;
                }
            }
            return $bestlang;
        }

        private static function _load($lang)
        {
            if (isset(self::$_cache[$lang])) return self::$_cache[$lang];

            $table = array();
            $parts = explode('-', $lang);
            $path = implode('/', $parts);

            $files = array(
                self::$_config['langpath'] . '/' . $path . '.php',
                self::$_config['langpath'] . '/' . $lang . '.php',
                self::$_config['langpath'] . '/' . strstr($lang, '-', true) . '.php',
            );

            foreach ($files as $file)
            {
                if (file_exists($file)) {
                    $table = include($file);
                    break;
                }
            }

            return self::$_cache[$lang] = $table;
        }
    }

}

// global functions
namespace {

    if (!function_exists('__')) {
        function __($string, array $values = NULL, $lang = 'en')
        {
            if ($lang !== \Omni\I18n::$lang) $string = \Omni\I18n::get($string);
            return empty($values) ? $string : strtr($string, $values);
        }
    }

}