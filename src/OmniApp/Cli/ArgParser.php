<?php

namespace OmniApp\Cli;

class ArgParser
{
    // Args options
    protected $options = array();

    // Hash args
    protected $args = array();

    // Positional arguments
    protected $positions = array();

    // Current argv
    protected $argv;

    // Executed runner
    protected $runner;

    // Error message
    protected $error;

    /**
     *
     * @var array Parsed params
     */
    public $params = array();

    /**
     * @var array Default options
     */
    protected static $default_options = array(
        'type'     => false,
        'long'     => false,
        'short'    => false,
        'value'    => true,
        'default'  => null,
        'help'     => null,
    );

    const ERROR_FEW_ARGS = 1;
    const ERROR_UNKNOWN_ARG = 2;
    const ERROR_UNMATCHED_TYPE = 3;
    const ERROR_EXPECT_VALUE = 4;

    // Errors
    protected static $errors = array(
        1 => 'two few arguments',
        2 => 'unknown arguments %s',
        3 => 'unmatched arguments type %s of %s',
        4 => 'expect value of %s'
    );

    /**
     * @param array $argv
     */
    public function __construct($argv = array())
    {
        if (!$argv) $argv = $_SERVER['argv'];

        $this->runner = array_shift($argv);
        $this->argv = array_values($argv);
    }

    /**
     * Add arguments
     *
     * @example
     *
     *  $parser->add('--long')      // => params.long
     *
     *  $parser->add('-l|--long')   // => params.long
     *
     *  $parser->add('x')           // => params.x with position 0
     *
     * @param string $param
     * @param array  $opt
     *
     *  `type`  int|bool    the type of value
     *  `long`  string      long arg name
     *  `short` string      short arg name
     *  `help`  string      help text
     */
    public function add($param, $opt = array())
    {
        // Merge with default options
        $opt += self::$default_options;

        // The param need register
        $_param = false;

        // Explode with "|"
        $_args = explode('|', $param);

        // Loop arguments
        foreach ($_args as $_arg) {
            // If is optional arguments
            if ($_len = self::isArg($_arg)) {
                if ($_len === 1) {
                    // Short
                    $opt['short'] = substr($_arg, 1);
                } else {
                    // Long
                    $opt['long'] = substr($_arg, 2);
                    // Set to default param
                    if (!$_param) $_param = $opt['long'];
                }
                // Save arg to lookup for parse
                $this->args[$_arg] = $_param;
                // Set default value
                $this->params[$_param] = null;
            } else {
                $_param = $_arg;
                // Set to positional args
                $this->positions[] = $param;
            }
        }

        // Set short
        if (!$_param) $_param = $opt['short'];

        // Save options
        $this->options[$_param] = $opt;
    }

    public function parse()
    {
        // Next except value?
        $_expect_value = false;

        // Arg position
        $_arg_potion = 0;

        // Loop arguments
        foreach ($this->argv as $v) {
            if ($_name = self::isArg($v)) {
                // If expect value?
                if ($_expect_value) {
                    if ($this->options[$_expect_value]['type'] == 'bool') {
                        // Set true if expect name is bool
                        $this->params[$_expect_value] = true;
                        $_expect_value = false;
                    } else {
                        // If not exists value
                        return $this->error(self::ERROR_EXPECT_VALUE, $_expect_value);
                    }
                }
                // If arg then check args and get param
                if (isset($this->args[$v]) && ($_param = $this->args[$v])) {
                    // Expect value
                    $_expect_value = $_param;
                }
            } elseif ($_expect_value) {
                // Check value
                switch ($this->options[$_expect_value]['type']) {
                    case 'int':
                        if (is_numeric($_expect_value)) {
                            // Convert to int if value is numberic
                            $this->params[$_expect_value] = (int)$v;
                        } else {
                            return $this->error(self::ERROR_UNMATCHED_TYPE, 'int', $_expect_value);
                        }
                        break;
                    case 'bool':
                        if ($v == 'true') {
                            // Check "true" text
                            $this->params[$_expect_value] = true;
                        } elseif ($v == 'false') {
                            // Check "false" text
                            $this->params[$_expect_value] = false;
                        } else {
                            return $this->error(self::ERROR_UNMATCHED_TYPE, 'bool', $_expect_value);
                        }
                    default:
                        // Set value to params
                        $this->params[$_expect_value] = $v;
                }
                $_expect_value = false;
            } elseif (isset($this->positions[$_arg_potion])) {
                // If checked positional argument
                $this->params[$this->positions[$_arg_potion]] = $v;
                $_arg_potion++;
            } else {
                return $this->error(self::ERROR_UNKNOWN_ARG, $v);
            }
        }

        // Check position arguments if all matched
        foreach ($this->positions as $pos) {
            // Check value if exists?
            if (!isset($this->params[$pos])) {
                return $this->error(self::ERROR_FEW_ARGS);
            }
        }

        // Return matched params
        return $this->params;
    }

    /**
     * Get usage
     *
     * @return string
     */
    public function usage()
    {
        $chunks = array('usage: ' . $this->runner);

        foreach ($this->options as $param => $opt) {
            if (!in_array($param, $this->positions)) {
                $chunks[] = '[' . ($opt['long'] ? '--' . $opt['long'] : '-' . $opt['short']) . ' ' . strtoupper($param) . ']';
            }
        }

        foreach ($this->positions as $param) {
            $chunks[] = $param;
        }

        $usage = join(' ', $chunks) . PHP_EOL;

        return $usage;
    }

    /**
     * Get help text
     *
     * @return string
     */
    public function help()
    {
        $help = '';

        $optionals = array();
        $positional = array();
        foreach ($this->options as $param => $opt) {
            if (!in_array($param, $this->positions)) {
                $line = '  ' . ($opt['short'] ? '-' . $opt['short'] . ', ' : '') . ($opt['long'] ? '--' . $opt['long'] : '');
                $line = str_pad($line, 30, ' ', STR_PAD_RIGHT) . $opt['help'];
                $optionals[] = $line;
            }
        }

        foreach ($this->positions as $param) {
            $opt = $this->options[$param];
            $line = '  ' . $param;
            $line = str_pad($line, 30, ' ', STR_PAD_RIGHT) . $opt['help'];
            $positional[] = $line;
        }

        if ($positional) {
            $help .= 'positional arguments:' . PHP_EOL;
            $help .= join(PHP_EOL, $positional);
        }

        if ($optionals) {
            $help .= ($positional ? PHP_EOL . PHP_EOL : '') . 'optional arguments:' . PHP_EOL;
            $help .= join(PHP_EOL, $optionals);
        }

        return $this->usage() . PHP_EOL . $help . PHP_EOL;
    }

    /**
     * Get error
     *
     * @param int $type
     * @return bool
     */
    public function error($type = null)
    {
        if ($type !== null && isset(self::$errors[$type])) {
            $_args = array(self::$errors[$type]);
            $this->error = call_user_func_array('sprintf', $_args + func_get_args());
            return false;
        }
        return $this->error ? $this->error : false;
    }

    /**
     * Get last info
     *
     * @return string
     */
    public function info()
    {
        if ($this->error) {
            return $this->usage() . PHP_EOL . 'error: ' . $this->error . PHP_EOL;
        }
        return $this->help();
    }

    /**
     * Check if arguments
     *
     * @param string $str
     * @return bool|int
     */
    protected static function isArg($str)
    {
        if ($str{0} == '-') {
            if ($str{1} == '-') {
                return 2;
            } else {
                return 1;
            }
        }
        return false;
    }
}
