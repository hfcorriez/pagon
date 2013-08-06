<?php

namespace Pagon\Engine;

use Everzet\Jade\Jade as Jader;
use Everzet\Jade\Dumper\PHPDumper;
use Everzet\Jade\Parser;
use Everzet\Jade\Lexer\Lexer;

class Jade
{
    /**
     * @var array Default options
     */
    protected $options = array(
        'compile_dir' => '/tmp'
    );

    protected $engine;

    /**
     * Init the engine
     *
     * @param array $options
     * @throws \RuntimeException
     */
    public function __construct(array $options = array())
    {
        if (!class_exists('\Everzet\Jade\Jade')) {
            throw new \RuntimeException("Use Jade engine need `composer update` to install or include manually.");
        }

        $this->options = $options + $this->options;

        $dumper = new PHPDumper();
        $parser = new Parser(new Lexer());

        $this->engine = new Jader($parser, $dumper, $this->options['compile_dir']);
    }

    /**
     * Implements the render
     *
     * @param string $path
     * @param array  $data
     * @param string $dir
     * @return string
     */
    public function render($path, $data, $dir)
    {
        $file = $this->engine->cache($dir . $path);

        ob_start();
        if ($data) {
            extract((array)$data);
        }
        include($file);

        return ob_get_clean();
    }
}
