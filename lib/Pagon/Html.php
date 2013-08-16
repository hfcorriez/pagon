<?php

namespace Pagon;

use Pagon\App;

/**
 * Html
 * functional usage for generate HTML code
 *
 * @package Pagon
 */
class Html
{
    /**
     * @var array Macros
     */
    public static $macros = array();

    /**
     * @var string Encode for HTML
     */
    protected static $charset = 'utf-8';

    /**
     * @var array Allow self close tags
     */
    protected static $self_close_tags = array(
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    );

    /**
     * Covert to entitles
     *
     * @param string $value
     * @return string
     */
    public static function encode($value)
    {
        return htmlentities($value, ENT_QUOTES, static::charset(), false);
    }

    /**
     * Convert entities to HTML characters.
     *
     * @param  string $value
     * @return string
     */
    public static function decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES, static::charset());
    }

    /**
     * Convert HTML special characters.
     *
     * @param  string $value
     * @return string
     */
    public static function specialChars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, static::charset(), false);
    }

    /**
     * Get or set macro
     *
     * @param string          $key
     * @param string|\Closure $html
     * @return mixed
     */
    public static function macro($key, $html = null)
    {
        if ($html == null) {
            return isset(self::$macros[$key]) ? (is_callable(self::$macros[$key]) ? call_user_func(self::$macros[$key]) : self::$macros[$key]) : null;
        }

        return self::$macros[$key] = $html;
    }

    /**
     * Build a link
     *
     * @param string $src
     * @param string $text
     * @param array  $attributes
     * @return string
     */
    public static function a($src, $text, array $attributes = array())
    {
        return self::dom('a', $text, array('href' => Url::to($src)) + $attributes);
    }

    /**
     * Build a script
     *
     * @param string $src
     * @param array  $attributes
     * @return string
     */
    public static function script($src, array $attributes = array())
    {
        return self::dom('script', '', ($src ? array('src' => Url::asset($src)) : array()) + $attributes + array('type' => 'text/javascript'));
    }

    /**
     * Build a link
     *
     * @param string $src
     * @param string $rel
     * @param array  $attributes
     * @return string
     */
    public static function link($src, $rel = 'stylesheet', array $attributes = array())
    {
        return self::dom('link', array('rel' => $rel, 'href' => Url::asset($src)) + $attributes);
    }

    /**
     * Alias img
     *
     * @param string $src
     * @param array  $attributes
     * @return string
     */
    public static function image($src, array $attributes = array())
    {
        return self::img($src, $attributes);
    }

    /**
     * Build a image
     *
     * @param string $src
     * @param array  $attributes
     * @return string
     */
    public static function img($src, array $attributes = array())
    {
        return self::dom('img', array('src' => Url::asset($src)) + $attributes);
    }

    /**
     * A simply style link
     *
     * @param string $src
     * @return string
     */
    public static function style($src)
    {
        return self::link($src);
    }

    /**
     * Select options
     *
     * @param string $name
     * @param array  $options
     * @param string $selected
     * @param array  $attributes
     * @return string
     */
    public static function select($name, array $options, $selected = null, array $attributes = array())
    {
        $options_html = array();
        foreach ($options as $key => $value) {
            $attr = array('value' => $key);
            if ($key == $selected) {
                $attr['selected'] = 'true';
            }
            $options_html[] = self::dom('option', $value, $attr);
        }
        return self::dom('select', join('', $options_html), array('name' => $name) + $attributes);
    }

    /**
     * Checkboxes
     *
     * @param string         $name
     * @param array          $values
     * @param null|string    $checked
     * @param array          $attributes
     * @param array|callable $wrapper
     * @return string
     */
    public static function checkboxes($name, array $values, $checked = null, $attributes = null, $wrapper = null)
    {
        return self::inputGroup($name, 'checkbox', $values, $checked, $attributes, $wrapper);
    }

    /**
     * Radios
     *
     * @param string         $name
     * @param array          $values
     * @param null|string    $checked
     * @param array          $attributes
     * @param array|callable $wrapper
     * @return string
     */
    public static function radios($name, array $values, $checked = null, $attributes = null, $wrapper = null)
    {
        return self::inputGroup($name, 'radio', $values, $checked, $attributes, $wrapper);
    }

    /**
     * Input group
     *
     * @param string         $name
     * @param string         $type
     * @param array          $values
     * @param null|string    $checked
     * @param array          $attributes
     * @param array|callable $wrapper
     * @return string
     */
    public function inputGroup($name, $type, array $values, $checked = null, $attributes = null, $wrapper = null)
    {
        return self::loop($values, function ($key, $value) use (
            $name, $type, $checked, $attributes, $wrapper
        ) {
            $attr = array('value' => $key, 'type' => $type, 'name' => $name) + (array)$attributes;
            if ($checked) {
                if (is_array($checked) && in_array($key, $checked)
                    || $checked == $key
                ) {
                    $attr['checked'] = 'true';
                }
            }
            if ($wrapper) {
                if (is_array($wrapper)) {
                    return Html::dom($wrapper[0], Html::dom('input', $value, $attr), isset($wrapper[1]) ? (array)$wrapper[1] : null);
                } else if (is_callable($wrapper)) {
                    return $wrapper(Html::dom('input', $value, $attr));
                }
            } else {
                return Html::dom('input', $value, $attr);
            }
        });
    }

    /**
     * Repeat elements
     *
     * @param string $dom
     * @param array  $values
     * @param array  $attributes
     * @return string
     */
    public static function repeat($dom, array $values, array $attributes = null)
    {
        return self::loop($values, function ($value) use ($dom, $attributes) {
            return Html::dom($dom, $value, $attributes);
        });
    }

    /**
     * Loop and join as string
     *
     * @param array    $values
     * @param \Closure $mapper
     * @return string
     */
    public static function loop(array $values, \Closure $mapper)
    {
        $html = array();
        foreach ($values as $key => $value) {
            $html[] = $mapper($value, $key);
        }
        return join('', $html);
    }

    /**
     * Build a element
     *
     * @param string $name
     * @param string $text
     * @param array  $attributes
     * @return string
     */
    public static function dom($name, $text = '', array $attributes = null)
    {
        $self_close = false;
        if (in_array($name, self::$self_close_tags)) {
            $self_close = true;
        }

        if ($self_close && is_array($text)) {
            $attributes = $text;
            $text = '';
        }

        $attr = '';
        $attributes = (array)$attributes;
        foreach ($attributes as $k => $v) {
            if (is_numeric($k)) $k = $v;

            if (!is_null($v)) {
                $attr .= ' ' . $k . '="' . static::encode($v) . '"';
            }
        }

        return '<' . $name . $attr .
        ($self_close ? ' />' : '>') . $text .
        ((!$self_close) ? '</' . $name . '>' : '');
    }

    /**
     * Get current encoding
     *
     * @return mixed
     */
    protected static function charset()
    {
        return static::$charset ? : static::$charset = App::self()->get('charset');
    }
}