<?php

namespace Pagon\Helper;

use Pagon\App;

class Html
{
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
    public static function entities($value)
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
     * Build a link
     *
     * @param string $src
     * @param string $text
     * @param array  $attributes
     * @return string
     */
    public static function a($src, $text, array $attributes = array())
    {
        return self::element('a', $text, array('src' => Url::to($src)) + $attributes);
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
        return self::element('script', '', ($src ? array('src' => Url::toAsset($src)) : array()) + $attributes + array('type' => 'text/javascript'));
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
        return self::element('link', array('rel' => $rel, 'href' => Url::toAsset($src)) + $attributes);
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
        return self::element('img', array('src' => Url::toAsset($src) + $attributes));
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
     * Build a element
     *
     * @param string $name
     * @param string $text
     * @param array  $attributes
     * @return string
     */
    public static function element($name, $text, array $attributes = array())
    {
        $attr = '';
        foreach ($attributes as $k => $v) {
            if (is_numeric($k)) $k = $v;

            if (!is_null($v)) {
                $attr .= $k . ' = "' . static::entities($v) . '"';
            }
        }

        $self_close = false;
        if (in_array($name, self::$self_close_tags)) {
            $self_close = true;
        }

        return '<' . $name . $attr .
            ($self_close ? ' />' : '>' . $text) .
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