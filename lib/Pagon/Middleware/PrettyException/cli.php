<?php
use Pagon\Cli;
use Pagon\Debug;

function console_output($text, $option, $length = 50)
{
    if (is_array($text)) {
        foreach ($text as $i => $t) {
            $text[$i] = console_output($t, $option, $length);
        }
        return join(PHP_EOL, $text);
    }

    if (strlen($text) <= $length) {
        return Cli::text('  ' . str_pad($text, $length, ' ') . ' ', $option);
    } else {
        return console_output(str_split($text, $length), $option, $length);
    }
}

?>
<?php echo
    console_output(
        array('', "$type [$code]: $message", ''),
        array('color' => 'white', 'background' => 'red')
    ) . PHP_EOL;

echo console_output("$file [$line]", array('color' => 'purple', 'background' => 'white')) . PHP_EOL;
$source = PHP_EOL . Debug::source($file, $line);
$source = str_replace(array('<span class="line">', '</span>', '</code></pre>', '<pre class="source"><code>', '<span class="number">'), '', $source);
$source = htmlspecialchars_decode($source);
foreach (explode("\n", $source) as $line) {
    if (!$line) continue;
    if (strpos($line, '<span class="line highlight">') === false) {
        echo console_output($line, array('color' => 'black', 'background' => 'white')) . PHP_EOL;
    } else {
        echo console_output(str_replace('<span class="line highlight">', '', $line), array('color' => 'black', 'background' => 'yellow')) . PHP_EOL;
    }
}
echo console_output('Use "--debug-trace" for more info.', array('background' => 'blue', 'color' => 'white')) . PHP_EOL;
if (array_search('--debug-trace', $GLOBALS['argv'])) {
    echo PHP_EOL . Cli::text($info, array('underline', 'color' => 'yellow')) . PHP_EOL;
}
?>
