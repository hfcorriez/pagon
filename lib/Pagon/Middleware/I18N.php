<?php

namespace Pagon\Middleware {

    use Pagon\Middleware;

    class I18N extends Middleware
    {
        /**
         * @return mixed
         */
        function call()
        {
            I18NTranslator::init($this->injectors);
            $this->next();
        }
    }

    /**
     * I18N
     */
    class I18NTranslator
    {
        protected static $lang = 'en-US';
        protected static $cache = array();
        protected static $enable = false;
        protected static $config = array();

        /**
         * Init i18n config
         *
         * @static
         */
        public static function init(array $config = array())
        {
            self::$config += $config;

            if (!self::$config) return;

            if (self::$config['lang'] && self::$config['dir']) self::$enable = true;
            self::lang(self::preferLanguage(self::$config['lang']));
        }

        /**
         * Get or set language
         *
         * @static
         * @param null|string $lang
         * @return string
         */
        public static function lang($lang = NULL)
        {
            if ($lang) self::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
            return self::$lang;
        }

        /**
         * Get words translation
         *
         * @static
         * @param             $string
         * @param null|string $lang
         * @return string
         */
        public static function get($string, $lang = NULL)
        {
            if (!$lang) $lang = self::$lang;
            $table = self::load($lang);
            return isset($table[$string]) ? $table[$string] : $string;
        }

        /**
         * Automatic match best language
         *
         * @static
         * @param array $languages
         * @return string
         */
        public static function preferLanguage($languages = array())
        {
            if (!$languages) return 'en-US';

            preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" . "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
                $_SERVER['HTTP_ACCEPT_LANGUAGE'], $hits, PREG_SET_ORDER);
            $best_lang = $languages[0];
            $best_q_val = 0;

            foreach ($hits as $arr) {
                $lang_prefix = strtolower($arr[1]);
                if (!empty($arr[3])) {
                    $lang_range = strtolower($arr[3]);
                    $language = $lang_prefix . "-" . $lang_range;
                } else $language = $lang_prefix;
                $q_value = 1.0;
                if (!empty($arr[5])) $q_value = floatval($arr[5]);

                if (in_array($language, $languages) && ($q_value > $best_q_val)) {
                    $best_lang = $language;
                    $best_q_val = $q_value;
                } else {
                    if (in_array($lang_prefix, $languages) && (($q_value * 0.9) > $best_q_val)) {
                        $best_lang = $lang_prefix;
                        $best_q_val = $q_value * 0.9;
                    }
                }
            }
            return $best_lang;
        }

        /**
         * Load language table
         *
         * @static
         * @param $lang
         * @return array
         */
        private static function load($lang)
        {
            if (isset(self::$cache[$lang])) return self::$cache[$lang];
            if (!self::$enable) return false;

            $table = array();
            $parts = explode('-', $lang);
            $path = implode('/', $parts);

            $files = array(
                self::$config['dir'] . '/' . $path . '.php',
                self::$config['dir'] . '/' . $lang . '.php',
                self::$config['dir'] . '/' . strstr($lang, '-', true) . '.php',
            );

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $table = include($file);
                    break;
                }
            }

            return self::$cache[$lang] = $table;
        }
    }
}

/**
 * I18N translate function
 *
 * @param            $string
 * @param array|null $values
 * @param string     $lang
 * @return string
 */
namespace {
    use Pagon\Middleware\I18NTranslator;

    function __($string, array $values = NULL, $lang = 'en')
    {
        if ($lang !== I18NTranslator::lang()) $string = I18NTranslator::get($string);

        return empty($values) ? $string : strtr($string, $values);
    }
}