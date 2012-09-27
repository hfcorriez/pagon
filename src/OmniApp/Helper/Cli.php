<?php

namespace OmniApp\Helper;


/**
 * Cli
 */
class Cli
{
    /**
     * Returns one or more command-line options. Options are specified using
     * standard CLI syntax:
     *     php index.php --username=john.smith --password=secret --var="some value with spaces"
     *     // Get the values of "username" and "password"
     *     $auth = CLI::options('username', 'password');
     *
     * @param   string  option name
     * @param   ...
     * @return  array
     */
    public static function options()
    {
        $options = func_get_args();
        $values = array();

        for ($i = 1; $i < $_SERVER['argc']; $i++) {
            if (!isset($_SERVER['argv'][$i])) break;

            $opt = $_SERVER['argv'][$i];
            if (substr($opt, 0, 2) !== '--') continue;
            $opt = substr($opt, 2);

            if (strpos($opt, '=')) {
                list ($opt, $value) = explode('=', $opt, 2);
            } else $value = NULL;

            if (in_array($opt, $options)) $values[$opt] = $value;
        }

        return $values;
    }

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