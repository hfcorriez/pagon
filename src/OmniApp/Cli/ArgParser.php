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
    protected $prog;

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
        'args'     => array(),
        'enum'     => array(),
        'help'     => null,
    );

    const ERROR_FEW_ARGS = 1;
    const ERROR_UNKNOWN_ARG = 2;
    const ERROR_UNMATCHED_TYPE = 3;
    const ERROR_EXPECT_VALUE = 4;
    const ERROR_UNMATCHED_ENUM = 5;

    // Errors
    protected static $errors = array(
        1 => 'two few arguments',
        2 => 'unknown arguments %s',
        3 => 'unmatched arguments type %s of %s',
        4 => 'expect value of %s',
        5 => 'unmatched arguments enum %s of %s',
    );

    /**
     * @param array $argv
     */
    public function __construct($argv = array())
    {
        if (!$argv) $argv = $_SERVER['argv'];

        $this->prog = array_shift($argv);
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
                // Short arg must be one letter
                if ($_len === 1 && strlen($_arg) !== 2) {
                    continue;
                }

                // Save args
                $opt['args'][] = $_arg;

                // Long will be the default param
                if ($_len == 2 && !$_param) {
                    $_param = substr($_arg, $_len);
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

        // Exchange enum's array list as dict
        if ($opt['enum'] && isset($opt['enum'][0])) {
            foreach ($opt['enum'] as $_i => $_e) {
                if (is_numeric($_i)) {
                    unset($opt['enum'][$_i]);
                    $opt['enum'][$_e] = '';
                }
            }
        }

        // Set short
        if (!$_param) $_param = $opt['args'][0];

        // Save options
        $this->options[$_param] = $opt;
    }

    /**
     * Parse to get the params
     *
     * @return array|bool
     */
    public function parse()
    {
        // Next except value?
        $_expect = false;

        // Arg position
        $_potion = 0;

        // Loop arguments
        foreach ($this->argv as $v) {
            if ($_name = self::isArg($v)) {
                // If expect value?
                if ($_expect) {
                    if ($this->options[$_expect]['type'] == 'bool') {
                        // Set true if expect name is bool
                        $this->params[$_expect] = true;
                        $_expect = false;
                    } else {
                        // If not exists value
                        return $this->error(self::ERROR_EXPECT_VALUE, $_expect);
                    }
                }
                // If arg then check args and get param
                if (isset($this->args[$v]) && ($_param = $this->args[$v])) {
                    // Expect value
                    $_expect = $_param;
                }
            } elseif ($_expect) {
                // Check value
                switch ($this->options[$_expect]['type']) {
                    case 'int':
                        if (is_numeric($_expect)) {
                            // Convert to int if value is numberic
                            $this->params[$_expect] = (int)$v;
                        } else {
                            return $this->error(self::ERROR_UNMATCHED_TYPE, 'int', $_expect);
                        }
                        break;
                    case 'bool':
                        if ($v == 'true') {
                            // Check "true" text
                            $this->params[$_expect] = true;
                        } elseif ($v == 'false') {
                            // Check "false" text
                            $this->params[$_expect] = false;
                        } else {
                            return $this->error(self::ERROR_UNMATCHED_TYPE, 'bool', $_expect);
                        }
                        break;
                    case 'array':
                        // Init as array
                        if (!isset($this->params[$_expect])) $this->params[$_expect] = array();

                        // Push to array
                        $this->params[$_expect][] = $v;
                        break;
                    default:
                        if ($this->options[$_expect]['enum'] && !in_array($v, $this->options[$_expect]['enum'])) {
                            return $this->error(self::ERROR_UNMATCHED_ENUM, $v, $_expect);
                        }
                        // Set value to params
                        $this->params[$_expect] = $v;
                }
                $_expect = false;
            } elseif (isset($this->positions[$_potion])) {
                // If checked positional argument
                $this->params[$this->positions[$_potion]] = $v;
                $_potion++;
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
        $chunks = array('usage: ' . $this->prog);

        foreach ($this->options as $param => $opt) {
            if (!in_array($param, $this->positions)) {
                $chunks[] = '[' . join('|', $opt['args']) . ($opt['type'] != 'bool' ? '=<' . ($opt['enum'] ? join('|', $opt['enum']) : strtoupper($param)) . '>' : '') . ']';
            }
        }

        foreach ($this->positions as $param) {
            $chunks[] = '<' . $param . '>';
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
        $blocks = array();
        $optionals = array();
        $positional = array();

        foreach ($this->options as $param => $opt) {
            if (!in_array($param, $this->positions)) {
                $arg = join(', ', $opt['args']);
                $optionals[$arg] = $opt['help'];
            }
        }

        foreach ($this->positions as $param) {
            $positional[$param] = $this->options[$param]['help'];

            if ($enums = $this->options[$param]['enum']) {
                $blocks[] = self::buildBlock("argument <$param> enum:", $enums);
            }
        }

        if ($positional) {
            array_unshift($blocks, self::buildBlock('positional arguments:', $positional));
        }

        if ($optionals) {
            $blocks[] = self::buildBlock('positional arguments:', $optionals);
        }

        return $this->usage() . PHP_EOL . join(PHP_EOL . PHP_EOL, $blocks) . PHP_EOL;
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

    /**
     * Build block help
     *
     * @param $title
     * @param $args
     * @return string
     */
    protected static function buildBlock($title, $args)
    {
        $block = $title;
        foreach ($args as $arg => $help) {
            $block .= PHP_EOL . '    ' . str_pad($arg, 20, ' ', STR_PAD_RIGHT) . $help;
        }
        return $block;
    }
}
