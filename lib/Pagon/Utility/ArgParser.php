<?php

namespace Pagon\Utility;

class ArgParser
{
    // Args options
    protected $options = array();

    // Saved values
    protected $values = array();

    // Hash args
    protected $ids = array();

    // Positional arguments
    protected $positions = array();

    // Current argv
    protected $argv;

    // Executed runner
    protected $program;

    // Error message
    protected $error;

    // Usage
    protected $usage;

    /**
     *
     * @var array Parsed params
     */
    public $params = array();

    /**
     * @var array Default options
     */
    protected static $default_options = array(
        'type'    => false,
        'args'    => array(),
        'enum'    => array(),
        'help'    => null,
        'default' => null,
    );

    const ERROR_FEW_ARGS = 1;
    const ERROR_UNKNOWN_ARG = 2;
    const ERROR_UNMATCHED_TYPE = 3;
    const ERROR_EXPECT_VALUE = 4;
    const ERROR_UNMATCHED_ENUM = 5;

    // Errors
    protected static $errors = array(
        1 => 'two few arguments',
        2 => 'unknown arguments "%s"',
        3 => 'unmatched arguments type "%s" of %s',
        4 => 'expect value "%s"',
        5 => 'unmatched arguments enum "%s" of %s',
    );

    /**
     * @param array  $argv
     * @param string $usage
     */
    public function __construct(array $argv = null, $usage = null)
    {
        if ($argv === null) $argv = $_SERVER['argv'];

        $this->program = array_shift($argv);
        $this->argv = array_values($argv);
        $this->usage = $usage;

        $this->add(array('-h', '--help'), array('help' => 'help of the command', 'type' => 'bool'));
    }

    /**
     * Get or set program
     *
     * @param string $program
     * @return string
     */
    public function program($program = null)
    {
        if ($program) {
            $this->program = $program;
        }

        return $this->program;
    }

    /**
     * Get or set argv
     *
     * @param array $argv
     * @return array
     */
    public function argv(array $argv = null)
    {
        if ($argv) {
            $this->argv = $argv;
        }

        return $this->argv;
    }

    /**
     * Add arguments
     *
     * @example
     *
     *  $parser->add('param')                   // => params.param
     *
     *  $parser->add(array('-l', '--long'))     // => params.long params.l
     *
     * @param string $argument
     * @param array  $option
     *
     *  `type`      string      the type of value   <int|bool|array>
     *  `args`      string      args name
     *  `enum`      string      enumerable list
     *  `help`      string      help text
     *  `default`   string      default value
     * @throws \InvalidArgumentException
     */
    public function add($argument, $option = array())
    {
        // Merge with default options
        $option += self::$default_options;

        // The param need register
        $params = array();

        if (is_string($argument)) {
            if (!preg_match('/^\w+$/', $argument)) {
                throw new \InvalidArgumentException("The input argument '$argument' is invalid");
            }
            $this->positions[] = $params[] = $option['position'] = $argument;
        } elseif (is_array($argument)) {
            foreach ($argument as $arg) {
                // Check pattern
                if (!preg_match('/^[\-]{1,2}\w+$/', $arg)) {
                    throw new \InvalidArgumentException("The input argument '$argument' is invalid");
                }

                if ($arg{1} == '-') {
                    // Long argument
                    $params[] = $option['args'][] = substr($arg, 2);
                } else {
                    $_args_list = str_split(substr($arg, 1));
                    // Short argument
                    $params = array_merge($params, $_args_list);
                    $option['args'] = array_merge($option['args'], $_args_list);
                }
            }
        } else {
            throw new \InvalidArgumentException("Unknown input argument");
        }

        $id = uniqid();

        // Save options
        $this->values[$id] = null;
        $this->options[$id] = $option;

        // Default support
        if (isset($option['default'])) $this->values[$id] = $option['default'];

        // Set link
        foreach ($params as $param) {
            $this->params[$param] = & $this->values[$id];
            $this->ids[$param] = $id;
        }
    }

