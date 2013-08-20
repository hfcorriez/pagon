<?php

namespace Pagon;

/**
 * Cli
 * functional usage for CLI mode
 *
 * @package Pagon
 */
class Console
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
     * @param bool         $auto_br
     * @return string
     * @example
     *
     *  text('Hello', 'red');       // "Hello" with red color
     *  text('Hello', true);        // "Hello" with PHP_EOL
     *  text('Hello', 'red', true)  // "Hello" with red color and PHP_EOL
     *  text('Hello', 'red', "\r")  // "Hello" with red color and "\r" line ending
     */
    public static function text($text, $color, $auto_br = false)
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
        } else if ($color === true) {
            $auto_br = $color;
        }

        return ($options ? "\033[" . join(';', $options) . "m$text\033[0m" : $text)
        . ($auto_br ? (is_bool($auto_br) ? PHP_EOL : $auto_br) : '');
    }

    /**
     * Prompt a message and get return
     *
     * @param string   $text
     * @param bool|int $password
     * @param int      $retry
     * @throws \RuntimeException
     * @return string
     * @example
     *
     *  prompt('Your username: ')           // Display input
     *  prompt('Your password: ', true)     // Hide input
     *  prompt('Your password: ', true, 10) // Hide input and retry 3
     *  prompt('Your username: ', 5)        // Display input and retry 5
     */
    public static function prompt($text, $password = false, $retry = 3)
    {
        $input = '';

        if (is_numeric($password)) {
            $retry = $password;
            $password = false;
        }

        while (!$input && $retry > 0) {
            if (!$password) {
                echo (string)$text;
                $input = trim(fgets(STDIN, 1024), "\n");
            } else {
                $command = "/usr/bin/env bash -c 'echo OK'";
                if (rtrim(shell_exec($command)) !== 'OK') {
                    throw new \RuntimeException("Can not invoke bash to input password!");
                }
                $command = "/usr/bin/env bash -c 'read -s -p \""
                    . addslashes($text)
                    . "\" input && echo \$input'";
                $input = rtrim(shell_exec($command));
                echo "\n";
            }
            $retry--;
        }
        return $input;
    }

    /**
     * Interactive mode
     *
     * @param string   $text
     * @param \Closure $cb
     */
    public static function interactive($text, $cb)
    {
        while (true) {
            echo (string)$text;
            $input = trim(fgets(STDIN, 1024), "\n");
            $cb($input);
            echo PHP_EOL;
        }
    }

    /**
     * Confirm message
     *
     * @param string $text
     * @param bool   $default
     * @param int    $retry
     * @return bool
     * @example
     *
     *  confirm('Are you sure?', true)  // Will confirm with default "true" by empty input
     */
    public static function confirm($text, $default = false, $retry = 3)
    {
        print (string)$text . ' [' . ($default ? 'Y/n' : 'y/N') . ']: ';
        $retry--;
        while (($input = trim(strtolower(fgets(STDIN, 1024)))) && !in_array($input, array('', 'y', 'n')) && $retry > 0) {
            echo PHP_EOL . 'Confirm: ';
            $retry--;
        }

        if ($retry == 0) die(PHP_EOL . 'Error input');

        $ret = $input === '' ? ($default === true ? true : false) : ($input === 'y' ? true : false);

        return $ret;
    }

    /**
     * Execute the command
     *
     * @param string $cmd
     * @param string $stdout
     * @param string $stderr
     * @param int    $timeout
     * @return int
     */
    public static function exec($cmd, &$stdout, &$stderr, $timeout = 3600)
    {
        if ($timeout <= 0) $timeout = 3600;

        $descriptors = array(
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $stdout = $stderr = $status = null;
        $process = proc_open($cmd, $descriptors, $pipes);

        $time_end = time() + $timeout;
        if (is_resource($process)) {
            do {
                $time_left = $time_end - time();
                $read = array($pipes[1]);
                stream_select($read, $write = NULL, $exceptions = NULL, $time_left, NULL);
                $stdout .= fread($pipes[1], 2048);
            } while (!feof($pipes[1]) && $time_left > 0);
            fclose($pipes[1]);

            if ($time_left <= 0) {
                proc_terminate($process);
                $stderr = 'process terminated for timeout.';
                return -1;
            }

            while (!feof($pipes[2])) {
                $stderr .= fread($pipes[2], 2048);
            }
            fclose($pipes[2]);

            $status = proc_close($process);
        }

        return $status;
    }

    /**
     * Execute the command with pipe
     *
     * @static
     * @param $cmd
     * @return int
     */
    public static function pipe($cmd)
    {
        return pclose(popen($cmd . ' &', 'r'));
    }
}