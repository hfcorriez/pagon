<?php

namespace Pagon\Helper;

class Cli
{
    /**
     * @var array Colors
     */
    protected static $colors = array('black' => 0, 'red' => 1, 'green' => 2, 'yellow' => 3, 'blue' => 4, 'purple' => 5, 'cyan' => 6, 'white' => 7);

    /**
     * @var array Cli text options
     */
    protected static $displays = array('default' => 0, 'bold' => '1', 'dim' => 2, 'underline' => '4', 'blink' => '5', 'reverse' => '7', 'hidden' => '8');

    /**
     * Color output text for the CLI
     *
     * @param string       $text      The text to print
     * @param string|array $color     The color or options for display
     * @return string
     */
    public static function text($text, $color)
    {
        $options = array();
        if (is_string($color)) {
            if (isset(self::$colors[$color])) $options[] = '3' . self::$colors[$color];
        } else if (is_array($color)) {
            foreach ($color as $key => $value) {
                switch ((string)$key) {
                    case 'color':
                        if (isset(self::$colors[$value])) $options[] = '3' . self::$colors[$value];
                        break;
                    case 'background':
                        if (isset(self::$colors[$value])) $options[] = '4' . self::$colors[$value];
                        break;
                    default:
                        if (isset(self::$displays[$value])) $options[] = self::$displays[$value];
                        break;
                }
            }
        }
        return "\033[" . join(';', $options) . "m$text\033[0m";
    }

    /**
     * Prompt a message and get return
     *
     * @param string $text
     * @return string
     */
    public static function prompt($text)
    {
        print (string)$text;
        $fp = fopen('php://stdin', 'r');
        $input = trim(fgets($fp, 1024), "\n ");
        fclose($fp);
        return $input;
    }

    /**
     * Confirm message
     *
     * @param string $text
     * @param bool   $default
     * @return bool
     */
    public static function confirm($text, $default = false)
    {
        print (string)$text . ' [' . ($default ? 'Y/n' : 'y/N') . ']: ';
        $fp = fopen('php://stdin', 'r');
        $i = 2;
        while (($input = trim(strtolower(fgets($fp, 1024)))) && !in_array($input, array('', 'y', 'n')) && $i > 0) {
            echo PHP_EOL . 'Confirm: ';
            $i--;
        }

        if ($i == 0) die(PHP_EOL . 'Error input');

        $ret = $input === '' ? ($default === true ? true : false) : ($input === 'y' ? true : false);

        fclose($fp);

        return $ret;
    }
}