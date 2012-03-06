<?php

namespace Omni;

class Cli
{

    /**
     * Returns one or more command-line options. Options are specified using
     * standard CLI syntax:
     *
     *     php index.php --username=john.smith --password=secret --var="some value with spaces"
     *
     *     // Get the values of "username" and "password"
     *     $auth = CLI::options('username', 'password');
     *
     * @param   string  option name
     * @param   ...
     * @return  array
     */
    public static function options()
    {
        // Get all of the requested options
        $options = func_get_args();

        // Found option values
        $values = array();

        // Skip the first option, it is always the file executed
        for ($i = 1; $i < $_SERVER['argc']; $i++)
        {
            if (!isset($_SERVER['argv'][$i]))
            {
                // No more args left
                break;
            }

            // Get the option
            $opt = $_SERVER['argv'][$i];

            if (substr($opt, 0, 2) !== '--')
            {
                // This is not an option argument
                continue;
            }

            // Remove the "--" prefix
            $opt = substr($opt, 2);

            if (strpos($opt, '='))
            {
                // Separate the name and value
                list ($opt, $value) = explode('=', $opt, 2);
            }
            else
            {
                $value = NULL;
            }

            if (in_array($opt, $options))
            {
                // Set the given value
                $values[$opt] = $value;
            }
        }

        return $values;
    }

    /**
     * Color output text for the CLI
     *
     * @param string $text to color
     * @param string $color of text
     * @param string $background color
     */
    public static function colorize($text, $color, $bold = FALSE)
    {
        // Standard CLI colors
        $colors = array_flip(array(30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black'));

        // Escape string with color information
        return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . "m$text\033[0m";
    }

} // End CLI