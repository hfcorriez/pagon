<?php
use Pagon\Helper\Cli;
use Pagon\Helper\Debug;

function console_output($text, $color, $bg_color, $length = 80)
{
    if (is_array($text)) {
        foreach ($text as $i => $t) {
            $text[$i] = console_output($t, $color, $bg_color);
        }
        return join(PHP_EOL, $text);
    }
    return Cli::text('  ' . str_pad(substr($text, 0, $length), $length, ' '), $color, $bg_color);
}

?>
<?php echo
    console_output(
        array('', "$type [$code]: $message", '')
        , 'white', 'red'
    ) . PHP_EOL;

echo  console_output(str_replace(getcwd(), '', $file) . " [$line]", 'purple', 'white'). PHP_EOL;
$source = PHP_EOL . Debug::source($file, $line);
$source = str_replace(array('<span class="line">', '</span>', '</code></pre>', '<pre class="source"><code>', '<span class="number">'), '', $source);
$source = htmlspecialchars_decode($source);
foreach (explode("\n", $source) as $line) {
    if (!$line) continue;
    if (strpos($line, '<span class="line highlight">') === false) {
        echo console_output($line, 'black', 'white') . PHP_EOL;
    } else {
        echo console_output(str_replace('<span class="line highlight">', '', $line), 'black', 'yellow') . PHP_EOL;
    }
}
echo str_replace(getcwd(), '', $info) . PHP_EOL;
?>
