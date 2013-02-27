<?php

namespace Pagon\Utility;

class Cli
{
    /**
     * Color output text for the CLI
     *
     * @param string      $text       to color
     * @param string      $color      of text
     * @param bool|string $bold       color
     * @param string      $bg_color   background color
     * @return string
     */
    public static function colorize($text, $color, $bold = false, $bg_color = 'default')
    {
        $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'default'));

        $bg_colors = array_flip(array(40 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'default'));

        return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . ';' . $bg_colors[$bg_color] . "m$text\033[0m";
    }

}