    /**
     * Parse to get the params
     *
     * @return array|bool
     */
    public function parse()
    {
        if (in_array('-h', $this->argv) || in_array('--help', $this->argv)) {
            print $this->help();
            exit(0);
        }

        try {
            // Next except value?
            $expect_param = false;

            // Arg position
            $potion = 0;

            // Loop arguments
            foreach ($this->argv as $arg) {
                if ($len = $this->isArg($arg)) {
                    $value = false;

                    // If expect value?
                    if ($expect_param) {
                        if (in_array($this->option($expect_param, 'type', false), array(false, 'bool'))) {
                            // Set true if expect name is bool
                            $this->params[$expect_param] = true;
                            $expect_param = false;
                            continue;
                        } else {
                            // If not exists value
                            $this->error(self::ERROR_EXPECT_VALUE, $this->buildArg($expect_param));
                        }
                    }

                    // Check value assign
                    if (strpos($arg, '=')) {
                        list($arg, $value) = explode('=', $arg);
                    }

                    // Check long or short argument
                    if ($len == 1) {
                        $shorts = str_split(substr($arg, 1));
                        if (count($shorts) == 1) {
                            if (isset($this->ids[$shorts[0]])) {
                                if (!$value) {
                                    // Expect value
                                    $expect_param = $shorts[0];

                                    // Pre-set
                                    if (in_array($this->option($shorts[0], 'type', false), array(false, 'bool'))) $this->params[$shorts[0]] = true;
                                } else {
                                    $this->value($expect_param, $value);
                                }
                            } else {
                                $this->error(self::ERROR_UNKNOWN_ARG, $arg);
                            }
                        } else {
                            foreach ($shorts as $short) {
                                $this->params[$short] = true;
                            }
                        }
                    } else {
                        $long = substr($arg, 2);

                        // If arg then check args and get param
                        if (isset($this->ids[$long])) {
                            if (!$value) {
                                // Expect value
                                $expect_param = $long;

                                // Pre-set
                                if (in_array($this->option($long, 'type', false), array(false, 'bool'))) $this->params[$long] = true;
                            } else {
                                $this->value($long, $value);
                            }
                        }
                    }
                } elseif ($expect_param) {
                    $this->value($expect_param, $arg);
                    $expect_param = false;
                } elseif (isset($this->positions[$potion])) {
                    // If checked positional argument
                    $this->params[$this->positions[$potion]] = $arg;
                    $potion++;
                } else {
                    $this->error(self::ERROR_UNKNOWN_ARG, $arg);
                }
            }

            // Check position arguments if all matched
            foreach ($this->positions as $position) {
                // Check value if exists?
                if (!isset($this->params[$position])) {
                    $this->error(self::ERROR_FEW_ARGS);
                }
            }

            // Return matched params
            return $this->params;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $param
     * @param $value
     * @return bool
     */
    protected function value($param, $value)
    {
        $option = $this->options[$this->ids[$param]];
        switch ($option['type']) {
            case 'int':
                if (is_numeric($value)) {
                    // Convert to int if value is numberic
                    $this->params[$param] = (int)$value;
                } else {
                    $this->error(self::ERROR_UNMATCHED_TYPE, 'int', $this->buildArg($param));
                }
                break;
            case 'bool':
                if ($value == 'true' || $value == '1') {
                    // Check "true" text
                    $this->params[$param] = true;
                } elseif ($value == 'false' || $value == '0') {
                    // Check "false" text
                    $this->params[$param] = false;
                } else {
                    $this->error(self::ERROR_UNMATCHED_TYPE, 'bool', $this->buildArg($param));
                }
                break;
            case 'array':
                // Init as array
                $this->params[$param] = array();

                // Push to array
                $this->params[$param][] = $value;
                break;
            default:
                if ($option['enum'] && !in_array($value, $option['enum'])) {
                    $this->error(self::ERROR_UNMATCHED_ENUM, $value, $this->buildArg($param));
                }
                // Set value to params
                $this->params[$param] = $value;
        }
    }

    /**
     * Get usage
     *
     * @param string $usage
     * @return string
     */
    public function usage($usage = null)
    {
        if ($usage) $this->usage = $usage;

        if ($this->usage) return $this->usage;

        $chunks = array('usage: ' . $this->program);

        foreach ($this->options as $option) {
            if (!empty($option['position'])) continue;

            $args = array_map(array($this, 'buildArg'), $option['args']);
            $chunks[] = '[' . join('|', $args) . ($option['type'] != 'bool' ? '=<' . ($option['enum'] ? join('|', $option['enum']) : 'VALUE') . '>' : '') . ']';
        }

        foreach ($this->positions as $param) {
            $chunks[] = '<' . $param . '>';
        }

        $this->usage = join(' ', $chunks) . PHP_EOL;

        return $this->usage;
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

        foreach ($this->positions as $param) {
            $positional[$param] = $this->option($param, 'help');

            if ($enums = $this->option($param, 'enum')) {
                $blocks[] = self::buildHelpBlock("argument <$param> enum:", $enums);
            }
        }

        foreach ($this->options as $option) {
            if (!empty($option['position'])) continue;

            $args = array_map(array($this, 'buildArg'), $option['args']);
            $optionals[join(', ', $args)] = $option['help'];
        }

        if ($positional) {
            array_unshift($blocks, self::buildHelpBlock('positional arguments:', $positional));
        }

        if ($optionals) {
            $blocks[] = self::buildHelpBlock('optional arguments:', $optionals);
        }

        return $this->usage() . PHP_EOL . join(PHP_EOL . PHP_EOL, $blocks) . PHP_EOL;
    }

    /**
     * Get error
     *
     * @param int $type
     * @throws \Exception
     * @return bool
     */
    public function error($type = null)
    {
        if ($type !== null && isset(self::$errors[$type])) {
            $_args = array(self::$errors[$type]);
            $this->error = call_user_func_array('sprintf', $_args + func_get_args());
            throw new \Exception('ArgParser parse error');
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
     * Get id by param
     *
     * @param string $param
     * @return bool
     */
    protected function id($param)
    {
        return isset($this->ids[$param]) ? $this->ids[$param] : false;
    }

    /**
     * Get options
     *
     * @param string $param
     * @param bool   $key
     * @param mixed  $default
     * @return bool|null
     */
    protected function option($param, $key = false, $default = null)
    {
        if (!isset($this->ids[$param])) return false;

        $option = $this->options[$this->ids[$param]];

        if (!$key) return $option;

        return isset($option[$key]) ? $option[$key] : $default;
    }

    /**
     * Check if arguments
     *
     * @param string $str
     * @return bool|int
     */
    protected function isArg($str)
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
     * Build argument
     *
     * @param string $param
     * @return string
     */
    protected function buildArg($param)
    {
        if (!empty($this->options[$this->ids[$param]]['position'])) {
            return $param;
        }

        if (strlen($param) > 1) {
            return '--' . $param;
        }
        return '-' . $param;
    }

    /**
     * Build block help
     *
     * @param $title
     * @param $args
     * @return string
     */
    protected static function buildHelpBlock($title, $args)
    {
        $block = $title;
        foreach ($args as $arg => $help) {
            $block .= PHP_EOL . '    ' . str_pad($arg, 20, ' ', STR_PAD_RIGHT) . $help;
        }
        return $block;
    }
}
