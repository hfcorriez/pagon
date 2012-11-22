<?php

namespace OmniApp\Cli;

class Util
{
    /**
     * Color output text for the CLI
     *
     * @param string      $text       to color
     * @param string      $color      of text
     * @param bool|string $bold       color
     * @return string
     */
    public static function colorize($text, $color, $bold = FALSE)
    {
        $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black'));
        return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . "m$text\033[0m";
    }

}