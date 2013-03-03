<?php

namespace Pagon\Helper;

class Cli
{
    /**
     * Color output text for the CLI
     *
     * @param string      $text       to color
     * @param string      $color      of text
     * @param string      $bg_color   background color
     * @param bool        $bold
     * @param bool        $extra
     * @return string
     */
    public static function text($text, $color, $bg_color = 'default', $bold = false, $extra = false)
    {
        $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white'));

        $bg_colors = array_flip(array(40 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 49 => 'default'));

        $extras = array_flip(array(4 => 'underscore', 'blink'));

        return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . ';' . $bg_colors[$bg_color] . "m$text\033[0" . ($extra ? $extras[$extra] : '') . "m";
    }

}